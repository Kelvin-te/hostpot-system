<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouterRequest;
use App\Http\Requests\UpdateRouterRequest;
use Illuminate\Http\Request;
use App\Models\Router;
use App\Services\MikroTikService;

class RouterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->isAdmin()) {
            redirect('/');
        }
        
        $routers = Router::orderBy("name","asc")->get();
        return view("router.index", compact("routers"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        return view('router.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:routers',
            'location' => 'required',
            'ip'=> 'required|ip',
            'username'=> 'required',
            'password'=> 'required',
            'api_port'=> 'nullable|integer|min:1|max:65535',
        ]);

        // Validate router connection before saving
        $validationResult = $this->validateRouterConnection($request);
        if (!$validationResult['success']) {
            return back()->with('error', $validationResult['message'])->withInput();
        }

        $router = new Router();
        $router->fill($validated);
        $router->save();

        // Update router with hotspot status
        $mikrotikService = new MikroTikService();
        $hotspotResult = $mikrotikService->testHotspotService($router);
        if ($hotspotResult['success']) {
            $router->hotspot_enabled = $hotspotResult['enabled'];
            $router->hotspot_interface = $hotspotResult['interface'];
            $router->hotspot_server_ip = $hotspotResult['server_ip'];
            $router->save();
        }

        return redirect('router')->with('success', __('Router successfully added'));
    }

    /**
     * Validate router connection before saving
     */
    private function validateRouterConnection(Request $request): array
    {
        try {
            $mikrotikService = new MikroTikService();
            
            // Create temporary router object for testing
            $tempRouter = new Router();
            $tempRouter->ip = $request->ip;
            $tempRouter->username = $request->username;
            $tempRouter->password = $request->password;
            $tempRouter->api_port = $request->api_port ?? 8728;

            // Test connection
            $connectionResult = $mikrotikService->testConnection($tempRouter);
            if (!$connectionResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Cannot connect to router: ' . $connectionResult['message']
                ];
            }

            // Test hotspot service
            $hotspotResult = $mikrotikService->testHotspotService($tempRouter);
            if (!$hotspotResult['enabled']) {
                return [
                    'success' => false,
                    'message' => 'Hotspot service is not enabled on this router'
                ];
            }

            return [
                'success' => true,
                'message' => 'Router validation successful'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        
        // Initialize data arrays
        $connectionStatus = null;
        $systemInfo = null;
        $interfaces = null;
        
        try {
            $mikrotikService = new MikroTikService();
            
            // Test connection and get status
            $connectionResult = $mikrotikService->testConnection($router);
            $connectionStatus = [
                'success' => $connectionResult['success'],
                'message' => $connectionResult['message'] ?? 'Unknown status',
                'diagnostics' => $connectionResult['diagnostics'] ?? null
            ];
            
            // If connection is successful, get system info and interfaces
            if ($connectionResult['success']) {
                // Get system information
                $systemResult = $mikrotikService->getSystemInfo($router);
                if ($systemResult['success']) {
                    $systemInfo = $systemResult['data'];
                }
                
                // Get interface information
                $interfaceResult = $mikrotikService->getInterfaces($router);
                if ($interfaceResult['success']) {
                    $interfaces = $interfaceResult['data'];
                }
            }
            
        } catch (\Exception $e) {
            $connectionStatus = [
                'success' => false,
                'message' => 'Error loading router data: ' . $e->getMessage(),
                'diagnostics' => null
            ];
        }
        
        return view('router.show', compact('router', 'connectionStatus', 'systemInfo', 'interfaces'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        return view('router.edit', compact('router'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'location'=> 'nullable|string',
            'ip'=> 'required|ip',
            'username'=> 'required',
            'password'=> 'required',
            'api_port'=> 'nullable|integer|min:1|max:65535',
        ]);

        $router->location = $validated['location'] ? $request->location : $router->location;
        $router->ip = $validated['ip'] ? $request->ip : $router->ip;
        $router->username = $validated['username'] ? $request->username : $router->username;
        $router->password = $validated['password'] ? $request->password : $router->password;
        $router->api_port = $validated['api_port'] ?? $router->api_port;
        $router->save();

        return redirect('router')->with('success', __('Router updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        
        $router->delete();
        return redirect('router')->with('success', __('Router deleted successfully'));
    }

    /**
     * Test connection to a specific router
     */
    public function testConnection(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->testConnection($router);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get system information from router
     */
    public function getSystemInfo(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->getSystemInfo($router);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system info: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get interface information from router
     */
    public function getInterfaces(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->getInterfaces($router);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get interfaces: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reboot the router
     */
    public function reboot(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->rebootRouter($router);
            
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'Router reboot initiated successfully']);
            } else {
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed to reboot router']);
            }
        } catch (\Exception $e) {
            \Log::error('Router reboot failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Reboot failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Backup the router configuration
     */
    public function backup(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->backupRouter($router);
            
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'Router backup created successfully', 'backup_file' => $result['backup_file'] ?? null]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed to backup router']);
            }
        } catch (\Exception $e) {
            \Log::error('Router backup failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get router configuration
     */
    public function getConfig(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->getRouterConfig($router);
            
            if ($result['success']) {
                return response()->json(['success' => true, 'config' => $result['config'] ?? null]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed to get router config']);
            }
        } catch (\Exception $e) {
            \Log::error('Get router config failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to get config: ' . $e->getMessage()]);
        }
    }

    /**
     * Get status for all routers (for dashboard async loading)
     */
    public function getAllStatuses()
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $routers = Router::all();
        $mikrotikService = new MikroTikService();
        $statuses = [];

        foreach ($routers as $router) {
            try {
                $result = $mikrotikService->testConnection($router);
                
                // Calculate sync status
                $totalPackages = \App\Models\Package::where('router_id', $router->id)->count();
                $syncedCount = $router->packages_sync_count ?? 0;
                $unsyncCount = $router->packages_unsync_count ?? 0;
                
                $syncStatus = 'unsynced';
                if ($totalPackages > 0 && $syncedCount === $totalPackages) {
                    $syncStatus = 'synced';
                } elseif ($syncedCount > 0) {
                    $syncStatus = 'partial';
                }
                
                $statuses[$router->id] = [
                    'id' => $router->id,
                    'name' => $router->name,
                    'ip_address' => $router->ip_address ?? $router->ip,
                    'online' => $result['success'],
                    'status' => $result['success'] ? 'online' : 'offline',
                    'message' => $result['message'],
                    'diagnostics' => $result['diagnostics'] ?? null,
                    'data' => $result['data'] ?? null,
                    'sync_status' => $syncStatus,
                    'synced_count' => $syncedCount,
                    'total_packages' => $totalPackages,
                    'last_synced_at' => $router->last_synced_at ? ($router->last_synced_at instanceof \Carbon\Carbon ? $router->last_synced_at->format('Y-m-d H:i:s') : \Carbon\Carbon::parse($router->last_synced_at)->format('Y-m-d H:i:s')) : null,
                ];
            } catch (\Exception $e) {
                $statuses[$router->id] = [
                    'id' => $router->id,
                    'name' => $router->name,
                    'ip_address' => $router->ip_address ?? $router->ip,
                    'online' => false,
                    'status' => 'error',
                    'message' => 'Connection test failed: ' . $e->getMessage(),
                    'diagnostics' => null,
                    'data' => null,
                    'sync_status' => 'unsynced',
                    'synced_count' => 0,
                    'total_packages' => \App\Models\Package::where('router_id', $router->id)->count(),
                    'last_synced_at' => null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'routers' => $statuses
        ]);
    }

    /**
     * Sync all packages to router
     */
    public function syncPackages(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            $packages = \App\Models\Package::where('router_id', $router->id)->get();
            
            $synced = 0;
            $failed = 0;
            
            foreach ($packages as $package) {
                if ($mikrotikService->syncPackageToRouter($package, $router)) {
                    $synced++;
                } else {
                    $failed++;
                }
            }
            
            // Update router sync tracking
            $router->last_synced_at = now();
            $router->packages_sync_count = $synced;
            $router->packages_unsync_count = $failed;
            $router->save();
            
            return response()->json([
                'success' => true,
                'message' => "Synced $synced packages, $failed failed",
                'synced' => $synced,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Apply walled garden settings to router
     */
    public function applyWalledGarden(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikroTikService();
            
            // Get walled garden settings from app settings
            $settings = \App\Models\Setting::first();
            
            if (!$settings || !$settings->walled_garden_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Walled garden is not enabled in settings'
                ]);
            }
            
            $domains = $settings->walled_garden_domains ?? [];
            $ips = $settings->walled_garden_ips ?? [];
            
            // Add predefined domains
            $predefinedDomains = [
                request()->getHost(),
                'wingufi.net',
                'wingufi.co.ke',
                'wingufi.com',
                '*.sterkedigital.com',
                '*.vintextechnologies.com',
            ];
            
            $allDomains = array_merge($predefinedDomains, $domains);
            
            if ($mikrotikService->applyWalledGarden($router, $allDomains, $ips)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Walled garden applied successfully',
                    'domains_count' => count($allDomains),
                    'ips_count' => count($ips),
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply walled garden'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error applying walled garden: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get comprehensive router diagnostics
     */
    public function getDiagnostics(Router $router)
    {
        try {
            $mikrotikService = new MikroTikService();
            $diagnostics = $mikrotikService->getRouterDiagnostics($router);
            
            return response()->json([
                'success' => true,
                'diagnostics' => $diagnostics
            ]);
        } catch (\Exception $e) {
            \Log::error('Get diagnostics failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get diagnostics: ' . $e->getMessage()
            ]);
        }
    }
}
