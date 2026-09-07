<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index()
    {
        if (auth()->user()->isUser()) {
            $user = auth()->user();
            $router_name = $user->detail->router_name;
            $router = Router::where("name", $router_name)->firstOrFail();
            $packages = Package::where('router_id', $router->id)->orderBy('name')->get();
            return view('packages.index', compact('packages'));
        }
        
        if (auth()->user()->isAdmin()) {
            $packages = Package::orderBy('name')->get();
            return view('packages.index', compact('packages'));
        }
        
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        
        $routers = Router::orderBy('name')->get();

        if (count($routers) == 0) {
            return redirect('packages')->with('error', __('Add a router first'));
        }

        return view('packages.create', compact('routers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('packages', 'name')->where(function ($query) use ($request) {
                    return $query->where('router_id', $request->router_id);
                })
            ],
            'router_id'=> 'required',
            'price' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'bandwidth_upload' => 'nullable|numeric|min:0',
            'bandwidth_download' => 'nullable|numeric|min:0',
            'shared_users' => 'nullable|integer|min:1',
            'data_cap' => ['nullable', 'string', 'max:50', 'regex:/^\d+\s*(MB|GB)$/i'],
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days',
        ]);

        $validated = array_merge($validated, $this->resolveValidityFromDuration($request));

        $validated = $this->normalizePackageFields($validated);

        $router = Router::where("id", $request->router_id)->firstOrFail();

        $package = new Package();
        $package->fill($validated);
        $package->save();

        $syncMessage = $this->pushPackageToRouter($package);

        $message = __('Hotspot package successfully created');
        if ($syncMessage) {
            $message .= '. ' . $syncMessage;
        }

        return redirect('packages')->with('success', $message);
    }

    public function show(Package $package)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        return view('packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'bandwidth_upload' => 'nullable|numeric|min:0',
            'bandwidth_download' => 'nullable|numeric|min:0',
            'shared_users' => 'nullable|integer|min:1',
            'data_cap' => ['nullable', 'string', 'max:50', 'regex:/^\d+\s*(MB|GB)$/i'],
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days',
        ]);

        $validated = array_merge($validated, $this->resolveValidityFromDuration($request));

        $validated = $this->normalizePackageFields($validated);

        // Update the package in the database
        $package->fill($validated);
        $package->save();

        $syncMessage = $this->pushPackageToRouter($package);

        $message = __('Hotspot package successfully updated');
        if ($syncMessage) {
            $message .= '. ' . $syncMessage;
        }

        return redirect('packages')->with('success', $message);
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy(Package $package)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        // Prevent deletion if there are active or reconnectable sessions
        $activeSessions = \App\Models\HotspotSession::where('package_id', $package->id)
            ->whereIn('status', ['active', 'authorized'])
            ->exists();

        if ($activeSessions) {
            return redirect()->route('packages.index')
                ->with('error', __('Cannot delete package while it has active sessions.'));
        }

        // Clean up the corresponding profile and users on the router if no active sessions
        $router = $package->router;
        if ($router) {
            $mikrotikService = app(\App\Services\MikroTikService::class);
            $result = $mikrotikService->deletePackageProfile($router, $package);

            if (!$result['success']) {
                return redirect()->route('packages.index')
                    ->with('error', __('Package could not be deleted from router: ') . $result['message']);
            }
        }

        // Delete package (non-active hotspot_sessions have FK with cascade delete)
        $package->delete();

        return redirect()->route('packages.index')->with('success', __('Package deleted successfully'));
    }

    /**
     * Push a package's profile to its router and return a human-readable status,
     * or null when there is no actionable status (router offline/unavailable).
     */
    protected function pushPackageToRouter(Package $package): ?string
    {
        $router = $package->router;

        if (!$router) {
            return null;
        }

        $result = app(MikroTikService::class)->syncPackageProfiles($router);

        if (!empty($result['success'])) {
            return $result['message'] ?? 'Router profiles synced.';
        }

        return 'Router sync warning: ' . ($result['message'] ?? 'could not push profile to router');
    }

    /**
     * Resolve a flexible duration_value + duration_unit input into validity_minutes.
     * Supports anywhere from 1 minute up to 90 days.
     */
    protected function resolveValidityFromDuration(Request $request): array
    {
        if (!$request->filled('duration_value') || !$request->filled('duration_unit')) {
            return [];
        }

        $value = (int) $request->input('duration_value');
        $unit = $request->input('duration_unit');

        $minutes = match ($unit) {
            'minutes' => $value,
            'hours' => $value * 60,
            'days' => $value * 60 * 24,
            default => null,
        };

        if ($minutes === null || $minutes < 1 || $minutes > 90 * 24 * 60) {
            return [];
        }

        return [
            'validity_minutes' => $minutes,
            'idle_timeout' => $minutes,
        ];
    }

    /**
     * Normalize package fields before saving:
     * - Cast bandwidth to integers (RouterOS rejects decimals like 4.00M)
     * - Normalize data_cap to uppercase format (e.g., "500mb" → "500MB", "2 gb" → "2GB")
     */
    protected function normalizePackageFields(array $validated): array
    {
        if (isset($validated['bandwidth_upload']) && $validated['bandwidth_upload'] !== null && $validated['bandwidth_upload'] !== '') {
            $validated['bandwidth_upload'] = (int) $validated['bandwidth_upload'];
        }

        if (isset($validated['bandwidth_download']) && $validated['bandwidth_download'] !== null && $validated['bandwidth_download'] !== '') {
            $validated['bandwidth_download'] = (int) $validated['bandwidth_download'];
        }

        if (!empty($validated['data_cap'])) {
            $validated['data_cap'] = preg_replace_callback(
                '/^(\d+)\s*(MB|GB)$/i',
                fn($m) => $m[1] . strtoupper($m[2]),
                trim($validated['data_cap'])
            );
        }

        return $validated;
    }

    /**
     * Show form to copy selected packages to a router
     */
    public function copyToRouterForm(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $routers = Router::orderBy('name')->get();
        $packages = Package::with('router')->orderBy('name')->get();
        $selectedRouterId = $request->query('router_id');

        return view('packages.copy-to-router', compact('routers', 'packages', 'selectedRouterId'));
    }

    /**
     * Copy selected packages to a destination router
     */
    public function copyToRouter(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'package_ids' => 'required|array|min:1',
            'package_ids.*' => 'exists:packages,id',
            'dest_router_id' => 'required|exists:routers,id',
            'overwrite' => 'nullable|boolean',
        ]);

        $destRouter = Router::findOrFail($validated['dest_router_id']);
        $overwrite = (bool)($validated['overwrite'] ?? false);
        $packages = Package::whereIn('id', $validated['package_ids'])->get();

        $created = 0; $updated = 0; $skipped = 0;

        foreach ($packages as $pkg) {
            // Skip if package already belongs to destination router
            if ((int)$pkg->router_id === (int)$destRouter->id) {
                $skipped++;
                continue;
            }

            $existing = Package::where('router_id', $destRouter->id)->where('name', $pkg->name)->first();
            if ($existing) {
                if ($overwrite) {
                    $existing->update([
                        'price' => $pkg->price,
                        'bandwidth_upload' => $pkg->bandwidth_upload,
                        'bandwidth_download' => $pkg->bandwidth_download,
                        'session_timeout' => $pkg->session_timeout,
                        'idle_timeout' => $pkg->idle_timeout,
                        'shared_users' => $pkg->shared_users,
                        'data_cap' => $pkg->data_cap,
                        'validity_minutes' => $pkg->validity_minutes,
                        'validity_days' => $pkg->validity_days,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Package::create([
                    'name' => $pkg->name,
                    'router_id' => $destRouter->id,
                    'price' => $pkg->price,
                    'bandwidth_upload' => $pkg->bandwidth_upload,
                    'bandwidth_download' => $pkg->bandwidth_download,
                    'session_timeout' => $pkg->session_timeout,
                    'idle_timeout' => $pkg->idle_timeout,
                    'shared_users' => $pkg->shared_users,
                    'data_cap' => $pkg->data_cap,
                    'validity_minutes' => $pkg->validity_minutes,
                    'validity_days' => $pkg->validity_days,
                ]);
                $created++;
            }
        }

        $msg = sprintf('Copy to %s: %d created, %d updated, %d skipped', $destRouter->name, $created, $updated, $skipped);
        return redirect()->route('packages.index')->with('success', $msg);
    }

}
