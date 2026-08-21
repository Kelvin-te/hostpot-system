<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        Staff::firstOrCreate(
            ['email' => 'admin@wingufi.net'],
            [
                'name' => 'System Administrator',
                'phone' => '0700000000',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
