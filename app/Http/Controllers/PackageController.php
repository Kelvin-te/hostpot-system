<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use Illuminate\Http\Request;
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
            'name' => 'required|string|max:255|unique:packages',
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
}
