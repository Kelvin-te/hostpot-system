<?php

namespace App\Services;

use App\Models\Router;
use App\Models\HotspotSession;
use App\Classes\Mikrotik;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Query;
use Exception;

class MikroTikService
{
    protected Mikrotik $mikrotik;

    public function __construct()
    {
        $this->mikrotik = new Mikrotik();
    }

    /**
     * Create hotspot user and session on MikroTik router
     */
    public function createHotspotSession(HotspotSession $session): bool
    {
        try {
            $router = $session->package->router;
            
            if (!$router) {
                Log::warning('No router found for session', ['session_id' => $session->session_id]);
                return false;
            }

            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return false;
            }

            // Create user profile for the package
            $profileName = $this->createOrUpdateUserProfile($client, $session);
            
            // Create hotspot user
            $username = $session->username ?: $session->session_id;
            $password = $this->generatePassword();
            
            $this->createHotspotUser($client, $username, $password, $profileName, $session);
            
            // Update session with credentials
            $session->update([
                'mikrotik_username' => $username,
                'mikrotik_password' => $password,
                'mikrotik_profile' => $profileName
            ]);
            
            Log::info('Hotspot session created on MikroTik', [
                'session_id' => $session->session_id,
                'router_id' => $router->id,
                'username' => $username,
                'profile' => $profileName
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to create hotspot session on MikroTik', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Disconnect user from MikroTik hotspot
     */
    public function disconnectUser(HotspotSession $session): bool
    {
        try {
            $router = $session->package->router;
            
            if (!$router) {
                Log::warning('No router found for session', ['session_id' => $session->session_id]);
                return false;
            }

            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return false;
            }

            // Remove active hotspot sessions
            $this->removeActiveHotspotSessions($client, $session);
            
            // Remove hotspot user
            $this->removeHotspotUser($client, $session);
            
            Log::info('User disconnected from MikroTik', [
                'session_id' => $session->session_id,
                'router_id' => $router->id
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to disconnect user from MikroTik', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Connect to MikroTik router
     */
    protected function connectToRouter(Router $router): ?Client
    {
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->api_port ?? 8728,
                'timeout' => 10,
            ]);
            
            // Test connection with a simple query
            $query = new Query('/system/identity/print');
            $client->query($query)->read();
            
            Log::info('Connected to MikroTik router', [
                'router_id' => $router->id,
                'ip' => $router->ip_address
            ]);
            
            return $client;
            
        } catch (Exception $e) {
            Log::error('Failed to connect to MikroTik router', [
                'router_id' => $router->id,
                'ip' => $router->ip_address,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Create or update user profile for the package
     */
    protected function createOrUpdateUserProfile(Client $client, HotspotSession $session): string
    {
        $package = $session->package;
        $profileName = 'pkg_' . $package->id . '_' . str_replace(' ', '_', strtolower($package->name));
        
        try {
            // Check if profile exists
            $query = (new Query('/ip/hotspot/user/profile/print'))
                ->where('name', $profileName);
            
            $existingProfiles = $client->query($query)->read();
            
            if (empty($existingProfiles)) {
                // Create new profile
                $this->createUserProfile($client, $profileName, $package);
            } else {
                // Update existing profile
                $this->updateUserProfile($client, $profileName, $package, $existingProfiles[0]['.id']);
            }
            
            return $profileName;
            
        } catch (Exception $e) {
            Log::error('Failed to create/update user profile', [
                'profile_name' => $profileName,
                'error' => $e->getMessage()
            ]);
            
            // Return default profile if creation fails
            return 'default';
        }
    }

    /**
     * Create user profile
     */
    protected function createUserProfile(Client $client, string $profileName, $package): void
    {
        $profileData = [
            'name' => $profileName,
            'rate-limit' => $this->formatRateLimit($package),
            'session-timeout' => $this->formatSessionTimeout($package),
            'idle-timeout' => '00:10:00', // 10 minutes idle timeout
            'keepalive-timeout' => '00:02:00',
            'status-autorefresh' => '00:01:00',
        ];

        // Add data limits if specified
        if ($package->data_limit) {
            $profileData['shared-users'] = (string)($package->shared_users ?? 1);
        }

        $query = new Query('/ip/hotspot/user/profile/add');
        foreach ($profileData as $key => $value) {
            $query->equal($key, $value);
        }
        
        $client->query($query)->read();
        
        Log::info('Created hotspot user profile', [
            'profile_name' => $profileName,
            'package_id' => $package->id
        ]);
    }

    /**
     * Update existing user profile
     */
    protected function updateUserProfile(Client $client, string $profileName, $package, string $profileId): void
    {
        $profileData = [
            'rate-limit' => $this->formatRateLimit($package),
            'session-timeout' => $this->formatSessionTimeout($package),
        ];

        $query = new Query('/ip/hotspot/user/profile/set');
        $query->equal('.id', $profileId);
        
        foreach ($profileData as $key => $value) {
            $query->equal($key, $value);
        }
        
        $client->query($query)->read();
        
        Log::info('Updated hotspot user profile', [
            'profile_name' => $profileName,
            'package_id' => $package->id
        ]);
    }

    /**
     * Create hotspot user
     */
    protected function createHotspotUser(Client $client, string $username, string $password, string $profileName, HotspotSession $session): void
    {
        $userData = [
            'name' => $username,
            'password' => $password,
            'profile' => $profileName,
            'comment' => 'Auto-created for session: ' . $session->session_id,
        ];

        // Add MAC address binding if available
        if ($session->mac_address) {
            $userData['mac-address'] = $session->mac_address;
        }

        $query = new Query('/ip/hotspot/user/add');
        foreach ($userData as $key => $value) {
            $query->equal($key, $value);
        }
        
        $client->query($query)->read();
        
        Log::info('Created hotspot user', [
            'username' => $username,
            'profile' => $profileName,
            'session_id' => $session->session_id
        ]);
    }

    /**
     * Remove active hotspot sessions
     */
    protected function removeActiveHotspotSessions(Client $client, HotspotSession $session): void
    {
        try {
            $query = new Query('/ip/hotspot/active/print');
            $activeSessions = $client->query($query)->read();

            foreach ($activeSessions as $activeSession) {
                if ($this->isMatchingSession($activeSession, $session)) {
                    $removeQuery = (new Query('/ip/hotspot/active/remove'))
                        ->equal('.id', $activeSession['.id']);
                    
                    $client->query($removeQuery)->read();
                    
                    Log::info('Removed active hotspot session', [
                        'session_id' => $session->session_id,
                        'mikrotik_id' => $activeSession['.id']
                    ]);
                }
            }
            
        } catch (Exception $e) {
            Log::error('Failed to remove active sessions', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove hotspot user
     */
    protected function removeHotspotUser(Client $client, HotspotSession $session): void
    {
        try {
            $username = $session->mikrotik_username ?: $session->username ?: $session->session_id;
            
            $query = (new Query('/ip/hotspot/user/print'))
                ->where('name', $username);
            
            $users = $client->query($query)->read();
            
            foreach ($users as $user) {
                $removeQuery = (new Query('/ip/hotspot/user/remove'))
                    ->equal('.id', $user['.id']);
                
                $client->query($removeQuery)->read();
                
                Log::info('Removed hotspot user', [
                    'username' => $username,
                    'session_id' => $session->session_id
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to remove hotspot user', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if MikroTik session matches our session
     */
    protected function isMatchingSession(array $activeSession, HotspotSession $session): bool
    {
        // Match by username
        $username = $session->mikrotik_username ?: $session->username ?: $session->session_id;
        if (isset($activeSession['user']) && $activeSession['user'] === $username) {
            return true;
        }

        // Match by MAC address
        if ($session->mac_address && isset($activeSession['mac-address'])) {
            if (strtolower($session->mac_address) === strtolower($activeSession['mac-address'])) {
                return true;
            }
        }

        // Match by IP address
        if ($session->ip_address && isset($activeSession['address'])) {
            if ($session->ip_address === $activeSession['address']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format rate limit for MikroTik
     */
    protected function formatRateLimit($package): string
    {
        $download = $package->bandwidth_download ?? 10; // Default 10 Mbps
        $upload = $package->bandwidth_upload ?? 5;     // Default 5 Mbps
        
        return $upload . 'M/' . $download . 'M';
    }

    /**
     * Format session timeout for MikroTik
     */
    protected function formatSessionTimeout($package): string
    {
        $hours = $package->session_timeout ?? 24; // Default 24 hours
        
        // Convert hours to HH:MM:SS format
        $totalMinutes = $hours * 60;
        $hoursFormatted = str_pad(floor($totalMinutes / 60), 2, '0', STR_PAD_LEFT);
        $minutesFormatted = str_pad($totalMinutes % 60, 2, '0', STR_PAD_LEFT);
        
        return $hoursFormatted . ':' . $minutesFormatted . ':00';
    }

    /**
     * Generate random password
     */
    protected function generatePassword(): string
    {
        return bin2hex(random_bytes(8)); // 16 character hex password
    }

    /**
     * Get active sessions from MikroTik
     */
    public function getActiveSessions(Router $router): array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return [];
            }

            $query = new Query('/ip/hotspot/active/print');
            $activeSessions = $client->query($query)->read();
            
            return $activeSessions;
            
        } catch (Exception $e) {
            Log::error('Failed to get active sessions from MikroTik', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
}
