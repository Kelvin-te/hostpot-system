<?php

namespace App\Console\Commands;

use App\Models\HotspotSession;
use App\Services\VintexSmsService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAutoRenewals extends Command
{
    protected $signature = 'wallet:check-auto-renewals';
    protected $description = 'Check sessions expiring soon and auto-renew from wallet if sufficient';

    public function handle(WalletService $wallet, VintexSmsService $sms): int
    {
        $sessions = HotspotSession::active()
            ->where('expires_at', '<=', now()->addMinutes(15))
            ->where('expires_at', '>', now())
            ->with(['user', 'package'])
            ->get();

        $renewed = 0;
        $alerted = 0;

        foreach ($sessions as $session) {
            $user = $session->user;
            if (!$user || !$session->package) {
                continue;
            }

            $price = (float) $session->package->price;

            if ($wallet->hasSufficientBalance($user, $price)) {
                $result = $wallet->autoRenewIfSufficient($user);
                if ($result) {
                    $renewed++;
                    if ($user->phone) {
                        $newExpiry = now()->addMinutes($session->package->validity_minutes ?? 1440)->format('M d, H:i');
                        $sms->sendAutoRenewalSms($user->phone, $session->package->name, $newExpiry);
                    }
                }
            } else {
                $alerted++;
                if ($user->phone) {
                    $balance = number_format((float) $user->wallet_balance, 2);
                    $sms->sendWalletLowBalanceSms($user->phone, $balance, $session->package->name);
                }
            }
        }

        $this->info("Auto-renewed: {$renewed} | Low balance alerts: {$alerted}");
        Log::info("CheckAutoRenewals: renewed={$renewed}, alerted={$alerted}");

        return self::SUCCESS;
    }
}
