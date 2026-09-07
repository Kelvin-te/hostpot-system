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
            $identifier = session('hotspot_router_identifier');
            $source = $identifier ? 'session' : null;
        }

        if (!$identifier) {
            Log::info('HOTSPOT_FLOW_TRACE', [
                'stage' => 'RouterIdentificationService::resolveRouter:no_identifier',
                'path' => $request->path(),
                'query_params' => $request->query(),
            ]);

            return null;
        }

        $router = Router::where('identifier', $identifier)->first();

        if (!$router) {
            Log::info('HOTSPOT_FLOW_TRACE', [
                'stage' => 'RouterIdentificationService::resolveRouter:identifier_not_found',
                'path' => $request->path(),
                'identifier_source' => $source,
            ]);

            return null;
        }

        if (!$router->is_active) {
            return null;
        }

        // If the fresh query-param identifier differs from the session-stored
        // one, the device has reconnected through a different router (or
        // reconnected after disconnect). Discard the old hotspot session token
        // so a stale session is never reused.
        $sessionIdentifier = session('hotspot_router_identifier');
        if ($sessionIdentifier && $sessionIdentifier !== $identifier) {
            Log::info('HOTSPOT_FLOW_TRACE', [
                'stage' => 'RouterIdentificationService::resolveRouter:identifier_changed_clearing_stale_session',
                'path' => $request->path(),
                'old_identifier' => $sessionIdentifier,
                'new_identifier' => $identifier,
            ]);

            session()->forget('hotspot_session_token');
        }

        session(['hotspot_router_identifier' => $identifier]);

        Log::info('HOTSPOT_FLOW_TRACE', [
            'stage' => 'RouterIdentificationService::resolveRouter:resolved',
            'path' => $request->path(),
            'router_id' => $router->id,
            'identifier_source' => $source,
        ]);

        return $router;
    }

    /**
     * Clear all router identification and hotspot session state.
     * Called on disconnect so the next connection starts fresh.
     */
    public function clearSessionState(): void
    {
        session()->forget([
            'hotspot_router_identifier',
            'hotspot_session_token',
        ]);

        Log::info('HOTSPOT_FLOW_TRACE', [
            'stage' => 'RouterIdentificationService::clearSessionState',
        ]);
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
