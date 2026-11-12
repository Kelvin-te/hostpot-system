<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PppoeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforcePppoeExpiry extends Command
{
    protected $signature = 'pppoe:enforce-expiry';
    protected $description = 'Suspend PPPoE subscribers whose package validity has expired';

    public function handle(PppoeService $pppoe): int
    {
        $suspended = 0;

        User::where('connection_type', 'pppoe')
            ->whereNotNull('pppoe_username')
            ->where('status', 'active')
            ->with(['detail', 'package'])
            ->chunkById(100, function ($users) use ($pppoe, &$suspended) {
                foreach ($users as $user) {
                    $validity = $user->package?->validity_minutes;

                    // No validity = unlimited/unmetered package, never expires
                    if (!$validity || !$user->detail?->package_start) {
                        continue;
                    }

                    $expiresAt = $user->detail->package_start->copy()->addMinutes($validity);

                    if ($expiresAt->isFuture()) {
                        continue;
                    }

                    if ($pppoe->suspend($user)) {
                        $user->detail->update(['status' => 'expired']);
                        $suspended++;

                        Log::info('PPPoE subscriber suspended for expiry', [
                            'user_id' => $user->id,
                            'pppoe_username' => $user->pppoe_username,
                            'expired_at' => $expiresAt->toDateTimeString(),
                        ]);
                    }
                }
            });

        $this->info("Suspended {$suspended} expired PPPoE subscriber(s).");

        return self::SUCCESS;
    }
}
