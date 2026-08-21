<?php

namespace Database\Seeders;

use App\Models\NetworkClient;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NetworkClientSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'DEV001')->first();

        NetworkClient::create([
            'tenant_id' => $tenant->id,
            'uuid' => Str::uuid(),
            'username' => 'test-user',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+254700000000',
            'status' => 'active',
            'password_hash' => bcrypt('password123'), // DEV ONLY - NOT FOR PRODUCTION
            'password_type' => 'bcrypt',
            'mac_address' => '00:11:22:33:44:55',
            'source_system' => 'dev_seeder',
        ]);
    }
}
