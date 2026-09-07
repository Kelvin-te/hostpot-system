<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\Package;
use App\Models\User;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkout_request_id' => Str::uuid()->toString(),
            'merchant_request_id' => $this->faker->optional()->uuid(),
            'phone_number' => '07' . $this->faker->numerify('########'),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'account_reference' => $this->faker->word(),
            'transaction_desc' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'gateway' => 'mpesa',
            'type' => 'one_time',
            'mpesa_receipt_number' => $this->faker->optional()->bothify('??*####'),
            'transaction_date' => $this->faker->optional()->dateTime(),
            'package_id' => Package::factory(),
            'user_id' => null,
            'session_id' => null,
            'voucher_id' => null,
            'router_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'mpesa_receipt_number' => strtoupper($this->faker->bothify('??*####')),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'result_description' => 'Insufficient funds',
        ]);
    }
}
