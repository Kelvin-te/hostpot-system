<?php

namespace App\Services;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouterIdentificationService
{
    /**
     * Validate and resolve router identifier from request or session
     */
    public function resolveRouter(Request $request): ?Router
    {
        $identifier = $request->query('router') ?? $request->input('router');
        $source = $identifier ? 'request' : null;

        if (!$identifier) {
            $identifier = session('captive_portal_router_identifier');
            $source = $identifier ? 'session' : null;
        }

        if (!$identifier) {
            Log::info('CAPTIVE_FLOW_TRACE', [
                'stage' => 'RouterIdentificationService::resolveRouter:no_identifier',
                'path' => $request->path(),
            ]);

            return null;
        }

        $router = Router::where('identifier', $identifier)->first();

        if (!$router) {
            Log::info('CAPTIVE_FLOW_TRACE', [
                'stage' => 'RouterIdentificationService::resolveRouter:identifier_not_found',
                'path' => $request->path(),
                'identifier_source' => $source,
            ]);

            return null;
        }

        if (!$router->is_active) {
            return null;
        }

        session(['captive_portal_router_identifier' => $identifier]);

        Log::info('CAPTIVE_FLOW_TRACE', [
            'stage' => 'RouterIdentificationService::resolveRouter:resolved',
            'path' => $request->path(),
            'router_id' => $router->id,
            'identifier_source' => $source,
        ]);

        return $router;
    }

    /**
     * Validate router identifier
     */
    public function validateIdentifier(string $identifier): bool
    {
        return Router::where('identifier', $identifier)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Generate unique router identifier
     */
    public static function generateIdentifier(): string
    {
        do {
            $identifier = strtolower(substr(str_replace('-', '', bin2hex(random_bytes(8))), 0, 12));
        } while (Router::where('identifier', $identifier)->exists());

        return $identifier;
    }
}
