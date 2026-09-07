<?php

namespace Tests\Feature;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Router;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_wallet_balance_default(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0, (float) $user->wallet_balance);
        $this->assertEquals('active', $user->status);
    }

    public function test_user_sessions_relation(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        HotspotSession::create([
            'session_id' => 'hs_rel_1' . uniqid(),
            'user_id' => $user->id,
            'package_id' => $package->id,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'status' => 'active',
        ]);

        $this->assertEquals(1, $user->sessions()->count());
    }

    public function test_user_router_relation(): void
    {
        $router = Router::factory()->create();
        $user = User::factory()->create(['router_id' => $router->id]);

        $this->assertEquals($router->id, $user->router->id);
    }

    public function test_user_wallet_transactions_relation(): void
    {
        $user = User::factory()->create(['wallet_balance' => 500]);

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 500.00,
            'type' => 'credit',
            'description' => 'Initial top-up',
            'balance_after' => 500.00,
        ]);

        $this->assertEquals(1, $user->walletTransactions()->count());
    }

    public function test_user_scope_active(): void
    {
        User::query()->delete();

        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'suspended']);
        User::factory()->create(['status' => 'banned']);

        $this->assertEquals(1, User::active()->count());
    }

    public function test_user_is_suspended(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $this->assertTrue($user->isSuspended());
        $this->assertFalse($user->isBanned());
    }

    public function test_user_is_banned(): void
    {
        $user = User::factory()->create(['status' => 'banned']);

        $this->assertTrue($user->isBanned());
        $this->assertFalse($user->isSuspended());
    }
}
