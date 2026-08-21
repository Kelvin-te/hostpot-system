<?php

namespace App\Http\Middleware;

use App\Models\TenantCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantCredential
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');

        if (empty($authorization) || ! str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $plainTextToken = substr($authorization, 7);

        if (! str_contains($plainTextToken, ':')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        [$clientId, $secret] = explode(':', $plainTextToken, 2);

        if (empty($clientId) || empty($secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $credential = TenantCredential::where('client_id', $clientId)->first();

        if (! $credential || ! $credential->verifyToken($secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $credential->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $credential->update(['last_used_at' => now()]);

        $request->attributes->set('tenant', $credential->tenant);
        $request->attributes->set('credential', $credential);

        return $next($request);
    }
}
