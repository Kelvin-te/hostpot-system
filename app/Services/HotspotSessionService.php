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

    public function getDeviceIdentificationService(): DeviceIdentificationService
    {
        return $this->deviceService;
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
        //
        // BUT: if the user has never connected (no bytes, no uptime), they
        // may simply not have logged in through the captive portal yet.
        // Don't expire them in that case.
        if (!$this->mikroTikService->isSessionActiveOnRouter($session)) {
            $hasUsedData = ($session->bytes_total ?? 0) > 0
                || !empty($session->mikrotik_data['uptime_seconds']);

            // Not logged in yet — this is the normal state before captive-portal login.
            if (!$hasUsedData) {
                return $session;
            }

            Log::info('Hotspot session disconnected on router', [
                'session_id' => $session->session_id,
            ]);

            $stillValid = $session->expires_at && $session->expires_at > now();

            $session->update([
                'status' => $stillValid ? 'disconnected' : 'expired',
                'expires_at' => $stillValid ? $session->expires_at : now(),
            ]);

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

        // Re-create the hotspot user on the router — it may have been
        // removed during disconnect or by a sync cycle.
        $this->mikroTikService->createHotspotSession($session);

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

        // Calculate expiry time — always recalculate from the package to
        // ensure the timezone is correct. The authorization's expires_at
        // may have been calculated under a different timezone.
        $expiresAt = $this->calculateExpiryTime($package);

        // Defensive guard: expiry must always be in the future. If the package
        // data somehow produces a past date, fall back to 24h so the user is
        // not disconnected seconds after login.
        if (!$expiresAt || $expiresAt <= now()) {
            Log::error('Calculated session expiry is not in the future', [
                'package_id' => $package->id,
                'calculated_expires_at' => $expiresAt?->toIso8601String(),
                'validity_minutes' => $package->validity_minutes,
                'session_timeout' => $package->session_timeout,
                'validity_days' => $package->validity_days,
            ]);
            $expiresAt = now()->addDay();
        }

        $sessionData = [
            'mac_address' => $deviceInfo['mac_address'],
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'device_fingerprint' => $deviceInfo['device_fingerprint'],
            'package_id' => $package->id,
            'authorization_id' => $authorization->id,
            'user_id' => $user?->id,
            'username' => $username,
            'mikrotik_username' => $authorization->hotspot_username,
            'expires_at' => $expiresAt,
        ];

        $session = HotspotSession::createSession($sessionData);

        // Create the hotspot user + profile directly on the router via API.
        // This is the primary auth mechanism — no RADIUS roundtrip required.
        $created = $this->mikroTikService->createHotspotSession($session);

        if (!$created) {
            Log::warning('Hotspot session provisioning failed', [
                'session_id' => $session->session_id,
            ]);

            $session->update(['status' => 'provisioning_failed']);
        }

        return $session;
    }

    /**
     * Authenticate user with voucher or credentials
     */
    public function authenticateUser(Request $request, string $username, ?string $password = null, ?Package $package = null): ?HotspotSession
    {
        // Check if it's a voucher code (no password required)
        if (!$password) {
            return $this->authenticateWithVoucher($request, $username, $package);
        }

        // Check if it's phone number + password
        return $this->authenticateWithCredentials($request, $username, $password);
    }

    /**
     * Authenticate with voucher code
     * NOTE: This now creates authorization first, then session from authorization
     */
    protected function authenticateWithVoucher(Request $request, string $voucherCode, ?Package $package = null): ?HotspotSession
    {
        // Find voucher by code
        $voucher = Voucher::findByCode($voucherCode);

        if (!$voucher || !$voucher->isValid()) {
            return null; // Invalid or expired voucher
        }

        $targetPackage = $package ?? $voucher->package;

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
            $deviceInfo['mac_address'] ?? null,
            $targetPackage
        );

        // Create session from authorization
        $sessionData = [
            'mac_address' => $deviceInfo['mac_address'],
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'device_fingerprint' => $deviceInfo['device_fingerprint'],
            'package_id' => $targetPackage->id,
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

        // Create hotspot user on router via API
        $this->mikroTikService->createHotspotSession($session);

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
     * the MikroTik router via API.
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
     * Sync local hotspot sessions by polling each router via MikroTik API.
     * Updates byte counters, detects stale sessions, and re-creates missing users.
     */
    public function syncSessionsWithRouters(): array
    {
        $routers = \App\Models\Router::where('is_active', true)->get();
        $synced = 0;
        $stopped = 0;
        $recreated = 0;

        foreach ($routers as $router) {
            $result = $this->mikroTikService->syncSessionsWithRouter($router);

            if (($result['success'] ?? false) === true) {
                $synced += $result['synced'] ?? 0;
                $stopped += $result['expired'] ?? 0;
                $recreated += $result['missing'] ?? 0;
            }
        }

        Log::info('Session sync via MikroTik API completed', [
            'synced' => $synced,
            'stopped' => $stopped,
            'recreated' => $recreated,
        ]);

        return [
            'success' => true,
            'synced' => $synced,
            'stopped' => $stopped,
            'recreated' => $recreated,
        ];
    }

    /**
     * @deprecated Use syncSessionsWithRouters() instead.
     * Kept for backward compatibility — delegates to the API-based sync.
     */
    public function syncSessionsWithCore(): array
    {
        return $this->syncSessionsWithRouters();
    }

    /**
     * Carry over unused data from a previous session to a new session.
     * Only applies when the same package is repurchased within 24h of expiry.
     */
    public function carryOverData(HotspotSession $newSession, HotspotSession $previousSession): void
    {
        if (!$previousSession->package || $previousSession->package_id !== $newSession->package_id) {
            return;
        }

        $remainingData = $previousSession->getRemainingData();
        if ($remainingData === null || $remainingData <= 0) {
            return;
        }

        // Only carry over if previous session expired within last 24 hours
        if ($previousSession->expires_at < now()->subDay()) {
            return;
        }

        $newSession->update([
            'bytes_total' => 0,
        ]);

        Log::info('Data carry-over applied', [
            'new_session_id' => $newSession->session_id,
            'previous_session_id' => $previousSession->session_id,
            'carried_over_bytes' => $remainingData,
        ]);
    }

    /**
     * Find the most recent expired session for a user/device to check for carry-over.
     */
    public function findCarryOverSession(Request $request, Package $package): ?HotspotSession
    {
        $deviceInfo = $this->deviceService->getDeviceInfo($request);

        return HotspotSession::where('package_id', $package->id)
            ->where('status', 'expired')
            ->where('expires_at', '>', now()->subDay())
            ->where(function ($q) use ($deviceInfo) {
                $q->where('mac_address', $deviceInfo['mac_address'] ?? '')
                  ->orWhere('device_fingerprint', $deviceInfo['device_fingerprint'] ?? '');
            })
            ->latest('expires_at')
            ->first();
    }
}
