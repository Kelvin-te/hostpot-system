<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RouterOS\Query;
use RouterOS\Client;

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
            'validity_days' => 'nullable|integer|min:1'
        ]);

        $router = Router::where("id", $request->router_id)->firstOrFail();

        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            // Create hotspot user profile
            $query = new Query("/ip/hotspot/user/profile/add");
            $query->equal("name", $request->name);
            
            // Set rate limit based on bandwidth settings
            if ($request->bandwidth_upload && $request->bandwidth_download) {
                $rateLimit = ($request->bandwidth_upload * 1000000) . "/" . ($request->bandwidth_download * 1000000);
                $query->equal("rate-limit", $rateLimit);
            } elseif ($request->rate_limit) {
                $query->equal("rate-limit", $request->rate_limit);
            }
            
            // Set session timeout (convert hours to seconds)
            if ($request->session_timeout) {
                $query->equal("session-timeout", $request->session_timeout * 3600);
            }
            
            // Set idle timeout (convert minutes to seconds)
            if ($request->idle_timeout) {
                $query->equal("idle-timeout", $request->idle_timeout * 60);
            }
            
            // Set shared users
            if ($request->shared_users) {
                $query->equal("shared-users", $request->shared_users);
            }
            
            $client->query($query)->read();
            
        } catch (\Exception $e) {
            return back()->with("error", __("Mikrotik connection failed: ") . $e->getMessage());
        }
        
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
            'validity_days' => 'nullable|integer|min:1'
        ]);

        // Update the package in the database
        $package->fill($validated);
        $package->save();

        // Update the hotspot profile on the router
        try {
            $router = $package->router;
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            // Update hotspot user profile
            $query = new Query("/ip/hotspot/user/profile/set");
            $query->equal("name", $package->name);
            
            // Set rate limit based on bandwidth settings
            if ($request->bandwidth_upload && $request->bandwidth_download) {
                $rateLimit = ($request->bandwidth_upload * 1000000) . "/" . ($request->bandwidth_download * 1000000);
                $query->equal("rate-limit", $rateLimit);
            } elseif ($request->rate_limit) {
                $query->equal("rate-limit", $request->rate_limit);
            }
            
            // Set session timeout (convert hours to seconds)
            if ($request->session_timeout) {
                $query->equal("session-timeout", $request->session_timeout * 3600);
            }
            
            // Set idle timeout (convert minutes to seconds)
            if ($request->idle_timeout) {
                $query->equal("idle-timeout", $request->idle_timeout * 60);
            }
            
            // Set shared users
            if ($request->shared_users) {
                $query->equal("shared-users", $request->shared_users);
            }
            
            $client->query($query)->read();
            
        } catch (\Exception $e) {
            // Log the error but don't fail the update
            \Log::warning("Failed to update hotspot profile on router: " . $e->getMessage());
        }

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

        // Try to remove corresponding MikroTik profile on the router
        try {
            $router = $package->router;
            if ($router) {
                $client = new Client([
                    'host' => $router->ip,
                    'user' => $router->username,
                    'pass' => $router->password,
                ]);

                // Find profile by name
                $printQuery = (new Query('/ip/hotspot/user/profile/print'))
                    ->where('name', $package->name);
                $profiles = $client->query($printQuery)->read();

                if (!empty($profiles) && isset($profiles[0]['.id'])) {
                    $removeQuery = (new Query('/ip/hotspot/user/profile/remove'))
                        ->equal('.id', $profiles[0]['.id']);
                    $client->query($removeQuery)->read();
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to remove MikroTik profile when deleting package: ' . $e->getMessage());
        }

        // Delete package (hotspot_sessions have FK with cascade delete)
        $package->delete();

        return redirect()->route('packages.index')->with('success', __('Package deleted successfully'));
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

            // Prepare MikroTik client for destination router
            $client = null;
            try {
                $client = new Client([
                    'host' => $destRouter->ip,
                    'user' => $destRouter->username,
                    'pass' => $destRouter->password,
                ]);
            } catch (\Exception $e) {
                $errors[] = __('Mikrotik connection failed: ') . $e->getMessage();
            }

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
                        $existing->validity_days = $pkg->validity_days;
                        $existing->validity_hours = $pkg->validity_hours;
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
                        'validity_days' => $pkg->validity_days,
                        'validity_hours' => $pkg->validity_hours,
                    ]);
                    $created++;
                }

                // Create or update MikroTik user profile on destination router
                if ($client) {
                    try {
                        // Determine rate-limit
                        $rateLimit = null;
                        if ($pkg->bandwidth_upload && $pkg->bandwidth_download) {
                            $rateLimit = ($pkg->bandwidth_upload * 1000000) . "/" . ($pkg->bandwidth_download * 1000000);
                        } elseif ($pkg->rate_limit) {
                            $rateLimit = $pkg->rate_limit;
                        }

                        // Check if profile exists
                        $printQuery = (new Query('/ip/hotspot/user/profile/print'))
                            ->where('name', $pkg->name);
                        $profiles = $client->query($printQuery)->read();

                        if (!empty($profiles)) {
                            // Update existing profile
                            $profileId = $profiles[0]['.id'] ?? null;
                            if ($profileId) {
                                $setQuery = new Query('/ip/hotspot/user/profile/set');
                                $setQuery->equal('.id', $profileId);
                                if ($rateLimit) { $setQuery->equal('rate-limit', $rateLimit); }
                                if ($pkg->session_timeout) { $setQuery->equal('session-timeout', $pkg->session_timeout * 3600); }
                                if ($pkg->idle_timeout) { $setQuery->equal('idle-timeout', $pkg->idle_timeout * 60); }
                                if ($pkg->shared_users) { $setQuery->equal('shared-users', $pkg->shared_users); }
                                $client->query($setQuery)->read();
                            }
                        } else {
                            // Create new profile
                            $addQuery = new Query('/ip/hotspot/user/profile/add');
                            $addQuery->equal('name', $pkg->name);
                            if ($rateLimit) { $addQuery->equal('rate-limit', $rateLimit); }
                            if ($pkg->session_timeout) { $addQuery->equal('session-timeout', $pkg->session_timeout * 3600); }
                            if ($pkg->idle_timeout) { $addQuery->equal('idle-timeout', $pkg->idle_timeout * 60); }
                            if ($pkg->shared_users) { $addQuery->equal('shared-users', $pkg->shared_users); }
                            $client->query($addQuery)->read();
                        }
                    } catch (\Exception $e) {
                        $errors[] = __('Failed to sync MikroTik profile for package ":pkg" on router ":rtr": ', ['pkg' => $pkg->name, 'rtr' => $destRouter->name]) . $e->getMessage();
                    }
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
