<?php

namespace App\Services;

use App\Models\HotspotAuthorization;
use App\Models\Package;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class HotspotAuthorizationService
{
    /**
     * Create or retrieve an existing authorization from package purchase.
     * No external core sync is performed; local MikroTik hotspot users are the
     * source of truth for the API-only flow.
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
                return $existing;
            }
        }

        $hotspotCredentials = $this->generateHotspotCredentials($clientIdentifier);

        $attributes = [
            'router_id' => $package->router_id,
            'package_id' => $package->id,
            'user_id' => $user?->id,
            'client_identifier' => $clientIdentifier,
            'hotspot_username' => $hotspotCredentials['username'],
            'hotspot_password_encrypted' => $hotspotCredentials['password_encrypted'],
            'client_mac' => $clientMac,
            'payment_transaction_id' => $paymentTransactionId,
            'status' => 'authorized',
            'authorized_at' => now(),
            'starts_at' => now(),
            'expires_at' => $this->calculateExpiry($package),
            'session_timeout' => $package->getSessionTimeoutSeconds(),
            'idle_timeout' => $package->idle_timeout ? $package->idle_timeout * 60 : null,
            'data_cap' => $package->data_cap,
            'simultaneous_sessions' => $package->shared_users ?? 1,
            'authorization_attributes' => $this->buildAuthorizationAttributes($package),
        ];

        if ($paymentTransactionId) {
            $authorization = HotspotAuthorization::firstOrCreate(
                ['payment_transaction_id' => $paymentTransactionId, 'package_id' => $package->id],
                array_merge($attributes, ['authorization_key' => $this->generateAuthorizationKey()])
            );

            return $authorization;
        }

        $authorization = HotspotAuthorization::create(array_merge($attributes, [
            'authorization_key' => $this->generateAuthorizationKey(),
        ]));

        return $authorization;
    }

    /**
     * Create authorization from voucher
     */
    public function createFromVoucher(
        Voucher $voucher,
        ?string $clientMac = null,
        ?Package $package = null
    ): HotspotAuthorization {
        $targetPackage = $package ?? $voucher->package;
        $password = Str::random(16);

        $authorization = HotspotAuthorization::create([
            'authorization_key' => $this->generateAuthorizationKey(),
            'router_id' => $targetPackage->router_id,
            'package_id' => $targetPackage->id,
            'voucher_id' => $voucher->id,
            'client_identifier' => $voucher->code,
            'hotspot_username' => substr($voucher->code, 0, 60),
            'hotspot_password_encrypted' => Crypt::encryptString($password),
            'client_mac' => $clientMac,
            'status' => 'authorized',
            'authorized_at' => now(),
            'starts_at' => now(),
            'expires_at' => $this->calculateExpiry($targetPackage),
            'session_timeout' => $targetPackage->session_timeout ? $targetPackage->session_timeout * 3600 : null,
            'idle_timeout' => $targetPackage->idle_timeout ? $targetPackage->idle_timeout * 60 : null,
            'data_cap' => $targetPackage->data_cap,
            'simultaneous_sessions' => $targetPackage->shared_users ?? 1,
            'authorization_attributes' => $this->buildAuthorizationAttributes($targetPackage),
        ]);

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
     * Generate a hotspot username/password pair for the captive-portal handoff.
     * The password is encrypted before storage.
     */
    private function generateHotspotCredentials(?string $clientIdentifier): array
    {
        if ($clientIdentifier) {
            $username = 'u-' . substr(hash('sha256', $clientIdentifier), 0, 6);
        } else {
            $username = 'g-' . Str::random(6);
        }

        $password = Str::random(16);

        return [
            'username' => $username,
            'password' => $password,
            'password_encrypted' => Crypt::encryptString($password),
        ];
    }

    /**
     * Calculate expiry time based on package.
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

        // Default to 24 hours — must never return null, otherwise
        // sessions won't appear as active in the admin portal.
        return now()->addDay();
    }

    /**
     * Build authorization attributes for the hotspot profile
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
