<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTenantCredential extends Command
{
    protected $signature = 'wingufi:credential:create
                            {tenant : The ID of the tenant}
                            {name? : A descriptive name for the credential}';

    protected $description = 'Create a new opaque bearer token credential for a tenant. The token is shown only once.';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');
        $name = $this->argument('name') ?? 'API Credential';

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error('Tenant not found.');
            return self::FAILURE;
        }

        $clientId = 'wc_'.Str::random(24);
        $secret = Str::random(64);
        $plainTextToken = $clientId.':'.$secret;

        TenantCredential::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'client_id' => $clientId,
            'client_secret_hash' => Hash::make($secret),
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        $this->warn('=== BEARER TOKEN (shown only once) ===');
        $this->line($plainTextToken);
        $this->warn('=== Copy this token now. It cannot be recovered. ===');

        return self::SUCCESS;
    }
}
