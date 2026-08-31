<?php

namespace App\Services;

use App\Models\HotspotAuthorization;
use App\Models\Package;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotspotAuthorizationService
{
    public function __construct(
        protected WinguFiCoreService $winguFiCore
    ) {}

    /**
     * Create or retrieve an existing authorization from package purchase and sync to WinguFi Core
     */
    public function createFromPackage(
        Package $package,
        ?User $user = null,
        ?string $clientIdentifier = null,
        ?string $clientMac = null,
        ?int $paymentTransactionId = null
    ): HotspotAuthorization {
        // Free-package idempotency: reuse an existing active authorization for
        // this device+package instead of creating a duplicate. Expired
        // authorizations (time-based) and revoked ones (status-based) are
        // excluded by scopeActive(), so they naturally fall through to create
        // a new authorization below rather than being silently reused.
        if (!$paymentTransactionId && $clientIdentifier) {
            $existing = $this->getActiveAuthorization($clientIdentifier, $package->id);

            if ($existing) {
                return $this->ensureSynced($existing);
            }
        }

        $radiusCredentials = $this->generateRadiusCredentials($clientIdentifier);

        $attributes = [
            'router_id' => $package->router_id,
            'package_id' => $package->id,
            'user_id' => $user?->id,
            'client_identifier' => $clientIdentifier,
            'radius_username' => $radiusCredentials['username'],
            'radius_password_encrypted' => $radiusCredentials['password_encrypted'],
            'client_mac' => $clientMac,
            'payment_transaction_id' => $paymentTransactionId,
            'status' => 'authorized',
            'authorized_at' => now(),
            'starts_at' => now(),
            'expires_at' => $this->calculateExpiry($package),
            'session_timeout' => $package->getSessionTimeoutSeconds(),
            'idle_timeout' => $package->idle_timeout ? $package->idle_timeout * 60 : null,
            'rate_limit' => $package->rate_limit,
            'simultaneous_sessions' => $package->shared_users ?? 1,
            'authorization_attributes' => $this->buildAuthorizationAttributes($package),
        ];

        if ($paymentTransactionId) {
            $key = $this->winguFiCore->externalAuthorizationIdForPayment($paymentTransactionId);

            $authorization = HotspotAuthorization::firstOrCreate(
                ['payment_transaction_id' => $paymentTransactionId, 'package_id' => $package->id],
                array_merge($attributes, ['authorization_key' => $key])
            );

            return $this->ensureSynced($authorization);
        }

        $authorization = HotspotAuthorization::create(array_merge($attributes, [
            'authorization_key' => $this->generateAuthorizationKey(),
        ]));

        return $this->ensureSynced($authorization);
    }

    /**
     * Create authorization from voucher
     */
    public function createFromVoucher(
        Voucher $voucher,
        ?string $clientMac = null
    ): HotspotAuthorization {
        $radiusCredentials = $this->generateRadiusCredentials($voucher->code);

        $authorization = HotspotAuthorization::create([
            'authorization_key' => $this->generateAuthorizationKey(),
            'router_id' => $voucher->package->router_id,
            'package_id' => $voucher->package_id,
            'voucher_id' => $voucher->id,
            'client_identifier' => $voucher->code,
            'radius_username' => $radiusCredentials['username'],
            'radius_password_encrypted' => $radiusCredentials['password_encrypted'],
            'client_mac' => $clientMac,
            'status' => 'authorized',
            'authorized_at' => now(),
            'starts_at' => now(),
            'expires_at' => $this->calculateExpiry($voucher->package),
            'session_timeout' => $voucher->package->session_timeout ? $voucher->package->session_timeout * 3600 : null,
            'idle_timeout' => $voucher->package->idle_timeout ? $voucher->package->idle_timeout * 60 : null,
            'rate_limit' => $voucher->package->rate_limit,
            'simultaneous_sessions' => $voucher->package->shared_users ?? 1,
            'authorization_attributes' => $this->buildAuthorizationAttributes($voucher->package),
        ]);

        return $this->ensureSynced($authorization);
    }

    /**
     * Ensure a local authorization is synchronized to WinguFi Core
     */
    public function ensureSynced(HotspotAuthorization $authorization): HotspotAuthorization
    {
        if ($authorization->wingufi_core_authorization_id) {
            return $authorization;
        }

        try {
            $result = $this->winguFiCore->syncAuthorization($authorization);

            $authorization->update([
                'wingufi_core_authorization_id' => $result['data']['id'] ?? $result['data']['external_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('WinguFi Core authorization sync failed', [
                'authorization_id' => $authorization->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $authorization;
    }

    /**
     * Revoke authorization
     */
    public function revoke(HotspotAuthorization $authorization, string $reason): void
    {
        $authorization->update([
            'status' => 'revoked',
            'revoke_reason' => $reason,
        ]);
    }

    /**
     * Activate authorization
     */
    public function activate(HotspotAuthorization $authorization): void
    {
        $authorization->update([
            'status' => 'active',
            'starts_at' => now(),
        ]);
    }

    /**
     * Check if authorization is valid for use
     */
    public function isValid(HotspotAuthorization $authorization): bool
    {
        return $authorization->isActive() &&
            $authorization->simultaneous_sessions > 0;
    }

    /**
     * Get active authorization for client, optionally scoped to a package.
     */
    public function getActiveAuthorization(string $clientIdentifier, ?int $packageId = null): ?HotspotAuthorization
    {
        $query = HotspotAuthorization::active()
            ->where('client_identifier', $clientIdentifier);

        if ($packageId !== null) {
            $query->where('package_id', $packageId);
        }

        return $query->first();
    }

    /**
     * Generate a RADIUS username/password pair for the captive-portal handoff.
     * The password is encrypted before storage.
     */
    private function generateRadiusCredentials(?string $clientIdentifier): array
    {
        $username = $clientIdentifier ?: 'guest-' . Str::random(16);
        $password = Str::random(16);

        return [
            'username' => $username,
            'password' => $password,
            'password_encrypted' => Crypt::encryptString($password),
        ];
    }

    /**
     * Calculate expiry time based on package.
     *
     * Mirrors the priority used by WinguFiCoreService::packageValiditySeconds
     * so the local authorization expiry stays consistent with the Core.
     */
    private function calculateExpiry(Package $package): ?\Carbon\CarbonInterface
    {
        if ($package->validity_minutes) {
            return now()->addMinutes($package->validity_minutes);
        }

        if ($package->session_timeout) {
            return now()->addHours($package->session_timeout);
        }

        if ($package->validity_days) {
            return now()->addDays($package->validity_days);
        }

        return null;
    }

    /**
     * Build authorization attributes for RADIUS
     */
    private function buildAuthorizationAttributes(Package $package): array
    {
        $attributes = [];

        if ($package->bandwidth_upload) {
            $attributes['WISPr-Bandwidth-Max-Up'] = $package->bandwidth_upload * 1024 * 1024;
        }

        if ($package->bandwidth_download) {
            $attributes['WISPr-Bandwidth-Max-Down'] = $package->bandwidth_download * 1024 * 1024;
        }

        $sessionTimeout = $package->getSessionTimeoutSeconds();
        if ($sessionTimeout) {
            $attributes['Session-Timeout'] = $sessionTimeout;
        }

        if ($package->idle_timeout) {
            $attributes['Idle-Timeout'] = $package->idle_timeout * 60;
        }

        if ($package->rate_limit) {
            $attributes['Framed-Filter-Id'] = $package->rate_limit;
        }

        return $attributes;
    }

    /**
     * Generate unique authorization key
     */
    private function generateAuthorizationKey(): string
    {
        do {
            $key = 'auth_' . Str::random(32);
        } while (HotspotAuthorization::where('authorization_key', $key)->exists());

        return $key;
    }
}
