<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouterRequest;
use App\Http\Requests\UpdateRouterRequest;
use Illuminate\Http\Request;
use App\Models\Router;
use App\Services\HotspotFileGeneratorService;
use App\Services\MikroTikService;
use App\Services\RouterIdentificationService;

class RouterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $routers = Router::orderBy("name","asc")->get();
        return view("router.index", compact("routers"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

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
        $router->identifier = RouterIdentificationService::generateIdentifier();
        $router->fill($validated);
        $router->save();

        // Update router with hotspot status
        $mikrotikService = app(MikroTikService::class);
        $hotspotResult = $mikrotikService->testHotspotService($router);
        if ($hotspotResult['success']) {
            $router->hotspot_enabled = $hotspotResult['enabled'];
            $router->hotspot_interface = $hotspotResult['interface'];
            $router->hotspot_server_ip = $hotspotResult['server_ip'];
            $router->save();
        }
        $hotspotWarning = $hotspotResult['server_ip_warning'] ?? null;

        if ($hotspotWarning) {
            return redirect('router')->with('warning', __('Router successfully added, but: ') . __($hotspotWarning));
        }

        return redirect('router')->with('success', __('Router successfully added'));
    }

    /**
     * Validate router connection before saving
     */
    private function validateRouterConnection(Request $request): array
    {
        try {
            $mikrotikService = app(MikroTikService::class);
            
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
        
        // Initialize data arrays
        $connectionStatus = null;
        $systemInfo = null;
        $interfaces = null;
        
        try {
            $mikrotikService = app(MikroTikService::class);
            
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

        $ipChanged = $validated['ip'] !== $router->ip;

        $router->location = $validated['location'] ? $request->location : $router->location;
        $router->ip = $validated['ip'] ? $request->ip : $router->ip;
        $router->username = $validated['username'] ? $request->username : $router->username;
        $router->password = $validated['password'] ? $request->password : $router->password;
        $router->api_port = $validated['api_port'] ?? $router->api_port;
        $router->save();

        if (!$ipChanged) {
            return redirect('router')->with('success', __('Router updated successfully'));
        }

        return redirect('router')->with('success', __('Router updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Router $router)
    {
        
        $router->delete();
        return redirect('router')->with('success', __('Router deleted successfully'));
    }

    /**
     * Test connection to a specific router
     */
    public function testConnection(Router $router)
    {

        try {
            $mikrotikService = app(MikroTikService::class);
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

        try {
            $mikrotikService = app(MikroTikService::class);
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

        try {
            $mikrotikService = app(MikroTikService::class);
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

        try {
            $mikrotikService = app(MikroTikService::class);
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

        try {
            $mikrotikService = app(MikroTikService::class);
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

        try {
            $mikrotikService = app(MikroTikService::class);
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

        $routers = Router::all();
        $mikrotikService = app(MikroTikService::class);
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
     * Configure the router's hotspot profile to serve local hotspot files
     * and add the Laravel app + Google Fonts to the walled garden.
     */
    public function configurePortal(Router $router)
    {

        try {
            $mikrotikService = app(MikroTikService::class);
            $apiBaseUrl = rtrim(config('app.url'), '/');
            $result = $mikrotikService->configureLocalPortal($router, $apiBaseUrl);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Portal configuration failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Portal configuration failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * One-click setup of the HotSpot server, user profile, and local portal
     * configuration on the router.
     */
    public function setupHotspot(Router $router)
    {

        try {
            $mikrotikService = app(MikroTikService::class);
            $result = $mikrotikService->setupHotspot($router);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Hotspot setup failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Hotspot setup failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync all active package profiles for this router to RouterOS.
     * Creates/updates hotspot user profiles for each active package so
     * session creation never fails because a profile is missing.
     */
    public function syncPackageProfiles(Router $router)
    {

        $mikrotikService = app(MikroTikService::class);
        $result = $mikrotikService->syncPackageProfiles($router);

        return response()->json($result);
    }

    /**
     * Re-detect the router's HotSpot gateway/server IP, interface, and
     * enabled state directly from RouterOS (hotspot-address on the active
     * hotspot profile, falling back to the hotspot interface's own IP).
     * Does NOT touch the router's management IP (routers.ip/ip_address).
     * Use this to backfill hotspot_server_ip for routers provisioned before
     * this detection existed, or after re-configuring the hotspot profile.
     */
    public function syncHotspotInfo(Router $router)
    {

        $mikrotikService = app(MikroTikService::class);
        $hotspotResult = $mikrotikService->testHotspotService($router);

        if (!$hotspotResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $hotspotResult['message'] ?? 'Failed to detect hotspot info',
            ]);
        }

        $router->hotspot_enabled = $hotspotResult['enabled'];
        $router->hotspot_interface = $hotspotResult['interface'];
        $router->hotspot_server_ip = $hotspotResult['server_ip'];
        $router->save();

        return response()->json([
            'success' => true,
            'message' => 'Hotspot info synced successfully',
            'warning' => $hotspotResult['server_ip_warning'] ?? null,
            'hotspot_enabled' => $router->hotspot_enabled,
            'hotspot_interface' => $router->hotspot_interface,
            'hotspot_server_ip' => $router->hotspot_server_ip,
        ]);
    }

    /**
     * Generate and download the customized MikroTik hotspot HTML file set
     * (login/error/logout/status + css) for this router, with the router's
     * identifier, portal URL, and company name pre-filled.
     */
    public function downloadHotspotFiles(Router $router)
    {

        $generator = new HotspotFileGeneratorService();
        $zipPath = $generator->generateZip($router);

        return response()->download(
            $zipPath,
            'hotspot-files-' . $router->identifier . '.zip'
        )->deleteFileAfterSend(true);
    }

    /**
     * Upload hotspot HTML files directly to the router via the RouterOS API.
     */
    public function uploadHotspotFiles(Router $router)
    {

        try {
            $service = new MikroTikService();
            $result = $service->uploadHotspotFiles($router);

            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply walled garden settings to router
     */
    public function applyWalledGarden(Router $router)
    {

        try {
            $mikrotikService = app(MikroTikService::class);
            
            // Get walled garden settings from app settings
            $settings = \App\Models\Setting::first();
            
            if (!$settings || !$settings->walled_garden_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Walled garden is not enabled in settings'
                ]);
            }
            
            $domains = is_array($settings->walled_garden_domains)
                ? $settings->walled_garden_domains
                : (json_decode($settings->walled_garden_domains, true) ?: []);
            $ips = is_array($settings->walled_garden_ips)
                ? $settings->walled_garden_ips
                : (json_decode($settings->walled_garden_ips, true) ?: []);
            
            // Add predefined domains
            $predefinedDomains = [
                request()->getHost(),
                parse_url(config('app.url'), PHP_URL_HOST),
                'fonts.googleapis.com',
                'fonts.gstatic.com',
                '*.sterkedigital.com',
                '*.vintextechnologies.com',
                // OS captive portal detection endpoints
                'connectivitycheck.gstatic.com',
                'captive.apple.com',
                'www.msftconnecttest.com',
                'detectportal.firefox.com',
            ];
            
            $allDomains = array_values(array_unique(array_filter(
                array_merge($predefinedDomains, $domains),
                fn($d) => !empty($d)
            )));
            $ips = array_values(array_unique(array_filter($ips, fn($ip) => !empty($ip))));

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
            $mikrotikService = app(MikroTikService::class);
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

    /**
     * Get router setup checklist
     */
    public function getSetupChecklist(Router $router)
    {
        try {
            $mikrotikService = app(MikroTikService::class);
            $checklist = $mikrotikService->getSetupChecklist($router);

            return response()->json([
                'success' => true,
                'checklist' => $checklist,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get setup checklist failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get setup checklist: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get interface traffic stats for a single router
     */
    public function getTrafficStats(Router $router)
    {

        try {
            $mikrotikService = app(MikroTikService::class);
            $result = $mikrotikService->getInterfaceTraffic($router);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get traffic: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get router health metrics for a single router
     */
    public function getHealth(Router $router)
    {

        try {
            $mikrotikService = app(MikroTikService::class);
            $result = $mikrotikService->getRouterHealth($router);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get health: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Batch fetch traffic and health for all routers
     */
    public function getAllTrafficStats()
    {

        $routers = Router::where('is_active', true)->get();
        $mikrotikService = app(MikroTikService::class);
        $data = [];

        foreach ($routers as $router) {
            $traffic = $mikrotikService->getInterfaceTraffic($router);
            $health = $mikrotikService->getRouterHealth($router);

            $primaryInterface = null;
            if ($traffic['success'] && !empty($traffic['interfaces'])) {
                $primaryInterface = collect($traffic['interfaces'])
                    ->firstWhere('name', $router->hotspot_interface ?? 'wlan1')
                    ?? $traffic['interfaces'][0];
            }

            $data[$router->id] = [
                'id' => $router->id,
                'name' => $router->name,
                'online' => $traffic['success'] || $health['success'],
                'rx_rate_mbps' => $primaryInterface['rx_rate_mbps'] ?? null,
                'tx_rate_mbps' => $primaryInterface['tx_rate_mbps'] ?? null,
                'cpu_load' => $health['success'] ? $health['cpu_load'] : null,
                'memory_percentage' => $health['success'] ? $health['memory_percentage'] : null,
                'uptime' => $health['success'] ? $health['uptime'] : null,
                'uptime_seconds' => $health['success'] ? $health['uptime_seconds'] : null,
                'active_sessions' => \App\Models\HotspotSession::active()
                    ->whereHas('package', function ($q) use ($router) {
                        $q->where('router_id', $router->id);
                    })
                    ->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'routers' => $data,
        ]);
    }
}
