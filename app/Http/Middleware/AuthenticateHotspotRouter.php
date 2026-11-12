<?php

namespace App\Http\Middleware;

use App\Models\Router;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate captive-portal API calls with the per-router hotspot API token.
 * The token is embedded in the hotspot HTML files uploaded to each router, so
 * only devices behind that router's hotspot can obtain it.
 */
class AuthenticateHotspotRouter
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Router-Token')
            ?? $request->input('apiToken')
            ?? $request->input('api_token');

        $router = $token ? Router::where('hotspot_api_token', $token)->first() : null;

        if (!$router) {
            return response()->json([
                'type' => 'error',
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $request->attributes->set('router', $router);

        return $next($request);
    }
}
