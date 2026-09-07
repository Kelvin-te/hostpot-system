<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Router>
 */
class RouterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company() . ' Router',
            'identifier' => fake()->unique()->uuid(),
            'location' => fake()->city(),
            'ip' => fake()->ipv4(),
            'ip_address' => fake()->ipv4(),
            'username' => 'admin',
            'password' => 'password',
            'api_port' => 8728,
            'hotspot_enabled' => true,
            'hotspot_interface' => 'wlan1',
            'hotspot_server_ip' => fake()->ipv4(),
            'is_active' => true,
        ];
    }
}
