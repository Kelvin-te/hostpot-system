<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'uuid' => Str::uuid(),
            'name' => 'Development Company',
            'slug' => 'dev-company',
            'code' => 'DEV001',
            'status' => 'active',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'contact_email' => 'dev@example.com',
            'contact_phone' => '+254700000000',
        ]);
    }
}
