<?php

namespace Database\Seeders;

use App\Models\NetworkPackage;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class NetworkPackageSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'DEV001')->first();

        NetworkPackage::create([
            'tenant_id' => $tenant->id,
            'name' => '1 Day Unlimited',
            'code' => '1DAY-UNL',
            'description' => 'Unlimited data for 1 day',
            'status' => 'active',
            'download_speed' => 10000,
            'upload_speed' => 5000,
            'session_timeout' => 86400,
            'validity_seconds' => 86400,
            'data_limit_bytes' => null,
            'simultaneous_sessions' => 1,
            'price' => 50.00,
            'currency' => 'KES',
        ]);

        NetworkPackage::create([
            'tenant_id' => $tenant->id,
            'name' => '1 Week 10GB',
            'code' => '1WK-10GB',
            'description' => '10GB data for 1 week',
            'status' => 'active',
            'download_speed' => 20000,
            'upload_speed' => 10000,
            'session_timeout' => 604800,
            'validity_seconds' => 604800,
            'data_limit_bytes' => 10737418240, // 10GB
            'simultaneous_sessions' => 1,
            'price' => 300.00,
            'currency' => 'KES',
        ]);
    }
}
