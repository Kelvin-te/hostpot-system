<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantCredentialSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'DEV001')->first();

        TenantCredential::create([
            'tenant_id' => $tenant->id,
            'name' => 'Development API Key',
            'client_id' => 'dev_client_' . time(),
            'client_secret_hash' => Hash::make('dev_secret_' . time()), // DEV ONLY - NOT FOR PRODUCTION
            'status' => 'active',
        ]);
    }
}
