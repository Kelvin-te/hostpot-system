<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceIdentificationService
{
    /**
     * Generate a device fingerprint from request data
     */
    public function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $this->getMacAddress($request),
            $request->ip(),
            $this->sanitizeUserAgent($request->userAgent()),
            $request->header('Accept-Language', ''),
        ];

        // Filter out empty components
        $components = array_filter($components);
        
        // Create hash of combined components
        return hash('sha256', implode('|', $components));
    }

    /**
     * Extract MAC address from request
     * Note: This depends on MikroTik configuration and how it passes MAC address
     */
    public function getMacAddress(Request $request): ?string
    {
        // Common ways MikroTik passes MAC address:
        $macSources = [
            'HTTP_X_FORWARDED_MAC',     // Custom header
            'HTTP_X_MAC_ADDRESS',       // Custom header
            'HTTP_MAC',                 // Custom header
            'mac',                      // Query parameter
            'client-mac-address',       // Query parameter
        ];

        foreach ($macSources as $source) {
            if (str_starts_with($source, 'HTTP_')) {
                $mac = $request->server($source);
            } else {
                $mac = $request->get($source);
            }

            if ($mac && $this->isValidMacAddress($mac)) {
                return $this->normalizeMacAddress($mac);
            }
        }

        return null;
    }

    /**
     * Validate MAC address format
     */
    public function isValidMacAddress(?string $mac): bool
    {
        if (!$mac) {
            return false;
        }

        // Remove common separators and normalize
        $normalized = preg_replace('/[:-]/', '', strtoupper($mac));
        
        // Check if it's 12 hex characters
        return preg_match('/^[0-9A-F]{12}$/', $normalized) === 1;
    }

    /**
     * Normalize MAC address to consistent format (XX:XX:XX:XX:XX:XX)
     */
    public function normalizeMacAddress(string $mac): string
    {
        // Remove separators and convert to uppercase
        $clean = preg_replace('/[:-]/', '', strtoupper($mac));
        
        // Add colons every 2 characters
        return implode(':', str_split($clean, 2));
    }

    /**
     * Sanitize user agent string
     */
    private function sanitizeUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return '';
        }

        // Remove version numbers to group similar browsers/devices
        $sanitized = preg_replace('/\d+\.\d+[\d\.]*/', 'X.X', $userAgent);
        
        // Limit length
        return Str::limit($sanitized, 200);
    }

    /**
     * Get client IP address (considering proxies)
     */
    public function getClientIpAddress(Request $request): string
    {
        $ipSources = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipSources as $source) {
            $ip = $request->server($source);
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        return $request->ip();
    }

    /**
     * Extract device information for logging/debugging
     */
    public function getDeviceInfo(Request $request): array
    {
        return [
            'mac_address' => $this->getMacAddress($request),
            'ip_address' => $this->getClientIpAddress($request),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'headers' => [
                'x-forwarded-for' => $request->header('X-Forwarded-For'),
                'x-real-ip' => $request->header('X-Real-IP'),
                'accept-language' => $request->header('Accept-Language'),
            ],
            'query_params' => $request->query(),
        ];
    }

    /**
     * Check if device appears to be mobile
     */
    public function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent) {
            return false;
        }

        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 
            'BlackBerry', 'Windows Phone', 'Opera Mini'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if MAC address is randomized (privacy feature)
     */
    public function isRandomizedMac(?string $mac): bool
    {
        if (!$mac || !$this->isValidMacAddress($mac)) {
            return false;
        }

        $normalized = $this->normalizeMacAddress($mac);
        $firstOctet = hexdec(substr(str_replace(':', '', $normalized), 0, 2));
        
        // Check if locally administered bit is set (bit 1 of first octet)
        // This often indicates a randomized MAC address
        return ($firstOctet & 0x02) !== 0;
    }
}
