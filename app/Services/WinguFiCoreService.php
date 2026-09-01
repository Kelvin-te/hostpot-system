<?php

namespace App\Services;

use App\Models\HotspotAuthorization;
use App\Models\Package;
use App\Models\RadiusNas;
use App\Models\Router;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WinguFiCoreService
{
    private string $baseUrl;
    private ?string $token;
    private string $sourceSystem;
    private bool $enabled;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wingufi.base_url', ''), '/');
        $this->token = config('services.wingufi.token');
        $this->sourceSystem = config('services.wingufi.source_system', 'admin');
        $this->enabled = (bool) config('services.wingufi.enabled', true);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function syncRouter(Router $router, ?RadiusNas $nas = null): array
    {
        $externalId = 'router-' . $router->identifier;

        $payload = [
            'external_id' => $externalId,
            'name' => $router->name,
            'identifier' => $router->identifier,
            'nasname' => $router->ip ?? $router->ip_address,
            'type' => 'mikrotik',
            'status' => $router->is_active ? 'active' : 'inactive',
        ];

        if ($nas) {
            $payload['radius_secret'] = $nas->nas_secret;
            $payload['auth_port'] = $nas->nas_port ?? config('services.radius.auth_port', 1812);
            $payload['acct_port'] = config('services.radius.acct_port', 1813);
        }

        $result = $this->post('/routers', $payload);

        Log::info('WinguFi Core router synchronized', [
            'router_id' => $router->id,
            'external_id' => $externalId,
            'wingufi_response' => $this->redactSensitive($result),
        ]);

        return $result;
    }

    public function syncClient(?User $user, string $clientIdentifier, ?string $password = null): array
    {
        $externalId = $user
            ? 'customer-' . $user->id
            : 'guest-' . md5($clientIdentifier);

        $payload = [
            'external_id' => $externalId,
            'username' => $clientIdentifier,
            'display_name' => $user?->name ?? $clientIdentifier,
            'phone' => $user?->phone,
            'status' => 'active',
        ];

        if ($password !== null) {
            $payload['password'] = $password;
        }

        return $this->post('/clients', $payload);
    }

    public function syncPackage(Package $package): array
    {
        $externalId = 'package-' . $package->id;

        return $this->post('/packages', [
            'external_id' => $externalId,
            'name' => $package->name,
            'code' => $externalId,
            'download_speed' => $package->bandwidth_download ? (int) ($package->bandwidth_download * 1000000) : null,
            'upload_speed' => $package->bandwidth_upload ? (int) ($package->bandwidth_upload * 1000000) : null,
            'validity_seconds' => $this->packageValiditySeconds($package),
            'session_timeout' => $package->getSessionTimeoutSeconds(),
            'data_limit_bytes' => $this->dataLimitBytes($package),
            'status' => $package->is_active ? 'active' : 'inactive',
        ]);
    }

    public function syncAuthorization(HotspotAuthorization $authorization): array
    {
        $package = $authorization->package;
        $user = $authorization->user;
        $clientIdentifier = $authorization->client_identifier ?? $user?->phone ?? 'guest-' . md5($authorization->client_mac ?? $authorization->id);
        $radiusUsername = $authorization->radius_username ?? $clientIdentifier;
        $radiusPassword = $authorization->radiusPassword();

        $this->syncClient($user, $clientIdentifier, $radiusPassword);
        $this->syncPackage($package);

        $externalId = $this->externalAuthorizationId($authorization);

        $payload = [
            'external_id' => $externalId,
            'client_external_id' => $user
                ? 'customer-' . $user->id
                : 'guest-' . md5($clientIdentifier),
            'package_external_id' => 'package-' . $package->id,
            'status' => $authorization->status === 'revoked' ? 'revoked' : 'active',
            'starts_at' => $authorization->starts_at?->toIso8601String(),
            'expires_at' => $authorization->expires_at?->toIso8601String(),
            'session_timeout' => $authorization->session_timeout,
            'download_speed' => $package->bandwidth_download ? (int) ($package->bandwidth_download * 1000000) : null,
            'upload_speed' => $package->bandwidth_upload ? (int) ($package->bandwidth_upload * 1000000) : null,
            'data_limit_bytes' => $this->dataLimitBytes($package),
        ];

        $result = $this->post('/authorizations', $payload);

        Log::info('WinguFi Core authorization synchronized', [
            'authorization_id' => $authorization->id,
            'external_id' => $externalId,
            'wingufi_response' => $this->redactSensitive($result),
        ]);

        return $result;
    }

    public function fetchAuthorization(string $externalId): ?array
    {
        try {
            $response = $this->client()->get("/authorizations/{$externalId}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::warning('Failed to fetch WinguFi Core authorization', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function fetchSessions(string $routerExternalId, string $status = 'active'): ?array
    {
        if (!$this->enabled || !$this->token) {
            return null;
        }

        try {
            $response = $this->client()->get('/sessions', [
                'router_external_id' => $routerExternalId,
                'status' => $status,
                'per_page' => 100,
            ]);

            if (!$response->successful()) {
                Log::warning('WinguFi Core fetch sessions failed', [
                    'router_external_id' => $routerExternalId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::warning('Failed to fetch WinguFi Core sessions', [
                'router_external_id' => $routerExternalId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function externalAuthorizationId(HotspotAuthorization $authorization): string
    {
        $paymentId = $authorization->payment_transaction_id ?? $authorization->id;
        return $this->externalAuthorizationIdForPayment($paymentId);
    }

    public function externalAuthorizationIdForPayment(int $paymentId): string
    {
        return 'auth-' . $this->sourceSystem . '-ptx-' . $paymentId;
    }

    private function post(string $endpoint, array $payload): array
    {
        if (!$this->enabled || !$this->token) {
            throw new \RuntimeException('WinguFi Core synchronization is disabled or not configured.');
        }

        $payload['source_system'] = $this->sourceSystem;

        $response = $this->client()->post($endpoint, $payload);

        if (!$response->successful() && $response->status() !== 422) {
            $this->throwOnFailure($response, $endpoint);
        }

        $body = $response->json() ?? [];

        if ($response->status() === 422 && ($body['errors'] ?? null)) {
            $this->throwOnFailure($response, $endpoint);
        }

        return $body;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->token)
            ->baseUrl($this->baseUrl)
            ->timeout(30)
            ->asJson();
    }

    private function throwOnFailure(Response $response, string $endpoint): void
    {
        $body = $response->json() ?? [];
        $message = $body['message'] ?? 'Unknown WinguFi Core API error';

        $safeBody = $body;
        foreach (['password', 'radius_secret'] as $sensitiveKey) {
            if (isset($safeBody[$sensitiveKey])) {
                $safeBody[$sensitiveKey] = '***';
            }
        }

        Log::error('WinguFi Core API request failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $safeBody,
        ]);

        throw new \RuntimeException("WinguFi Core API error ({$response->status()}): {$message}");
    }

    private function redactSensitive(array $payload): array
    {
        $redacted = $payload;

        foreach (['password', 'radius_password', 'radius_secret', 'token', 'access_token'] as $key) {
            if (isset($redacted[$key])) {
                $redacted[$key] = '***';
            }

            if (isset($redacted['data'][$key])) {
                $redacted['data'][$key] = '***';
            }
        }

        return $redacted;
    }

    private function packageValiditySeconds(Package $package): ?int
    {
        if ($package->validity_minutes) {
            return $package->validity_minutes * 60;
        }

        if ($package->session_timeout) {
            return $package->session_timeout * 3600;
        }

        if ($package->validity_days) {
            return $package->validity_days * 86400;
        }

        return null;
    }

    private function dataLimitBytes(Package $package): ?int
    {
        if (!$package->rate_limit) {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(mb|gb|tb)/i', $package->rate_limit, $matches)) {
            $value = (float) $matches[1];
            $unit = strtolower($matches[2]);

            return (int) round($value * match ($unit) {
                'mb' => 1024 * 1024,
                'gb' => 1024 * 1024 * 1024,
                'tb' => 1024 * 1024 * 1024 * 1024,
            });
        }

        return null;
    }
}
