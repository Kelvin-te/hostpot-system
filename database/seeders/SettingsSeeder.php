<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default settings if none exist
        Setting::firstOrCreate([
            'id' => 1
        ], [
            'company_name' => config('app.name', 'Hotspot Admin'),
            'app_name' => config('app.name', 'Hotspot Admin'),
            'timezone' => config('app.timezone', 'UTC'),
            'currency' => 'KES',
            'walled_garden_enabled' => true,
            'bill_at' => 1,
            'disconnect_at' => 5,
            'billing_alerts_enabled' => true,
            'expiry_warnings_enabled' => true,
            'anti_abuse_enabled' => true,
            'payment_timeout_minutes' => 15,
            'password_min_length' => 8,
            'password_require_uppercase' => true,
            'password_require_number' => true,
            'session_timeout_minutes' => 60,
            'free_package_data_limit_mb' => 500,
            'free_package_validity_hours' => 24,
            'invoice_prefix' => 'INV-',
            'payment_terms' => 'Net 30',
            'backup_schedule' => 'daily',
            'backup_retention_days' => 30,
        ]);
    }
}
