<?php

namespace Database\Seeders;

use App\Models\NetworkAuthorization;
use App\Models\NetworkClient;
use App\Models\NetworkPackage;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class NetworkAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'DEV001')->first();
        $client = NetworkClient::where('username', 'test-user')->first();
        $package = NetworkPackage::where('code', '1DAY-UNL')->first();

        NetworkAuthorization::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'package_id' => $package->id,
            'source_type' => 'manual',
            'source_id' => 'dev-seeder-001',
            'username' => 'test-user',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDay(),
            'session_timeout' => 86400,
            'download_speed' => 10000,
            'upload_speed' => 5000,
            'data_limit_bytes' => null,
            'data_used_bytes' => 0,
            'simultaneous_sessions' => 1,
            'source_system' => 'dev_seeder',
        ]);
    }
}
