<?php

namespace App\Console\Commands;

use App\Models\HotspotSession;
use App\Services\SessionExpiryService;
use App\Services\VintexSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendExpiryAlerts extends Command
{
    protected $signature = 'session:send-expiry-alerts';
    protected $description = 'Send SMS alerts for sessions expiring soon (15min, 5min, 1min)';

    public function handle(SessionExpiryService $service, VintexSmsService $sms): int
    {
        $portalUrl = config('app.portal_url', url('/portal'));
        $alertsSent = 0;

        foreach ([15, 5, 1] as $minutes) {
            $sessions = HotspotSession::active()
                ->whereBetween('expires_at', [
                    now()->addMinutes($minutes - 1),
                    now()->addMinutes($minutes),
                ])
                ->with(['user', 'package'])
                ->get();

            foreach ($sessions as $session) {
                $phone = $session->user?->phone ?? $session->username;
                if (!$phone) {
                    continue;
                }

                $packageName = $session->package?->name ?? 'your package';

                $result = $sms->sendExpiryAlertSms($phone, $packageName, $minutes, $portalUrl);

                if ($result['success'] ?? false) {
                    $alertsSent++;
                    Log::info("Expiry alert sent: {$minutes}min to {$phone}", [
                        'session_id' => $session->session_id,
                    ]);
                }
            }
        }

        $this->info("Expiry alerts sent: {$alertsSent}");

        return self::SUCCESS;
    }
}
