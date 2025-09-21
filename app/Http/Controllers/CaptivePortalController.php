<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaptivePortalController extends Controller
{
    /**
     * Display the captive portal page with packages
     */
    public function index(Request $request)
    {
        // Try to detect router by various methods
        $router = $this->detectRouter($request);
        
        if (!$router) {
            // Fallback: show all packages if router can't be detected
            $packages = Package::with('router')->orderBy('price')->get();
            $routerName = 'Available Packages';
        } else {
            // Show packages for the detected router
            $packages = Package::where('router_id', $router->id)->orderBy('price')->get();
            $routerName = $router->name;
        }

        return view('captive-portal.index', compact('packages', 'routerName', 'router'));
    }

    /**
     * Show package details
     */
    public function package(Request $request, $packageId)
    {
        $package = Package::with('router')->findOrFail($packageId);
        $router = $this->detectRouter($request);
        
        return view('captive-portal.package', compact('package', 'router'));
    }

    /**
     * Handle package purchase/selection
     */
    public function purchase(Request $request, $packageId)
    {
        $package = Package::with('router')->findOrFail($packageId);
        $router = $this->detectRouter($request);
        
        // Here you would integrate with payment gateway or voucher system
        // For now, we'll just show a purchase confirmation
        
        return view('captive-portal.purchase', compact('package', 'router'));
    }

    /**
     * Detect which router the user is connected through
     */
    private function detectRouter(Request $request)
    {
        // Method 1: Check if router_id is passed as parameter
        if ($request->has('router_id')) {
            $router = Router::find($request->router_id);
            if ($router) {
                return $router;
            }
        }

        // Method 2: Check if router IP is passed as parameter (common in MikroTik)
        if ($request->has('router_ip')) {
            $router = Router::where('ip', $request->router_ip)->first();
            if ($router) {
                return $router;
            }
        }

        // Method 3: Try to detect by HTTP_HOST or SERVER_NAME
        $serverName = $request->server('HTTP_HOST') ?? $request->server('SERVER_NAME');
        if ($serverName) {
            $router = Router::where('ip', $serverName)->first();
            if ($router) {
                return $router;
            }
        }

        // Method 4: Check X-Forwarded-For or other headers that might contain router IP
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                $router = Router::where('ip', $ip)->first();
                if ($router) {
                    return $router;
                }
            }
        }

        // Method 5: Check client IP ranges (if routers use specific IP ranges)
        $clientIp = $request->ip();
        if ($clientIp) {
            // Try to match router by IP subnet (basic implementation)
            $routers = Router::all();
            foreach ($routers as $router) {
                // Simple check if client IP starts with router IP subnet
                $routerSubnet = substr($router->ip, 0, strrpos($router->ip, '.'));
                $clientSubnet = substr($clientIp, 0, strrpos($clientIp, '.'));
                
                if ($routerSubnet === $clientSubnet) {
                    return $router;
                }
            }
        }

        // Log the detection attempt for debugging
        Log::info('Router detection failed', [
            'router_id' => $request->get('router_id'),
            'router_ip' => $request->get('router_ip'),
            'http_host' => $request->server('HTTP_HOST'),
            'server_name' => $request->server('SERVER_NAME'),
            'client_ip' => $request->ip(),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
        ]);

        return null;
    }

    /**
     * API endpoint to get packages for a specific router
     */
    public function apiPackages(Request $request, $routerId = null)
    {
        if ($routerId) {
            $packages = Package::where('router_id', $routerId)->orderBy('price')->get();
        } else {
            $router = $this->detectRouter($request);
            if ($router) {
                $packages = Package::where('router_id', $router->id)->orderBy('price')->get();
            } else {
                $packages = Package::with('router')->orderBy('price')->get();
            }
        }

        return response()->json([
            'success' => true,
            'packages' => $packages,
            'router_detected' => $router ?? null
        ]);
    }
}
