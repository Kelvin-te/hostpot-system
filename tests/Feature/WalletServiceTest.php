<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_wallet_increases_balance(): void
    {
        $user = User::factory()->create(['wallet_balance' => 0]);

        $walletService = app(WalletService::class);
        $walletService->creditWallet($user, 500.00, 'Test top-up');

        $user->refresh();
        $this->assertEquals(500.00, (float) $user->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 500.00,
            'type' => 'credit',
            'balance_after' => 500.00,
        ]);
    }

    public function test_debit_wallet_decreases_balance(): void
    {
        $user = User::factory()->create(['wallet_balance' => 1000.00]);

        $walletService = app(WalletService::class);
        $walletService->debitWallet($user, 300.00, 'Test debit');

        $user->refresh();
        $this->assertEquals(700.00, (float) $user->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 300.00,
            'type' => 'debit',
            'balance_after' => 700.00,
        ]);
    }

    public function test_debit_wallet_throws_when_insufficient(): void
    {
        $user = User::factory()->create(['wallet_balance' => 100.00]);

        $walletService = app(WalletService::class);

        $this->expectException(\RuntimeException::class);
        $walletService->debitWallet($user, 200.00, 'Overdraft');
    }

    public function test_has_sufficient_balance(): void
    {
        $user = User::factory()->create(['wallet_balance' => 500.00]);
        $walletService = app(WalletService::class);

        $this->assertTrue($walletService->hasSufficientBalance($user, 500.00));
        $this->assertTrue($walletService->hasSufficientBalance($user, 100.00));
        $this->assertFalse($walletService->hasSufficientBalance($user, 501.00));
    }

    public function test_multiple_credits_accumulate(): void
    {
        $user = User::factory()->create(['wallet_balance' => 0]);
        $walletService = app(WalletService::class);

        $walletService->creditWallet($user, 200.00, 'First');
        $walletService->creditWallet($user, 300.00, 'Second');

        $user->refresh();
        $this->assertEquals(500.00, (float) $user->wallet_balance);
        $this->assertEquals(2, WalletTransaction::where('user_id', $user->id)->count());
    }
}
