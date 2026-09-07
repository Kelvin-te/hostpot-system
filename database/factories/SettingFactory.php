<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_name' => 'MatuNet',
            'company_name' => 'MatuNet',
            'company_phone' => '0712 345 678',
            'company_email' => 'info@matunet.net',
            'company_address' => 'Machakos, Kenya',
            'sms_sender_id' => null,
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
        ];
    }
}
