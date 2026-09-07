<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Staff::firstOrCreate(
            ['email' => 'admin@sterkedigital.com'],
            [
                'name' => 'System Administrator',
                'phone' => '0700000000',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $timezones = [
            'GMT',
            'Etc/GMT+12',
            'Etc/GMT+11',
            'Pacific/Apia',
            'Pacific/Midway',
            'Pacific/Honolulu',
            'America/Juneau',
            'America/Los_Angeles',
            'America/Denver',
            'America/Chicago',
            'America/New_York',
            'America/Argentina/Buenos_Aires',
            'America/Sao_Paulo',
            'Atlantic/Cape_Verde',
            'Europe/London',
            'Europe/Paris',
            'Europe/Istanbul',
            'Africa/Lagos',
            'Asia/Dubai',
            'Asia/Kolkata',
            'Asia/Dhaka',
            'Asia/Jakarta',
            'Asia/Tokyo',
            'Australia/Sydney',
            'Pacific/Auckland',
        ];

        foreach ($timezones as $timezone) {
            DB::table('time_zones')->insertOrIgnore([
                'timezone' => $timezone,
                'created_at' => now(),
            ]);
        }
    }
}
