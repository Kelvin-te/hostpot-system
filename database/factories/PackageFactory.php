<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Daily', 'Weekly', 'Monthly', 'Hourly']) . ' ' . $this->faker->numberBetween(1, 100),
            'price' => $this->faker->numberBetween(10, 500),
            'router_id' => Router::factory(),
            'bandwidth_upload' => $this->faker->optional()->randomFloat(2, 1, 10),
            'bandwidth_download' => $this->faker->optional()->randomFloat(2, 5, 50),
            'session_timeout' => $this->faker->optional()->numberBetween(1, 720),
            'idle_timeout' => $this->faker->optional()->numberBetween(5, 60),
            'shared_users' => 1,
            'data_cap' => null,
            'validity_minutes' => $this->faker->optional()->numberBetween(60, 43200),
            'is_active' => true,
        ];
    }
}
