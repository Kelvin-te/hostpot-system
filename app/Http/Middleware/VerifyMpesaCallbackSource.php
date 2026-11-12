<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict M-Pesa (Daraja) callbacks to Safaricom source IPs.
 *
 * Daraja does not sign callbacks, so the source IP allowlist is the primary
 * authenticity check. Configure via MPESA_CALLBACK_IPS ("*" for local dev).
 */
class VerifyMpesaCallbackSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('mpesa.callback_allowed_ips', []);

        if ($allowed === ['*']) {
            return $next($request);
        }

        if (empty($allowed) || !in_array($request->ip(), $allowed, true)) {
            \Illuminate\Support\Facades\Log::warning('M-Pesa callback rejected: source IP not allowed', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
