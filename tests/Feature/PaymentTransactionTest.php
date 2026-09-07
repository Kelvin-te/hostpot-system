<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PaymentTransaction::query()->delete();
    }

    public function test_gateway_scope_filters_by_gateway(): void
    {
        PaymentTransaction::factory()->create(['gateway' => 'mpesa', 'status' => 'completed']);
        PaymentTransaction::factory()->create(['gateway' => 'paystack', 'status' => 'completed']);
        PaymentTransaction::factory()->create(['gateway' => 'manual', 'status' => 'completed']);

        $this->assertEquals(1, PaymentTransaction::gateway('mpesa')->count());
        $this->assertEquals(1, PaymentTransaction::gateway('paystack')->count());
        $this->assertEquals(1, PaymentTransaction::gateway('manual')->count());
    }

    public function test_type_scope_filters_by_type(): void
    {
        PaymentTransaction::factory()->create(['type' => 'one_time']);
        PaymentTransaction::factory()->create(['type' => 'subscription']);
        PaymentTransaction::factory()->create(['type' => 'voucher']);

        $this->assertEquals(1, PaymentTransaction::ofType('one_time')->count());
        $this->assertEquals(1, PaymentTransaction::ofType('subscription')->count());
        $this->assertEquals(1, PaymentTransaction::ofType('voucher')->count());
    }

    public function test_for_router_scope_filters_by_router(): void
    {
        $router1 = Router::factory()->create();
        $router2 = Router::factory()->create();

        PaymentTransaction::factory()->create(['router_id' => $router1->id]);
        PaymentTransaction::factory()->create(['router_id' => $router2->id]);
        PaymentTransaction::factory()->create(['router_id' => null]);

        $this->assertEquals(1, PaymentTransaction::forRouter($router1->id)->count());
        $this->assertEquals(1, PaymentTransaction::forRouter($router2->id)->count());
    }

    public function test_is_refundable_returns_true_for_recent_completed(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHours(12),
        ]);

        $this->assertTrue($transaction->isRefundable());
    }

    public function test_is_refundable_returns_false_for_old_completed(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        $this->assertFalse($transaction->isRefundable());
    }

    public function test_is_refundable_returns_false_for_pending(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'pending',
        ]);

        $this->assertFalse($transaction->isRefundable());
    }

    public function test_router_relation_works(): void
    {
        $router = Router::factory()->create();
        $transaction = PaymentTransaction::factory()->create(['router_id' => $router->id]);

        $this->assertEquals($router->id, $transaction->router->id);
    }

    public function test_router_relation_returns_null_when_not_set(): void
    {
        $transaction = PaymentTransaction::factory()->create(['router_id' => null]);

        $this->assertNull($transaction->router);
    }

    public function test_default_gateway_is_mpesa(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $this->assertEquals('mpesa', $transaction->gateway);
    }

    public function test_default_type_is_one_time(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $this->assertEquals('one_time', $transaction->type);
    }

    public function test_user_transactions_relation(): void
    {
        $user = User::factory()->create();
        PaymentTransaction::factory()->create(['user_id' => $user->id]);
        PaymentTransaction::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->transactions->count());
    }
}
