<?php

namespace App\Services;

use App\Models\HotspotSession;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Credit wallet and record transaction.
     */
    public function creditWallet(User $user, float $amount, string $description, ?string $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $reference) {
            $user->refresh();
            $newBalance = (float) $user->wallet_balance + $amount;
            $user->update(['wallet_balance' => $newBalance]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'credit',
                'description' => $description,
                'reference' => $reference ?? Str::uuid()->toString(),
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Debit wallet and record transaction.
     */
    public function debitWallet(User $user, float $amount, string $description): WalletTransaction
    {
        if (!$this->hasSufficientBalance($user, $amount)) {
            throw new \RuntimeException('Insufficient wallet balance');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            $user->refresh();
            $newBalance = (float) $user->wallet_balance - $amount;
            $user->update(['wallet_balance' => $newBalance]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'debit',
                'description' => $description,
                'reference' => Str::uuid()->toString(),
                'balance_after' => $newBalance,
            ]);
        });
    }

    public function getBalance(User $user): float
    {
        return (float) $user->wallet_balance;
    }

    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return (float) $user->wallet_balance >= $amount;
    }

    /**
     * Check if wallet has enough for the user's current/last package and auto-purchase.
     */
    public function autoRenewIfSufficient(User $user): bool
    {
        $lastSession = $user->sessions()
            ->latest('created_at')
            ->with('package')
            ->first();

        if (!$lastSession || !$lastSession->package) {
            return false;
        }

        $package = $lastSession->package;
        $price = (float) $package->price;

        if (!$this->hasSufficientBalance($user, $price)) {
            return false;
        }

        try {
            DB::transaction(function () use ($user, $package, $price, $lastSession) {
                $this->debitWallet($user, $price, "Auto-renewal: {$package->name}");

                $paymentTxn = PaymentTransaction::create([
                    'checkout_request_id' => 'wallet_' . Str::uuid()->toString(),
                    'phone_number' => $user->phone ?? '',
                    'amount' => $price,
                    'account_reference' => $user->phone ?? $user->id,
                    'transaction_desc' => "Wallet auto-renewal: {$package->name}",
                    'status' => 'completed',
                    'gateway' => 'wallet',
                    'type' => 'subscription',
                    'package_id' => $package->id,
                    'user_id' => $user->id,
                    'router_id' => $package->router_id,
                ]);

                $expiresAt = now()->addMinutes($package->validity_minutes ?? 1440);

                HotspotSession::create([
                    'session_id' => 'hs_' . bin2hex(random_bytes(16)),
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'started_at' => now(),
                    'expires_at' => $expiresAt,
                    'status' => 'active',
                    'mac_address' => $lastSession->mac_address,
                    'ip_address' => $lastSession->ip_address,
                ]);
            });

            Log::info("Wallet auto-renewal succeeded for user {$user->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Wallet auto-renewal failed for user {$user->id}: {$e->getMessage()}");
            return false;
        }
    }
}
