<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
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
            'session_timeout' => 'nullable|integer|min:0',
            'idle_timeout' => 'nullable|integer|min:0',
            'shared_users' => 'nullable|integer|min:1',
            'rate_limit' => 'nullable|string|max:50',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days',
        ]);

        $validated = array_merge($validated, $this->resolveValidityFromDuration($request));

        $router = Router::where("id", $request->router_id)->firstOrFail();

        $package = new Package();
        $package->fill($validated);
        $package->save();

        return redirect('packages')->with('success', __('Hotspot package successfully created'));
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
            'session_timeout' => 'nullable|integer|min:0',
            'idle_timeout' => 'nullable|integer|min:0',
            'shared_users' => 'nullable|integer|min:1',
            'rate_limit' => 'nullable|string|max:50',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days',
        ]);

        $validated = array_merge($validated, $this->resolveValidityFromDuration($request));

        // Update the package in the database
        $package->fill($validated);
        $package->save();

        return redirect('packages')->with('success', __('Hotspot package successfully updated'));
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy(Package $package)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        // Delete package (hotspot_sessions have FK with cascade delete)
        $package->delete();

        return redirect()->route('packages.index')->with('success', __('Package deleted successfully'));
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
        ];
    }

    /**
     * Show form to clone packages between routers
     */
    public function cloneForm(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $routers = Router::orderBy('name')->get();
        $sourceRouterId = $request->query('source_router_id');

        return view('packages.clone', compact('routers', 'sourceRouterId'));
    }

    /**
     * Clone packages from one router to another or across all routers
     */
    public function clone(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'source_router_id' => 'required|exists:routers,id',
            'clone_all' => 'nullable|boolean',
            'dest_router_id' => 'nullable|exists:routers,id',
            'overwrite' => 'nullable|boolean',
        ]);

        $sourceRouter = Router::findOrFail($validated['source_router_id']);
        $overwrite = (bool)($validated['overwrite'] ?? false);

        // Determine destination routers
        $destRouters = collect();
        if (!empty($validated['clone_all']) && $validated['clone_all']) {
            $destRouters = Router::where('id', '!=', $sourceRouter->id)->get();
        } else {
            if (empty($validated['dest_router_id'])) {
                return back()->with('error', __('Please select destination router or choose clone to all'));
            }
            if ((int)$validated['dest_router_id'] === (int)$sourceRouter->id) {
                return back()->with('error', __('Destination router cannot be the same as source router'));
            }
            $destRouters = Router::where('id', $validated['dest_router_id'])->get();
        }

        if ($destRouters->count() === 0) {
            return back()->with('error', __('No destination routers found'));
        }

        $sourcePackages = Package::where('router_id', $sourceRouter->id)->orderBy('name')->get();
        if ($sourcePackages->count() === 0) {
            return back()->with('error', __('No packages found on the source router'));
        }

        $summary = [];

        foreach ($destRouters as $destRouter) {
            $created = 0; $updated = 0; $skipped = 0; $errors = [];

            foreach ($sourcePackages as $pkg) {
                // Upsert DB package on destination
                $existing = Package::where('router_id', $destRouter->id)->where('name', $pkg->name)->first();
                if ($existing) {
                    if ($overwrite) {
                        $existing->price = $pkg->price;
                        $existing->bandwidth_upload = $pkg->bandwidth_upload;
                        $existing->bandwidth_download = $pkg->bandwidth_download;
                        $existing->session_timeout = $pkg->session_timeout;
                        $existing->idle_timeout = $pkg->idle_timeout;
                        $existing->shared_users = $pkg->shared_users;
                        $existing->rate_limit = $pkg->rate_limit;
                        $existing->validity_minutes = $pkg->validity_minutes;
                        $existing->save();
                        $updated++;
                    } else {
                        $skipped++;
                        // Still try to ensure profile exists/updated only if overwrite is true
                        continue;
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
                        'rate_limit' => $pkg->rate_limit,
                        'validity_minutes' => $pkg->validity_minutes,
                    ]);
                    $created++;
                }
            }

            $summary[] = [
                'router' => $destRouter->name,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        }

        // Build flash message
        $messages = [];
        foreach ($summary as $s) {
            $messages[] = $s['router'] . ': ' . __('created') . ' ' . $s['created'] . ', ' . __('updated') . ' ' . $s['updated'] . ', ' . __('skipped') . ' ' . $s['skipped'] . (count($s['errors']) ? ' (' . implode('; ', $s['errors']) . ')' : '');
        }

        return redirect()->route('packages.index')->with('success', __('Package cloning completed: ') . implode(' | ', $messages));
    }
}
