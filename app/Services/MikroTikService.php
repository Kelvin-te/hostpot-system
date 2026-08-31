<?php

namespace App\Services;

use App\Models\Router;
use App\Models\HotspotSession;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Query;
use Exception;

/**
 * MikroTik router service.
 *
 * Note: Methods that create, update or remove hotspot users directly on the
 * router (createHotspotSession, createUser, createUserProfile, etc.) are
 * legacy and must not be used for new captive-portal customer flows.
 * The current flow creates local RADIUS credentials and relies on WinguFi Core
 * / FreeRADIUS to authenticate the customer.
 */
class MikroTikService
{

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
            
            $this->createUser($client, $username, $password, $profileName, $session);
            
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

            // Remove active sessions
            $this->removeActiveSessions($client, $session);
            
            // Remove user
            $this->removeUser($client, $session);
            
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
     * Returns null on failure for safe error handling
     */
    public function connectToRouter(Router $router, bool $throw = false): ?Client
    {
        try {
            $client = new Client([
                'host' => $router->ip_address ?? $router->ip,
                'user' => $router->username,
                'pass' => $router->password ?? '',
                'port' => (int) ($router->api_port ?? 8728),
                'timeout' => 5,
                'attempts' => 1,
                'delay' => 0,
            ]);
            
            // Test connection with a simple query
            $query = new Query('/system/identity/print');
            $client->query($query)->read();
            
            Log::info('Connected to MikroTik router', [
                'router_id' => $router->id,
                'ip' => $router->ip_address ?? $router->ip
            ]);
            
            return $client;
            
        } catch (Exception $e) {
            Log::error('Failed to connect to MikroTik router', [
                'router_id' => $router->id,
                'ip' => $router->ip_address ?? $router->ip,
                'error' => $e->getMessage()
            ]);
            
            if ($throw) {
                throw $e;
            }
            
            return null;
        }
    }


    /**
     * Test connection to router with detailed diagnostics
     */
    public function testConnection(Router $router): array
    {
        $ip = $router->ip_address ?? $router->ip;
        $apiPort = (int) ($router->api_port ?? 8728);

        // Test API port connectivity
        $apiPortStatus = $this->testPort($ip, $apiPort);
        $apiPortOpen = stripos($apiPortStatus, 'open') !== false;
        
        // Step 2: Test API authentication
        try {
            $client = $this->connectToRouter($router, true);
            
            // Try to get system resource info to verify connection
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();
            
            // If API connection succeeds, mark API port as open regardless of port test
            $apiPortOpen = true;
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => $response,
                'diagnostics' => [
                    'api_port' => $apiPortOpen,
                ]
            ];
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $this->getDetailedErrorMessage($exception),
                'data' => null,
                'diagnostics' => [
                    'api_port' => $apiPortOpen,
                ]
            ];
        }
    }

    /**
     * Test basic connectivity using socket
     */
    private function testPing(string $ip, int $port = 80): string
    {
        // Use socket connection as a ping alternative
        $connection = @fsockopen($ip, $port, $errno, $errstr, 3);
        
        if ($connection) {
            fclose($connection);
            return 'Basic connectivity successful';
        } else {
            // Try ICMP ping if available
            if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
                $command = "ping -n 1 -w 3000 $ip 2>&1";
                exec($command, $output, $return_code);
                $output_string = implode(' ', $output);
                
                if ($return_code === 0 && (strpos($output_string, 'TTL=') !== false || strpos($output_string, 'time=') !== false)) {
                    return 'Ping successful';
                }
            }
            
            return 'Host unreachable - Check IP address and network connectivity';
        }
    }

    /**
     * Test port connectivity
     */
    private function testPort(string $ip, int $port): string
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, 2);
        
        if ($connection) {
            fclose($connection);
            return "Port $port is open and accepting connections";
        } else {
            if ($errno == 111) {
                return "Port $port is closed - Connection refused";
            } elseif ($errno == 110) {
                return "Port $port connection timed out";
            } elseif ($errno == 0) {
                return "Port $port is not accessible - No error but connection failed";
            } else {
                return "Port $port is not accessible (Error: $errno - $errstr)";
            }
        }
    }

    /**
     * Get detailed error message based on exception
     */
    private function getDetailedErrorMessage(Exception $exception): string
    {
        $message = $exception->getMessage();
        
        if (stripos($message, 'Connection refused') !== false) {
            return 'Connection refused - MikroTik API service may be disabled or the API port is blocked';
        } elseif (stripos($message, 'Connection timed out') !== false || stripos($message, 'A connection attempt failed') !== false) {
            return 'Connection timed out - Router not responding or firewall blocking connection';
        } elseif (stripos($message, 'No route to host') !== false) {
            return 'No route to host - Check network connectivity and router IP address';
        } elseif (stripos($message, 'invalid user name or password') !== false || stripos($message, 'cannot log in') !== false) {
            return 'Authentication failed - Invalid username, password, or user lacks API access';
        } elseif (stripos($message, 'Socket timeout reached') !== false) {
            return 'Socket read timeout - Router is too slow or the API response was interrupted';
        } elseif (stripos($message, 'Unable to establish socket session') !== false) {
            return 'Unable to establish socket session - Check that the MikroTik API service is enabled and reachable';
        } else {
            return "Connection failed: $message";
        }
    }

    /**
     * Get system information
     */
    public function getSystemInfo(Router $router): array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return ['success' => false, 'message' => 'Failed to connect to router'];
            }
            
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();
            
            if (!empty($response)) {
                return [
                    'success' => true,
                    'data' => $response[0] ?? []
                ];
            }
            
            return ['success' => false, 'message' => 'No system info received'];
        } catch (Exception $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * Get interface information
     */
    public function getInterfaces(Router $router): array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return ['success' => false, 'message' => 'Failed to connect to router'];
            }
            $query = new Query('/interface/print');
            $response = $client->query($query)->read();
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
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
     * Create user on MikroTik
     */
    protected function createUser(Client $client, string $username, string $password, string $profileName, HotspotSession $session): void
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
     * Remove active sessions from MikroTik
     */
    protected function removeActiveSessions(Client $client, HotspotSession $session): void
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
     * Remove user from MikroTik
     */
    protected function removeUser(Client $client, HotspotSession $session): void
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
        $seconds = $package->getSessionTimeoutSeconds();

        if ($seconds === null) {
            $seconds = 86400; // Default 24 hours
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    /**
     * Generate random password
     */
    protected function generatePassword(): string
    {
        return bin2hex(random_bytes(8)); // 16 character hex password
    }


    /**
     * Get active user details from MikroTik
     */
    public function getActiveUserDetails(Router $router, string $username): ?array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return null;
            }

            // Get active session for the user
            $query = (new Query('/ip/hotspot/active/print'))
                ->where('user', $username);
            
            $activeSessions = $client->query($query)->read();
            
            if (empty($activeSessions)) {
                return null;
            }

            // Return the first matching session
            $session = $activeSessions[0];
            
            return [
                'username' => $session['user'] ?? $username,
                'address' => $session['address'] ?? null,
                'mac_address' => $session['mac-address'] ?? null,
                'uptime' => $session['uptime'] ?? '0s',
                'session_time_left' => $session['session-time-left'] ?? null,
                'idle_time' => $session['idle-time'] ?? '0s',
                'bytes_in' => $session['bytes-in'] ?? 0,
                'bytes_out' => $session['bytes-out'] ?? 0,
                'packets_in' => $session['packets-in'] ?? 0,
                'packets_out' => $session['packets-out'] ?? 0,
                'login_by' => $session['login-by'] ?? null,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get active user details from MikroTik', [
                'router_id' => $router->id,
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }


    /**
     * Sync database sessions with router active sessions
     * - Creates missing sessions on router
     * - Marks sessions as expired if they don't exist on router anymore
     */
    public function syncSessionsWithRouter(Router $router): array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'Cannot connect to router',
                    'synced' => 0,
                    'created' => 0,
                    'expired' => 0,
                ];
            }

            // Get all active sessions from router
            $query = new Query('/ip/hotspot/active/print');
            $routerSessions = $client->query($query)->read();
            
            // Get all hotspot users from router
            $userQuery = new Query('/ip/hotspot/user/print');
            $routerUsers = $client->query($userQuery)->read();
            
            // Create maps of router sessions and users
            $routerSessionMap = [];
            foreach ($routerSessions as $rs) {
                $user = $rs['user'] ?? null;
                $mac = strtolower($rs['mac-address'] ?? '');
                
                if ($user) {
                    $routerSessionMap[$user] = true;
                }
                if ($mac) {
                    $routerSessionMap[$mac] = true;
                }
            }
            
            $routerUserMap = [];
            foreach ($routerUsers as $ru) {
                $user = $ru['name'] ?? null;
                if ($user) {
                    $routerUserMap[$user] = true;
                }
            }

            // Get all active sessions from database for this router
            $dbSessions = HotspotSession::active()
                ->whereHas('package', function($q) use ($router) {
                    $q->where('router_id', $router->id);
                })
                ->get();

            $syncedCount = 0;
            $createdCount = 0;
            $expiredCount = 0;

            $missingCount = 0;

            foreach ($dbSessions as $session) {
                $username = $session->mikrotik_username ?: $session->username ?: $session->session_id;
                $mac = strtolower($session->mac_address ?: '');
                
                // Check if session exists on router (currently active)
                $existsOnRouter = isset($routerSessionMap[$username]) || 
                                 ($mac && isset($routerSessionMap[$mac]));
                
                // Check if user exists on router (even if not active)
                $userExistsOnRouter = isset($routerUserMap[$username]);
                
                if (!$userExistsOnRouter) {
                    // Authentication is handled via RADIUS (WinguFi Core), so we do NOT
                    // create local MikroTik hotspot users here. Just report the gap.
                    $missingCount++;

                    Log::info('Session not found on router (RADIUS-managed, no local user created)', [
                        'session_id' => $session->session_id,
                        'username' => $username,
                    ]);
                } elseif ($existsOnRouter) {
                    // Session is active on router
                    $syncedCount++;
                } else {
                    // User exists but not active - this could be normal (user not logged in yet)
                    // Don't expire, just count as synced
                    $syncedCount++;
                }
            }

            return [
                'success' => true,
                'message' => "Synced $syncedCount sessions, $missingCount missing on router (RADIUS-managed, not created locally)",
                'synced' => $syncedCount,
                'created' => $createdCount,
                'missing' => $missingCount,
                'expired' => $expiredCount,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to sync sessions with router', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'synced' => 0,
                'created' => 0,
                'expired' => 0,
            ];
        }
    }

    /**
     * Resolve the HotSpot gateway/server IP from already-fetched RouterOS
     * entries. This is the address MikroTik uses to build the `link-login`
     * URL when the HotSpot profile's dns-name is empty - it is intentionally
     * distinct from the router's management/API/WireGuard IP.
     *
     * Preference order:
     * 1. The HotSpot profile's `hotspot-address` property (explicit override).
     * 2. The IP address bound to the HotSpot's own interface.
     *
     * @param array|null $profileEntry Row from /ip/hotspot/profile/print for the hotspot's profile.
     * @param array|null $interfaceAddressEntry Row from /ip/address/print for the hotspot's interface.
     */
    public function resolveHotspotServerIp(?array $profileEntry, ?array $interfaceAddressEntry): ?string
    {
        $hotspotAddress = $profileEntry['hotspot-address'] ?? null;
        if (!empty($hotspotAddress)) {
            return explode('/', $hotspotAddress)[0];
        }

        $interfaceAddress = $interfaceAddressEntry['address'] ?? null;
        if (!empty($interfaceAddress)) {
            return explode('/', $interfaceAddress)[0];
        }

        return null;
    }

    /**
     * Test if hotspot service is enabled on router
     */
    public function testHotspotService(Router $router): array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return [
                    'success' => false,
                    'enabled' => false,
                    'message' => 'Cannot connect to router',
                    'interface' => null,
                    'server_ip' => null,
                ];
            }

            // Check if hotspot service is running
            $query = new Query('/ip/hotspot/print');
            $hotspotInfo = $client->query($query)->read();
            
            if (empty($hotspotInfo)) {
                return [
                    'success' => true,
                    'enabled' => false,
                    'message' => 'Hotspot service is not configured',
                    'interface' => null,
                    'server_ip' => null,
                ];
            }

            $hotspot = $hotspotInfo[0];
            $interface = $hotspot['interface'] ?? null;
            $profileName = $hotspot['profile'] ?? null;

            $profileEntry = null;
            if ($profileName) {
                $profileQuery = (new Query('/ip/hotspot/profile/print'))
                    ->where('name', $profileName);
                $profiles = $client->query($profileQuery)->read();
                $profileEntry = $profiles[0] ?? null;
            }

            $interfaceAddressEntry = null;
            if ($interface) {
                $addressQuery = (new Query('/ip/address/print'))
                    ->where('interface', $interface);
                $addresses = $client->query($addressQuery)->read();
                $interfaceAddressEntry = $addresses[0] ?? null;
            }

            $serverIp = $this->resolveHotspotServerIp($profileEntry, $interfaceAddressEntry ?: null);

            return [
                'success' => true,
                'enabled' => true,
                'message' => 'Hotspot service is enabled',
                'interface' => $interface,
                'server_ip' => $serverIp,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to test hotspot service', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'enabled' => false,
                'message' => 'Error checking hotspot service: ' . $e->getMessage(),
                'interface' => null,
                'server_ip' => null,
            ];
        }
    }

    /**
     * Check walled garden status on router
     */
    public function checkWalledGardenStatus(Router $router): array
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return [
                    'success' => false,
                    'configured' => false,
                    'domains' => [],
                    'ips' => [],
                    'message' => 'Cannot connect to router',
                ];
            }

            // Get walled garden entries
            $query = new Query('/ip/hotspot/walled-garden/print');
            $entries = $client->query($query)->read();
            
            $domains = [];
            $ips = [];
            
            foreach ($entries as $entry) {
                if (isset($entry['dst-host']) && $entry['dst-host']) {
                    $domains[] = $entry['dst-host'];
                }
                if (isset($entry['dst-address']) && $entry['dst-address']) {
                    $ips[] = $entry['dst-address'];
                }
            }

            return [
                'success' => true,
                'configured' => !empty($entries),
                'domains' => $domains,
                'ips' => $ips,
                'message' => count($entries) . ' walled garden entries found',
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to check walled garden status', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'configured' => false,
                'domains' => [],
                'ips' => [],
                'message' => 'Error checking walled garden: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Provision this router as a RADIUS client (NAS) pointing at our FreeRADIUS server,
     * and enable RADIUS authentication on its hotspot server profile(s).
     */
    public function provisionRadiusClient(Router $router, string $secret): array
    {
        try {
            $client = $this->connectToRouter($router);

            if (!$client) {
                return ['success' => false, 'message' => 'Cannot connect to router'];
            }

            $radiusHost = config('services.radius.server_host');
            if (!$radiusHost) {
                return ['success' => false, 'message' => 'RADIUS server host is not configured (RADIUS_SERVER_HOST)'];
            }

            // Remove any existing hotspot RADIUS entries pointing at our server to avoid duplicates
            $query = new Query('/radius/print');
            $query->where('service', 'hotspot');
            $existing = $client->query($query)->read();

            foreach ($existing as $entry) {
                if (isset($entry['.id']) && ($entry['address'] ?? null) === $radiusHost) {
                    $removeQuery = new Query('/radius/remove');
                    $removeQuery->equal('.id', $entry['.id']);
                    $client->query($removeQuery)->read();
                }
            }

            $addQuery = new Query('/radius/add');
            $addQuery->equal('service', 'hotspot');
            $addQuery->equal('address', $radiusHost);
            $addQuery->equal('secret', $secret);
            $addQuery->equal('authentication-port', (string) config('services.radius.auth_port', 1812));
            $addQuery->equal('accounting-port', (string) config('services.radius.acct_port', 1813));
            $client->query($addQuery)->read();

            // Enable RADIUS on all hotspot server profiles
            $profileQuery = new Query('/ip/hotspot/profile/print');
            $profiles = $client->query($profileQuery)->read();

            foreach ($profiles as $profile) {
                if (isset($profile['.id'])) {
                    $setQuery = new Query('/ip/hotspot/profile/set');
                    $setQuery->equal('.id', $profile['.id']);
                    $setQuery->equal('use-radius', 'yes');
                    $client->query($setQuery)->read();
                }
            }

            Log::info('RADIUS client provisioned on router', [
                'router_id' => $router->id,
                'radius_host' => $radiusHost,
            ]);

            return ['success' => true, 'message' => 'RADIUS client provisioned successfully'];

        } catch (Exception $e) {
            Log::error('Failed to provision RADIUS client', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'RADIUS provisioning failed: ' . $e->getMessage()];
        }
    }

    /**
     * Configure the router's hotspot server profile to redirect unauthenticated
     * clients to our external captive portal instead of the router's local login page.
     */
    public function configureExternalPortal(Router $router, string $portalUrl): array
    {
        try {
            $client = $this->connectToRouter($router);

            if (!$client) {
                return ['success' => false, 'message' => 'Cannot connect to router'];
            }

            $profileQuery = new Query('/ip/hotspot/profile/print');
            $profiles = $client->query($profileQuery)->read();

            foreach ($profiles as $profile) {
                if (isset($profile['.id'])) {
                    $setQuery = new Query('/ip/hotspot/profile/set');
                    $setQuery->equal('.id', $profile['.id']);
                    // The captive-portal handoff posts a plaintext password (PAP-style), so
                    // http-pap must remain enabled; http-chap is kept for compatibility with
                    // any client that submits a CHAP-hashed response.
                    $setQuery->equal('login-by', 'http-pap,http-chap,mac-cookie');
                    $setQuery->equal('http-cookie-lifetime', '1d');
                    $client->query($setQuery)->read();
                }
            }

            $portalHost = parse_url($portalUrl, PHP_URL_HOST);
            if ($portalHost) {
                $existingQuery = new Query('/ip/hotspot/walled-garden/print');
                $existingQuery->where('dst-host', $portalHost);
                $existingEntries = $client->query($existingQuery)->read();

                if (empty($existingEntries)) {
                    $addGardenQuery = new Query('/ip/hotspot/walled-garden/add');
                    $addGardenQuery->equal('dst-host', $portalHost);
                    $addGardenQuery->equal('action', 'accept');
                    $client->query($addGardenQuery)->read();
                }
            }

            Log::info('External portal configured on router', [
                'router_id' => $router->id,
                'portal_url' => $portalUrl,
            ]);

            return ['success' => true, 'message' => 'Router configured to use the external captive portal'];

        } catch (Exception $e) {
            Log::error('Failed to configure external portal', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Portal configuration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Apply walled garden settings to router
     */
    public function applyWalledGarden(Router $router, array $domains, array $ips): bool
    {
        try {
            $client = $this->connectToRouter($router);
            
            if (!$client) {
                return false;
            }

            // Clear existing walled garden entries
            $query = new Query('/ip/hotspot/walled-garden/print');
            $entries = $client->query($query)->read();
            
            foreach ($entries as $entry) {
                if (isset($entry['.id'])) {
                    $removeQuery = new Query('/ip/hotspot/walled-garden/remove');
                    $removeQuery->equal('.id', $entry['.id']);
                    $client->query($removeQuery)->read();
                }
            }

            // Add new domain entries
            foreach ($domains as $domain) {
                if (empty($domain)) continue;
                
                $addQuery = new Query('/ip/hotspot/walled-garden/add');
                $addQuery->equal('dst-host', $domain);
                $addQuery->equal('action', 'accept');
                $client->query($addQuery)->read();
            }

            // Add new IP entries
            foreach ($ips as $ip) {
                if (empty($ip)) continue;
                
                $addQuery = new Query('/ip/hotspot/walled-garden/add');
                $addQuery->equal('dst-address', $ip);
                $addQuery->equal('action', 'accept');
                $client->query($addQuery)->read();
            }
            
            Log::info('Walled garden applied to router', [
                'router_id' => $router->id,
                'domains_count' => count($domains),
                'ips_count' => count($ips),
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to apply walled garden to router', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get comprehensive router diagnostics
     */
    public function getRouterDiagnostics(Router $router): array
    {
        try {
            $connection = $this->testConnection($router);
            $hotspot = $this->testHotspotService($router);
            $walledGarden = $this->checkWalledGardenStatus($router);
            
            // Get package sync status
            $totalPackages = \App\Models\Package::where('router_id', $router->id)->count();
            $syncedCount = $router->packages_sync_count ?? 0;
            $unsyncCount = $router->packages_unsync_count ?? 0;
            
            $lastSyncedAt = $router->last_synced_at;
            if ($lastSyncedAt && is_string($lastSyncedAt)) {
                $lastSyncedAt = \Carbon\Carbon::parse($lastSyncedAt)->format('Y-m-d H:i:s');
            } elseif ($lastSyncedAt) {
                $lastSyncedAt = $lastSyncedAt->format('Y-m-d H:i:s');
            }
            
            return [
                'connection' => $connection,
                'hotspot' => $hotspot,
                'walled_garden' => $walledGarden,
                'sync' => [
                    'total' => $totalPackages,
                    'synced' => $syncedCount,
                    'unsynced' => $unsyncCount,
                    'last_synced_at' => $lastSyncedAt,
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Get router diagnostics failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'connection' => [
                    'success' => false,
                    'message' => 'Diagnostics failed: ' . $e->getMessage(),
                    'diagnostics' => [
                        'api_port' => false,
                        'http_port' => false,
                        'https_port' => false,
                    ]
                ],
                'hotspot' => null,
                'walled_garden' => null,
                'sync' => null,
            ];
        }
    }

    /**
     * Reboot the router
     */
    public function rebootRouter(Router $router): array
    {
        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            $query = (new Query('/system/reboot'));
            $client->query($query)->read();

            return [
                'success' => true,
                'message' => 'Router reboot initiated'
            ];
        } catch (\Exception $e) {
            \Log::error('Router reboot failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to reboot router: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Backup the router configuration
     */
    public function backupRouter(Router $router): array
    {
        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            $query = (new Query('/system/backup/save'));
            $client->query($query)->read();

            return [
                'success' => true,
                'message' => 'Backup created successfully',
                'backup_file' => 'backup.backup'
            ];
        } catch (\Exception $e) {
            \Log::error('Router backup failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to backup router: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get router configuration
     */
    public function getRouterConfig(Router $router): array
    {
        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            // Try to get basic system info instead of full export
            $query = (new Query('/system/resource/print'));
            $result = $client->query($query)->read();

            $config = "# Router Configuration Export\n";
            $config .= "# Router: " . $router->name . "\n";
            $config .= "# IP: " . $router->ip . "\n";
            $config .= "# Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
            
            if (!empty($result)) {
                $config .= "# System Resources:\n";
                foreach ($result[0] as $key => $value) {
                    if ($key !== '.tag') {
                        $config .= "# {$key}: {$value}\n";
                    }
                }
            }

            return [
                'success' => true,
                'config' => $config
            ];
        } catch (\Exception $e) {
            \Log::error('Get router config failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to get config: ' . $e->getMessage()
            ];
        }
    }
}
