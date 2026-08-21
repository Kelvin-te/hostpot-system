<?php

namespace App\Services;

use App\Models\HotspotAuthorization;
use App\Models\Package;

class AuthorizationAttributeService
{
    /**
     * Generate RADIUS attributes from authorization
     */
    public function generateRadiusAttributes(HotspotAuthorization $authorization): array
    {
        $attributes = $authorization->authorization_attributes ?? [];
        $package = $authorization->package;

        // Add standard RADIUS attributes
        $attributes['User-Name'] = $authorization->client_identifier;
        $attributes['Framed-IP-Address'] = '255.255.255.254';
        $attributes['Framed-IP-Netmask'] = '255.255.255.255';

        // Add session timeout
        if ($authorization->session_timeout) {
            $attributes['Session-Timeout'] = $authorization->session_timeout;
        }

        // Add idle timeout
        if ($authorization->idle_timeout) {
            $attributes['Idle-Timeout'] = $authorization->idle_timeout;
        }

        // Add bandwidth limits from package
        if ($package->bandwidth_upload) {
            $attributes['WISPr-Bandwidth-Max-Up'] = $this->convertToBps($package->bandwidth_upload);
        }

        if ($package->bandwidth_download) {
            $attributes['WISPr-Bandwidth-Max-Down'] = $this->convertToBps($package->bandwidth_download);
        }

        // Add rate limit if specified
        if ($authorization->rate_limit) {
            $attributes['Framed-Filter-Id'] = $authorization->rate_limit;
        }

        // Add simultaneous sessions limit
        $attributes['Max-Multiple-Sessions'] = $authorization->simultaneous_sessions;

        return $attributes;
    }

    /**
     * Generate MikroTik attributes from authorization
     */
    public function generateMikroTikAttributes(HotspotAuthorization $authorization): array
    {
        $package = $authorization->package;
        $attributes = [];

        if ($package->bandwidth_upload && $package->bandwidth_download) {
            $attributes['limit-at'] = "{$package->bandwidth_upload}M/{$package->bandwidth_download}M";
        }

        if ($authorization->session_timeout) {
            $attributes['session-timeout'] = $authorization->session_timeout;
        }

        if ($authorization->idle_timeout) {
            $attributes['idle-timeout'] = $authorization->idle_timeout;
        }

        if ($package->rate_limit) {
            $attributes['rate-limit'] = $package->rate_limit;
        }

        return $attributes;
    }

    /**
     * Convert Mbps to bps
     */
    private function convertToBps(float $mbps): int
    {
        return (int) ($mbps * 1024 * 1024);
    }

    /**
     * Parse rate limit string to bytes
     */
    public function parseRateLimit(string $rateLimit): ?int
    {
        $rateLimit = strtoupper(trim($rateLimit));
        
        if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB|KB)/', $rateLimit, $matches)) {
            $value = floatval($matches[1]);
            $unit = $matches[2];
            
            switch ($unit) {
                case 'GB':
                    return (int) ($value * 1024 * 1024 * 1024);
                case 'MB':
                    return (int) ($value * 1024 * 1024);
                case 'KB':
                    return (int) ($value * 1024);
            }
        }
        
        return null;
    }
}
