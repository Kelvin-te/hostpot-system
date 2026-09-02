<?php

namespace App\Services;

use App\Exceptions\ActiveSessionConflictException;
use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HotspotSessionService
{
    protected DeviceIdentificationService $deviceService;
    protected HotspotAuthorizationService $authorizationService;
    protected MikroTikService $mikroTikService;

    public function __construct(DeviceIdentificationService $deviceService, HotspotAuthorizationService $authorizationService, MikroTikService $mikroTikService)
    {
        $this->deviceService = $deviceService;
        $this->authorizationService = $authorizationService;
        $this->mikroTikService = $mikroTikService;
    }

    /**
     * Check if device has an active session
     */
    public function getActiveSession(Request $request): ?HotspotSession
    {
        $deviceFingerprint = $this->deviceService->generateDeviceFingerprint($request);
        $macAddress = $this->deviceService->getMacAddress($request);

        // Try to find by device fingerprint first
        $session = HotspotSession::findActiveByDevice($deviceFingerprint);
        
        // If not found and we have MAC address, try by MAC
        if (!$session && $macAddress) {
            $session = HotspotSession::findActiveByMac($macAddress);
        }

        if (!$session) {
            return null;
        }

        // On-demand verification: check if the session is still active on the
        // MikroTik router. If MikroTik disconnected the user (timeout, data
        // limit, logout, admin kick) without notifying us, our DB still shows
        // status='active'. This prevents a stale session from trapping the user
        // in a redirect loop to the status page.
        if (!$this->mikroTikService->isSessionActiveOnRouter($session)) {
            Log::info('getActiveSession: session no longer active on router, marking disconnected', [
                'session_id' => $session->session_id,
                'device_fingerprint' => $deviceFingerprint,
                'mac_address' => $macAddress,
            ]);

            $stillValid = $session->expires_at && $session->expires_at > now();

            $session->update([
                'status' => $stillValid ? 'disconnected' : 'expired',
                'expires_at' => $stillValid ? $session->expires_at : now(),
            ]);

            if ($session->captivePortalSession) {
                $session->captivePortalSession->update([
                    'status' => 'expired',
                    'expires_at' => now(),
                ]);
            }

            return null;
        }

        return $session;
    }

    /**
     * Find a disconnected session that still has valid time remaining
     * for the current device. This allows users to reconnect and continue
     * using their remaining time/data after a disconnect.
     */
    public function getReconnectableSession(Request $request): ?HotspotSession
    {
        $deviceFingerprint = $this->deviceService->generateDeviceFingerprint($request);
        $macAddress = $this->deviceService->getMacAddress($request);

        $session = HotspotSession::findReconnectableByDevice($deviceFingerprint);

        if (!$session && $macAddress) {
            $session = HotspotSession::findReconnectableByMac($macAddress);
        }

        return $session;
    }

    /**
     * Reactivate a previously disconnected session — update device info
     * (MAC/IP may have changed on reconnect) and set status back to active.
     */
    public function reactivateSession(Request $request, HotspotSession $session): HotspotSession
    {
        $deviceInfo = $this->deviceService->getDeviceInfo($request);

        $session->update([
            'status' => 'active',
            'mac_address' => $deviceInfo['mac_address'] ?? $session->mac_address,
            'ip_address' => $deviceInfo['ip_address'] ?? $session->ip_address,
            'device_fingerprint' => $deviceInfo['device_fingerprint'] ?? $session->device_fingerprint,
        ]);

        Log::info('Session reactivated', [
            'session_id' => $session->session_id,
            'remaining_time' => $session->expires_at?->diffForHumans(),
        ]);

        return $session;
    }

    /**
     * NOTE: This now creates authorization first, then session from authorization
     */
    public function createSessionForPackage(Request $request, Package $package, ?User $user = null, ?string $username = null, ?int $paymentTransactionId = null): HotspotSession
    {
        $deviceInfo = $this->deviceService->getDeviceInfo($request);

        // Idempotency: if an active session already exists for this device/package, return it
        if ($existing = $this->getActiveSession($request)) {
            if ($existing->package_id === $package->id) {
                return $existing;
            }

            // Device is active on a different package. Never silently create
            // a second concurrent active session for the same device.
            throw new ActiveSessionConflictException($existing);
        }

        // Create authorization first
        $authorization = $this->authorizationService->createFromPackage(
            $package,
            $user,
            $username,
            $deviceInfo['mac_address'] ?? null,
            $paymentTransactionId
        );

        // Calculate expiry time
        $expiresAt = $authorization->expires_at ?? $this->calculateExpiryTime($package);

        $sessionData = [
            'mac_address' => $deviceInfo['mac_address'],
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'device_fingerprint' => $deviceInfo['device_fingerprint'],
            'package_id' => $package->id,
            'authorization_id' => $authorization->id,
            'user_id' => $user?->id,
            'username' => $username,
            'mikrotik_username' => $authorization->radius_username,
            'expires_at' => $expiresAt,
        ];

        $session = HotspotSession::createSession($sessionData);

        Log::info('CAPTIVE_FLOW_TRACE', [
            'stage' => 'HotspotSessionService::createSessionForPackage:session_created',
            'authorization_id' => $authorization->id,
            'client_identifier' => $username,
            'username' => $authorization->radius_username,
            'package_id' => $package->id,
            'hotspot_session_id' => $session->session_id,
            'mac_address' => $deviceInfo['mac_address'],
        ]);

        // NOTE: Direct MikroTik API call removed - will be handled by WinguFi Core + FreeRADIUS in future
        
        return $session;
    }

    /**
     * Authenticate user with voucher or credentials
     */
    public function authenticateUser(Request $request, string $username, ?string $password = null): ?HotspotSession
    {
        // Check if it's a voucher code (no password required)
        if (!$password) {
            return $this->authenticateWithVoucher($request, $username);
        }

        // Check if it's phone number + password
        return $this->authenticateWithCredentials($request, $username, $password);
    }

    /**
     * Authenticate with voucher code
     * NOTE: This now creates authorization first, then session from authorization
     */
    protected function authenticateWithVoucher(Request $request, string $voucherCode): ?HotspotSession
    {
        // Find voucher by code
        $voucher = Voucher::findByCode($voucherCode);

        if (!$voucher || !$voucher->isValid()) {
            return null; // Invalid or expired voucher
        }

        // Check if voucher is already used
        if ($voucher->isUsed()) {
            // Voucher already used, but check if it's the same device
            $deviceFingerprint = $this->deviceService->generateDeviceFingerprint($request);
            $existingSession = $voucher->session;
            
            if ($existingSession && $existingSession->device_fingerprint === $deviceFingerprint) {
                // Same device — return the session if it's still active
                if ($existingSession->isActive()) {
                    return $existingSession;
                }
                
                // If the session was disconnected but still has valid time,
                // reactivate it so the user can continue using remaining time.
                if ($existingSession->isReconnectable()) {
                    return $this->reactivateSession($request, $existingSession);
                }
                
                // Session is truly expired (time ran out) — fall through to
                // create a new session if the voucher is still valid.
            } else {
                return null; // Different device, voucher already used
            }
        }

        // Get device info
        $deviceInfo = $this->deviceService->getDeviceInfo($request);

        // Create authorization first
        $authorization = $this->authorizationService->createFromVoucher(
            $voucher,
            $deviceInfo['mac_address'] ?? null
        );

        // Create session from authorization
        $sessionData = [
            'mac_address' => $deviceInfo['mac_address'],
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'device_fingerprint' => $deviceInfo['device_fingerprint'],
            'package_id' => $voucher->package_id,
            'authorization_id' => $authorization->id,
            'username' => $voucherCode,
            'expires_at' => $authorization->expires_at,
        ];

        $session = HotspotSession::createSession($sessionData);

        // Mark voucher as used
        $voucher->markAsUsed(
            $deviceInfo['mac_address'] ?? 'unknown',
            $deviceInfo['ip_address'],
            $session->id
        );

        // NOTE: Direct MikroTik API call removed - will be handled by WinguFi Core + FreeRADIUS in future

        return $session;
    }

    /**
     * Authenticate with phone number and password
     */
    protected function authenticateWithCredentials(Request $request, string $phone, string $password): ?HotspotSession
    {
        // Find user by phone number
        $user = User::where('phone', $phone)->first();

        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }

        // Check if user has an active package/subscription
        $activeSession = HotspotSession::where('user_id', $user->id)
                                     ->active()
                                     ->first();

        if ($activeSession) {
            // Update session with current device info if needed
            $deviceInfo = $this->deviceService->getDeviceInfo($request);
            $activeSession->update([
                'mac_address' => $deviceInfo['mac_address'],
                'ip_address' => $deviceInfo['ip_address'],
                'device_fingerprint' => $deviceInfo['device_fingerprint'],
            ]);

            return $activeSession;
        }

        // Check if user has a disconnected session that still has valid time
        $reconnectable = HotspotSession::where('user_id', $user->id)
                                     ->reconnectable()
                                     ->latest()
                                     ->first();

        if ($reconnectable) {
            return $this->reactivateSession($request, $reconnectable);
        }

        // User exists but no active or reconnectable session - they need to purchase a package
        return null;
    }

    /**
     * Calculate expiry time for a package
     */
    protected function calculateExpiryTime(Package $package): Carbon
    {
        $now = now();

        // If package has a validity period (minutes/hours/days), use that
        $validityMinutes = $package->getValidityMinutes();
        if ($validityMinutes) {
            return $now->addMinutes($validityMinutes);
        }

        // If package has session timeout (in hours), use that
        if ($package->session_timeout) {
            return $now->addHours($package->session_timeout);
        }

        // Default to 24 hours
        return $now->addDay();
    }

    /**
     * Terminate a session
     *
     * Marks the session expired locally and removes the active session from
     * the MikroTik router. The direct router call is a pragmatic stopgap until
     * WinguFi Core/FreeRADIUS CoA disconnect is fully in place.
     */
    public function terminateSession(HotspotSession $session): bool
    {
        // Mark as disconnected (not expired) so the user can reconnect
        // and continue using their remaining time/data. The expires_at
        // is preserved — only the status changes.
        $session->update([
            'status' => 'disconnected',
        ]);

        // Remove the active session from the router immediately so the user
        // loses internet access instead of continuing to browse on a expired
        // session.
        try {
            $this->mikroTikService->disconnectUser($session);

            $data = $session->mikrotik_data ?? [];
            $data['disconnected_at'] = now()->toIso8601String();
            $session->update(['mikrotik_data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to disconnect session from MikroTik during terminateSession', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Get session status information
     */
    public function getSessionStatus(HotspotSession $session): array
    {
        $remainingTime = $session->getRemainingTime();
        $remainingData = $session->getRemainingData();

        return [
            'is_active' => $session->isActive(),
            'is_expired' => $session->isExpired(),
            'expires_at' => $session->expires_at,
            'remaining_time' => $remainingTime ? $this->formatRemainingTime($remainingTime) : null,
            'remaining_data' => $remainingData ? $this->formatBytes($remainingData) : 'Unlimited',
            'package_name' => $session->package->name,
            'bytes_used' => $this->formatBytes($session->bytes_total),
            'status' => $session->status,
        ];
    }

    /**
     * Format remaining time to hours and minutes
     */
    protected function formatRemainingTime($remainingTime): string
    {
        if (!$remainingTime) {
            return 'Expired';
        }

        $now = Carbon::now();
        $totalMinutes = $now->diffInMinutes($remainingTime, false);
        
        // If time is negative (expired), return expired
        if ($totalMinutes <= 0) {
            return 'Expired';
        }
        
        $hours = intval($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } elseif ($hours > 0) {
            return $hours . 'h';
        } elseif ($minutes > 0) {
            return $minutes . 'm';
        } else {
            return 'Less than 1m';
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        $expiredCount = HotspotSession::expired()
                                    ->where('status', '!=', 'expired')
                                    ->update(['status' => 'expired']);

        return $expiredCount;
    }

    /**
     * Get device information for debugging
     */
    public function getDeviceDebugInfo(Request $request): array
    {
        return $this->deviceService->getDeviceInfo($request);
    }

    /**
     * Sync local hotspot sessions with RADIUS accounting data from WinguFi Core.
     * Fetches active RADIUS sessions and updates bytes/time/status on matching
     * local HotspotSession records (matched by mikrotik_username or username).
     */
    public function syncSessionsWithCore(): array
    {
        $coreService = app(WinguFiCoreService::class);

        if (!$coreService->isEnabled()) {
            return ['success' => false, 'message' => 'WinguFi Core is not enabled'];
        }

        $routers = \App\Models\Router::all();
        $synced = 0;
        $stopped = 0;
        $notFound = 0;

        foreach ($routers as $router) {
            $routerExternalId = 'router-' . $router->identifier;

            // Fetch active RADIUS sessions for this router
            $result = $coreService->fetchSessions($routerExternalId, 'active');

            Log::info('Session sync debug: Core API response', [
                'router' => $router->name,
                'router_external_id' => $routerExternalId,
                'has_result' => $result !== null,
                'has_sessions' => $result && isset($result['data']['sessions']),
                'session_count' => $result['data']['sessions'] ?? 0,
                'raw_keys' => $result ? array_keys($result) : [],
            ]);

            if (!$result || !isset($result['data']['sessions'])) {
                continue;
            }

            $radiusSessions = $result['data']['sessions'];

            Log::info('Session sync debug: RADIUS sessions', [
                'router' => $router->name,
                'count' => count($radiusSessions),
                'usernames' => array_column($radiusSessions, 'username'),
            ]);

            // Index local active sessions by all possible usernames for this router
            $localSessions = HotspotSession::active()
                ->with('authorization')
                ->whereHas('package', function ($q) use ($router) {
                    $q->where('router_id', $router->id);
                })
                ->get();

            // Build a lookup map: radius_username => session
            $localByRadiusUsername = [];
            foreach ($localSessions as $session) {
                $radiusUsername = $session->mikrotik_username
                    ?? $session->authorization?->radius_username
                    ?? $session->username;
                $localByRadiusUsername[$radiusUsername] = $session;
            }

            Log::info('Session sync debug: Local sessions', [
                'router' => $router->name,
                'local_count' => $localSessions->count(),
                'local_usernames' => array_keys($localByRadiusUsername),
            ]);

            $matchedUsernames = [];

            foreach ($radiusSessions as $radiusSession) {
                $username = $radiusSession['username'] ?? null;
                if (!$username) {
                    continue;
                }

                $matchedUsernames[] = $username;
                $localSession = $localByRadiusUsername[$username] ?? null;

                if (!$localSession) {
                    $notFound++;
                    continue;
                }

                $inputOctets = (int) ($radiusSession['input_octets'] ?? 0);
                $outputOctets = (int) ($radiusSession['output_octets'] ?? 0);
                $sessionTime = (int) ($radiusSession['session_time'] ?? 0);

                $updateData = [
                    'bytes_uploaded' => $inputOctets,
                    'bytes_downloaded' => $outputOctets,
                    'bytes_total' => $inputOctets + $outputOctets,
                ];

                // Backfill mikrotik_username if it was missing
                if (!$localSession->mikrotik_username && $username) {
                    $updateData['mikrotik_username'] = $username;
                }

                $localSession->update($updateData);

                $synced++;
            }

            // Mark local active sessions not seen in RADIUS as disconnected
            foreach ($localSessions as $session) {
                $radiusUsername = $session->mikrotik_username
                    ?? $session->authorization?->radius_username
                    ?? $session->username;
                if (!in_array($radiusUsername, $matchedUsernames) && $session->isActive()) {
                    $session->update(['status' => 'disconnected']);
                    $stopped++;
                }
            }
        }

        Log::info('Session sync with WinguFi Core completed', [
            'synced' => $synced,
            'stopped' => $stopped,
            'not_found' => $notFound,
        ]);

        return [
            'success' => true,
            'synced' => $synced,
            'stopped' => $stopped,
            'not_found' => $notFound,
        ];
    }
}
