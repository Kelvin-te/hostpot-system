<?php

namespace App\Classes;

use Exception;
use RouterOS\Client;
use RouterOS\Query;
use App\Models\Router;

class Mikrotik
{
    private $router;
    private $client;

    public function __construct(Router $router = null)
    {
        $this->router = $router;
    }

    /**
     * Connect to a specific router
     */
    public function connect(Router $router = null)
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            throw new Exception('No router specified for connection');
        }

        try {
            $config = [
                'host' => $this->router->ip,
                'user' => $this->router->username,
                'pass' => $this->router->password ?: '',
                'port' => (int) 8728,
                'timeout' => 10, // Increased timeout for remote connections
                'attempts' => 3, // Connection attempts
                'delay' => 1, // Delay between attempts
            ];

            $this->client = new Client($config);

            return $this->client;
        } catch (Exception $exception) {
            throw new Exception('Failed to connect to router: ' . $exception->getMessage());
        }
    }

    /**
     * Test connection to router with detailed diagnostics
     */
    public function testConnection(Router $router = null)
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            return [
                'success' => false,
                'message' => 'No router specified for connection',
                'data' => null
            ];
        }

        // Step 1: Test basic network connectivity
        $pingResult = $this->testPing($this->router->ip);
        
        // Step 2: Test API port connectivity
        $portResult = $this->testPort($this->router->ip, 8728);
        
        // Step 3: Test API authentication
        try {
            $client = $this->connect($router);
            
            // Try to get system resource info to verify connection
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => $response,
                'diagnostics' => [
                    'ping' => $pingResult,
                    'port' => $portResult,
                    'api' => 'Connected successfully'
                ]
            ];
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $this->getDetailedErrorMessage($exception),
                'data' => null,
                'diagnostics' => [
                    'ping' => $pingResult,
                    'port' => $portResult,
                    'api' => $exception->getMessage()
                ]
            ];
        }
    }

    /**
     * Test basic connectivity using socket
     */
    private function testPing($ip)
    {
        // Use socket connection as a ping alternative
        $connection = @fsockopen($ip, 80, $errno, $errstr, 3);
        
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
    private function testPort($ip, $port)
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, 5);
        
        if ($connection) {
            fclose($connection);
            return "Port $port is open and accepting connections";
        } else {
            if ($errno == 111) {
                return "Port $port is closed - Connection refused";
            } elseif ($errno == 110) {
                return "Port $port connection timed out";
            } else {
                return "Port $port is not accessible (Error: $errno - $errstr)";
            }
        }
    }

    /**
     * Get detailed error message based on exception
     */
    private function getDetailedErrorMessage(Exception $exception)
    {
        $message = $exception->getMessage();
        
        if (strpos($message, 'Connection refused') !== false) {
            return 'Connection refused - MikroTik API service may be disabled or port 8728 is blocked';
        } elseif (strpos($message, 'Connection timed out') !== false) {
            return 'Connection timed out - Router not responding or firewall blocking connection';
        } elseif (strpos($message, 'No route to host') !== false) {
            return 'No route to host - Check network connectivity and router IP address';
        } elseif (strpos($message, 'invalid user name or password') !== false) {
            return 'Authentication failed - Invalid username or password';
        } elseif (strpos($message, 'cannot log in') !== false) {
            return 'Login failed - User may not have API access permissions';
        } else {
            return "Connection failed: $message";
        }
    }

    /**
     * Get system information
     */
    public function getSystemInfo(Router $router = null)
    {
        try {
            $client = $this->connect($router);
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
    public function getInterfaces(Router $router = null)
    {
        try {
            $client = $this->connect($router);
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
     * Get router identity
     */
    public function getIdentity(Router $router = null)
    {
        try {
            $client = $this->connect($router);
            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();
            
            return [
                'success' => true,
                'data' => $response[0] ?? []
            ];
        } catch (Exception $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * Connect via HTTP (RouterOS 6.x compatible)
     */
    public function connectViaHTTP(Router $router = null)
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            return ['success' => false, 'message' => 'No router specified'];
        }

        try {
            // For RouterOS 6.x, try different approaches
            $protocols = ['http']; // Start with HTTP only for RouterOS 6.x
            $lastError = '';

            foreach ($protocols as $protocol) {
                // Try REST API first (RouterOS 7.1+)
                $restUrl = "{$protocol}://{$this->router->ip}/rest/system/resource";
                $restResult = $this->tryHttpRequest($restUrl, $this->router->username, $this->router->password);
                
                if ($restResult['success']) {
                    return [
                        'success' => true,
                        'message' => "Connected via {$protocol} REST API",
                        'protocol' => $protocol,
                        'api_type' => 'rest',
                        'data' => $restResult['data']
                    ];
                }

                // Try legacy web interface authentication (RouterOS 6.x)
                $webUrl = "{$protocol}://{$this->router->ip}/";
                $webResult = $this->tryWebInterfaceAuth($webUrl, $this->router->username, $this->router->password);
                
                if ($webResult['success']) {
                    return [
                        'success' => true,
                        'message' => "Connected via {$protocol} Web Interface (RouterOS 6.x)",
                        'protocol' => $protocol,
                        'api_type' => 'web',
                        'data' => ['authenticated' => true, 'version' => '6.x']
                    ];
                }

                $lastError = $restResult['error'] . ' | ' . $webResult['error'];
            }

            return [
                'success' => false,
                'message' => "HTTP connection failed. RouterOS 6.x detected - REST API not available. Errors: {$lastError}"
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'HTTP connection error: ' . $exception->getMessage()
            ];
        }
    }

    /**
     * Try HTTP request
     */
    private function tryHttpRequest($url, $username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode == 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'data' => $data
            ];
        }

        return [
            'success' => false,
            'error' => $error ?: "HTTP {$httpCode}" . ($response ? ": " . substr($response, 0, 100) : "")
        ];
    }

    /**
     * Try web interface authentication (RouterOS 6.x)
     */
    private function tryWebInterfaceAuth($url, $username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode == 200) {
            // Check if we got the login page or actual content
            if (strpos($response, 'RouterOS') !== false && strpos($response, 'login') === false) {
                return [
                    'success' => true,
                    'data' => ['authenticated' => true]
                ];
            }
        }

        return [
            'success' => false,
            'error' => $error ?: "Web auth failed HTTP {$httpCode}"
        ];
    }

    /**
     * Execute HTTP REST API command
     */
    public function executeHTTPCommand($endpoint, $method = 'GET', $data = null, Router $router = null)
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            return ['success' => false, 'message' => 'No router specified'];
        }

        try {
            // Try HTTPS first, then HTTP
            $protocols = ['https', 'http'];
            $lastError = '';

            foreach ($protocols as $protocol) {
                $url = "{$protocol}://{$this->router->ip}/rest/{$endpoint}";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $this->router->username . ':' . $this->router->password);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);

                // Set HTTP method
                switch (strtoupper($method)) {
                    case 'POST':
                        curl_setopt($ch, CURLOPT_POST, true);
                        if ($data) {
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        }
                        break;
                    case 'PUT':
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                        if ($data) {
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        }
                        break;
                    case 'DELETE':
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                        break;
                }

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                    $responseData = json_decode($response, true);
                    return [
                        'success' => true,
                        'protocol' => $protocol,
                        'http_code' => $httpCode,
                        'data' => $responseData
                    ];
                }

                $lastError = $error ?: "HTTP {$httpCode}: " . substr($response, 0, 200);
            }

            return [
                'success' => false,
                'message' => "HTTP command failed: {$lastError}"
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'HTTP command error: ' . $exception->getMessage()
            ];
        }
    }

    /**
     * Get system information via HTTP REST API
     */
    public function getSystemInfoHTTP(Router $router = null)
    {
        $result = $this->executeHTTPCommand('system/resource', 'GET', null, $router);
        
        if ($result['success'] && isset($result['data'][0])) {
            return [
                'success' => true,
                'data' => $result['data'][0]
            ];
        }
        
        return $result;
    }

    /**
     * Get interfaces via HTTP REST API
     */
    public function getInterfacesHTTP(Router $router = null)
    {
        return $this->executeHTTPCommand('interface', 'GET', null, $router);
    }

    /**
     * Get router identity via HTTP REST API
     */
    public function getIdentityHTTP(Router $router = null)
    {
        $result = $this->executeHTTPCommand('system/identity', 'GET', null, $router);
        
        if ($result['success'] && isset($result['data'][0])) {
            return [
                'success' => true,
                'data' => $result['data'][0]
            ];
        }
        
        return $result;
    }

    /**
     * Test HTTP REST API connection
     */
    public function testHTTPConnection(Router $router = null)
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            return [
                'success' => false,
                'message' => 'No router specified for connection',
                'data' => null
            ];
        }

        // Step 1: Test basic HTTP connectivity
        $httpResult = $this->testHTTPPort($this->router->ip);
        
        // Step 2: Test REST API authentication and access
        try {
            $apiResult = $this->connectViaHTTP($router);
            
            if ($apiResult['success']) {
                return [
                    'success' => true,
                    'message' => 'HTTP REST API connection successful',
                    'data' => $apiResult['data'],
                    'diagnostics' => [
                        'http_connectivity' => $httpResult,
                        'rest_api' => "Connected via {$apiResult['protocol']}",
                        'authentication' => 'Authentication successful'
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $apiResult['message'],
                    'data' => null,
                    'diagnostics' => [
                        'http_connectivity' => $httpResult,
                        'rest_api' => $apiResult['message'],
                        'authentication' => 'Failed'
                    ]
                ];
            }
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'HTTP REST API test failed: ' . $exception->getMessage(),
                'data' => null,
                'diagnostics' => [
                    'http_connectivity' => $httpResult,
                    'rest_api' => $exception->getMessage(),
                    'authentication' => 'Error'
                ]
            ];
        }
    }

    /**
     * Test HTTP port connectivity
     */
    private function testHTTPPort($ip)
    {
        // Test HTTP port 80
        $httpConnection = @fsockopen($ip, 80, $errno, $errstr, 5);
        if ($httpConnection) {
            fclose($httpConnection);
            return "HTTP port 80 is accessible";
        }
        
        // Test HTTPS port 443
        $httpsConnection = @fsockopen($ip, 443, $errno, $errstr, 5);
        if ($httpsConnection) {
            fclose($httpsConnection);
            return "HTTPS port 443 is accessible";
        }
        
        return "HTTP/HTTPS ports are not accessible";
    }





    /**
     * Simple connection test without diagnostics
     */
    public function simpleConnectionTest(Router $router = null)
    {
        if ($router) {
            $this->router = $router;
        }

        if (!$this->router) {
            return ['success' => false, 'message' => 'No router specified'];
        }

        try {
            // Test raw socket connection first
            $socket = @fsockopen($this->router->ip, 8728, $errno, $errstr, 10);
            if (!$socket) {
                return [
                    'success' => false, 
                    'message' => "Socket connection failed: $errstr ($errno)"
                ];
            }
            fclose($socket);

            // Test RouterOS API connection
            $client = new Client([
                'host' => $this->router->ip,
                'user' => $this->router->username,
                'pass' => $this->router->password ?: '',
                'port' => 8728,
                'timeout' => 10,
            ]);

            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'identity' => $response[0]['name'] ?? 'Unknown'
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'details' => [
                    'host' => $this->router->ip,
                    'port' => 8728,
                    'user' => $this->router->username
                ]
            ];
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    public function checkConnection()
    {
        try {
            $client = new Client([
                'host' => get_setting('router_ip'),
                'user' => get_setting('router_username'),
                'pass' => get_setting('router_password') ?: '',
                'port' => 8728,
                'timeout' => 10,
            ]);

            $query = new Query('/system/resource/print');
            $client->query($query)->read();
            return $client;
        } catch (Exception $exception) {
            return null;
        }
    }
}
