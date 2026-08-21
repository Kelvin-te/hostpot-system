<?php

namespace App\Services;

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

    public function __construct(DeviceIdentificationService $deviceService, HotspotAuthorizationService $authorizationService)
    {
        $this->deviceService = $deviceService;
        $this->authorizationService = $authorizationService;
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

        return $session;
    }

    /**
     * Create a new session for a package purchase
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
            'expires_at' => $expiresAt,
        ];

        $session = HotspotSession::createSession($sessionData);
        
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
                return $existingSession; // Same device, return existing session
            }
            return null; // Different device, voucher already used
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

        // User exists but no active session - they need to purchase a package
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
     * NOTE: Direct MikroTik disconnect removed - will be handled by WinguFi Core + FreeRADIUS in future
     */
    public function terminateSession(HotspotSession $session): bool
    {
        // Update session status in database
        $session->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);

        // NOTE: MikroTik disconnection removed - will be handled by WinguFi Core + FreeRADIUS in future
        // Future: Send revocation signal to WinguFi Core which will notify FreeRADIUS

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
}
