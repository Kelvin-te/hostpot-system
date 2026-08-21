<?php

namespace Database\Seeders;

use App\Models\RadiusNas;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RadiusNasSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'DEV001')->first();

        RadiusNas::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Router',
            'nasname' => '192.168.1.1',
            'shortname' => 'test-router',
            'type' => 'mikrotik',
            'identifier' => 'test-router-001',
            'description' => 'Development test router - DEV ONLY',
            'status' => 'active',
            'radius_secret_encrypted' => bcrypt('dev_secret'), // DEV ONLY - NOT FOR PRODUCTION
            'auth_port' => 1812,
            'acct_port' => 1813,
            'management_ip' => '192.168.1.1',
        ]);
    }
}
