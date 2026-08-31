<?php

namespace App\Services;

use App\Models\CaptivePortalSession;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CaptivePortalService
{
    public function __construct(
        private RouterIdentificationService $routerIdentificationService
    ) {}

    /**
     * Laravel session key used to carry an opaque captive portal session
     * token across the landing -> package-selection -> purchase requests.
     * This does NOT contain MAC/IP/credentials, only the random session_token.
     */
    private const SESSION_TOKEN_KEY = 'captive_portal_session_token';

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

        $incomingLinkLogin = $request->input('link-login');
        $incomingHasFreshMikrotikParams = $incomingLinkLogin
            || $request->input('chap-id')
            || $request->input('link-orig');

        $session = $this->resolveExistingSession($request, $router, $clientMac, $clientIp);

        // Discard the old session when:
        // 1. Fresh MikroTik params arrive (device reconnected through login.html)
        // 2. The existing session has incomplete data (no link_login AND no chap_id)
        // In both cases, create a brand-new session with the fresh data.
        if ($session) {
            $sessionIsIncomplete = !$session->link_login && !$session->chap_id;

            if ($incomingHasFreshMikrotikParams || $sessionIsIncomplete) {
                Log::info('CAPTIVE_FLOW_TRACE', [
                    'stage' => 'CaptivePortalService::createSession:discarding_stale_session',
                    'reason' => $incomingHasFreshMikrotikParams
                        ? 'fresh_mikrotik_params_arrived'
                        : 'existing_session_incomplete',
                    'laravel_session_id_hash' => $this->sessionIdHash(),
                    'discarded_session_id' => $session->id,
                    'discarded_has_link_login' => (bool) $session->link_login,
                    'discarded_has_chap_id' => (bool) $session->chap_id,
                ]);

                // Mark the old session as expired so it won't be matched again
                $session->update(['status' => 'expired', 'expires_at' => now()]);
                session()->forget(self::SESSION_TOKEN_KEY);
                $session = null;
            }
        }

        Log::info('CAPTIVE_FLOW_TRACE', [
            'stage' => 'CaptivePortalService::createSession:entry',
            'method' => $request->method(),
            'path' => $request->path(),
            'laravel_session_id_hash' => $this->sessionIdHash(),
            'router_id' => $router?->id,
            'client_mac' => $clientMac,
            'client_ip' => $clientIp,
            'query_params' => $request->query(),
            'existing_captive_portal_session_id' => $session?->id,
            'existing_has_link_login' => (bool) $session?->link_login,
            'existing_has_chap_id' => (bool) $session?->chap_id,
            'existing_has_chap_challenge' => (bool) $session?->chap_challenge,
            'incoming_has_link_login' => (bool) $incomingLinkLogin,
            'incoming_has_link_orig' => (bool) $request->input('link-orig'),
            'incoming_has_chap_id' => (bool) $request->input('chap-id'),
            'incoming_has_chap_challenge' => (bool) $request->input('chap-challenge'),
        ]);

        $data = [
            'router_id' => $router?->id,
            // Preserve previously-captured device identity/MikroTik parameters
            // when this request doesn't carry them (e.g. a package-selection
            // click that doesn't forward mac/ip/link-login/chap-id/
            // chap-challenge - or forwards a different apparent IP due to
            // CGNAT/mobile network changes). Overwriting them with null/stale
            // values here would break the MikroTik handoff even though the
            // original values were captured correctly on landing.
            'client_mac' => $clientMac ?: $session?->client_mac,
            'client_ip' => $clientIp ?: $session?->client_ip,
            'link_login' => $incomingLinkLogin ?: $session?->link_login,
            'link_orig' => $request->input('link-orig') ?: $session?->link_orig,
            'chap_id' => $request->input('chap-id') ?: $session?->chap_id,
            'chap_challenge' => $request->input('chap-challenge') ?: $session?->chap_challenge,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'metadata' => array_merge($session?->metadata ?? [], [
                'user_agent' => $request->userAgent(),
                'accept_language' => $request->header('Accept-Language'),
            ]),
        ];

        if ($session) {
            $session->update($data);

            session([self::SESSION_TOKEN_KEY => $session->session_token]);

            Log::info('CAPTIVE_FLOW_TRACE', [
                'stage' => 'CaptivePortalService::createSession:updated_existing',
                'laravel_session_id_hash' => $this->sessionIdHash(),
                'captive_portal_session_id' => $session->id,
                'has_link_login' => (bool) $session->link_login,
                'has_link_orig' => (bool) $session->link_orig,
                'has_chap_id' => (bool) $session->chap_id,
                'has_chap_challenge' => (bool) $session->chap_challenge,
            ]);

            return $session;
        }

        $created = CaptivePortalSession::create(array_merge($data, [
            'session_token' => $this->generateSessionToken(),
        ]));

        session([self::SESSION_TOKEN_KEY => $created->session_token]);

        Log::info('CAPTIVE_FLOW_TRACE', [
            'stage' => 'CaptivePortalService::createSession:created_new',
            'laravel_session_id_hash' => $this->sessionIdHash(),
            'captive_portal_session_id' => $created->id,
            'has_link_login' => (bool) $created->link_login,
            'has_link_orig' => (bool) $created->link_orig,
            'has_chap_id' => (bool) $created->chap_id,
            'has_chap_challenge' => (bool) $created->chap_challenge,
        ]);

        return $created;
    }

    /**
     * Resolve the CaptivePortalSession that should be reused for this
     * request. Prefers the opaque session_token carried in the Laravel
     * session (survives across requests even when MAC/IP change, e.g. due to
     * CGNAT or a package-selection request that drops MikroTik's params).
     * Falls back to MAC/IP matching only when no valid token is present.
     */
    private function resolveExistingSession(Request $request, ?Router $router, ?string $clientMac, ?string $clientIp): ?CaptivePortalSession
    {
        $token = session(self::SESSION_TOKEN_KEY);

        if ($token) {
            $session = $this->getSessionByToken($token);

            Log::info('CAPTIVE_FLOW_TRACE', [
                'stage' => $session
                    ? 'CaptivePortalService::resolveExistingSession:matched_via_laravel_session'
                    : 'CaptivePortalService::resolveExistingSession:stale_token_not_found',
                'path' => $request->path(),
                'laravel_session_id_hash' => $this->sessionIdHash(),
                'captive_portal_session_id' => $session?->id,
            ]);

            if ($session) {
                return $session;
            }
        }

        $session = $this->findExistingSession($router, $clientMac, $clientIp);

        Log::info('CAPTIVE_FLOW_TRACE', [
            'stage' => 'CaptivePortalService::resolveExistingSession:fallback_device_match',
            'path' => $request->path(),
            'laravel_session_id_hash' => $this->sessionIdHash(),
            'matched' => (bool) $session,
            'captive_portal_session_id' => $session?->id,
        ]);

        return $session;
    }

    /**
     * Short, non-reversible hash of the Laravel session ID for log
     * correlation. Never logs the actual session cookie/ID.
     */
    private function sessionIdHash(): ?string
    {
        try {
            return substr(hash('sha256', session()->getId()), 0, 12);
        } catch (\Throwable $e) {
            return null;
        }
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
