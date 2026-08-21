<?php

namespace App\Services;

use App\Models\CaptivePortalSession;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptivePortalService
{
    public function __construct(
        private RouterIdentificationService $routerIdentificationService
    ) {}

    /**
     * Create or update a captive portal session from MikroTik parameters
     */
    public function createSession(Request $request, ?Router $router = null): CaptivePortalSession
    {
        if (!$router) {
            $router = $this->routerIdentificationService->resolveRouter($request);
        }

        $clientMac = $request->input('mac');
        $clientIp = $request->input('ip') ?? $request->ip();

        $session = $this->findExistingSession($router, $clientMac, $clientIp);

        $data = [
            'router_id' => $router?->id,
            'client_mac' => $clientMac,
            'client_ip' => $clientIp,
            'link_login' => $request->input('link-login'),
            'link_orig' => $request->input('link-orig'),
            'chap_id' => $request->input('chap-id'),
            'chap_challenge' => $request->input('chap-challenge'),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'metadata' => array_merge($session?->metadata ?? [], [
                'user_agent' => $request->userAgent(),
                'accept_language' => $request->header('Accept-Language'),
            ]),
        ];

        if ($session) {
            $session->update($data);
            return $session;
        }

        return CaptivePortalSession::create(array_merge($data, [
            'session_token' => $this->generateSessionToken(),
        ]));
    }

    private function findExistingSession(?Router $router, ?string $clientMac, ?string $clientIp): ?CaptivePortalSession
    {
        $query = CaptivePortalSession::query()
            ->where('status', 'pending')
            ->where('expires_at', '>', now());

        if ($router) {
            $query->where('router_id', $router->id);
        }

        if ($clientMac) {
            $query->where('client_mac', $clientMac);
        } elseif ($clientIp) {
            $query->where('client_ip', $clientIp);
        }

        return $query->latest()->first();
    }

    /**
     * Get existing session by token
     */
    public function getSessionByToken(string $token): ?CaptivePortalSession
    {
        return CaptivePortalSession::where('session_token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Update session with package selection
     */
    public function selectPackage(CaptivePortalSession $session, int $packageId): void
    {
        $session->update([
            'package_id' => $packageId,
        ]);
    }

    /**
     * Link session to authorization
     */
    public function linkAuthorization(CaptivePortalSession $session, int $authorizationId): void
    {
        $session->update([
            'authorization_id' => $authorizationId,
            'status' => 'authenticated',
        ]);
    }

    /**
     * Link the captive portal session to the hotspot session that will be used
     * for the MikroTik handoff.
     */
    public function linkSession(CaptivePortalSession $portalSession, \App\Models\HotspotSession $hotspotSession): void
    {
        $portalSession->update([
            'hotspot_session_id' => $hotspotSession->id,
            'status' => 'authenticated',
        ]);
    }

    /**
     * Mark session as failed
     */
    public function markFailed(CaptivePortalSession $session, string $reason): void
    {
        $session->update([
            'status' => 'failed',
            'metadata' => array_merge($session->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        return CaptivePortalSession::where('expires_at', '<', now())
            ->orWhere('status', 'expired')
            ->orWhere('status', 'failed')
            ->delete();
    }

    /**
     * Generate unique session token
     */
    private function generateSessionToken(): string
    {
        do {
            $token = Str::random(64);
        } while (CaptivePortalSession::where('session_token', $token)->exists());

        return $token;
    }
}
