<?php

namespace App\Services;

use App\Models\Router;
use Illuminate\Http\Request;

class RouterIdentificationService
{
    /**
     * Validate and resolve router identifier from request or session
     */
    public function resolveRouter(Request $request): ?Router
    {
        $identifier = $request->query('router') ?? $request->input('router');

        if (!$identifier) {
            $identifier = session('captive_portal_router_identifier');
        }

        if (!$identifier) {
            return null;
        }

        $router = Router::where('identifier', $identifier)->first();

        if (!$router) {
            return null;
        }

        if (!$router->is_active) {
            return null;
        }

        session(['captive_portal_router_identifier' => $identifier]);

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
