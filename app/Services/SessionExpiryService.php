<?php

namespace App\Services;

use App\Models\HotspotSession;
use Illuminate\Support\Facades\Log;

class SessionExpiryService
{
    protected int $maxRetries = 5;

    public function __construct(
        protected MikroTikService $mikrotikService
    ) {}

    /**
     * Find sessions expiring within the given minutes window.
     */
    public function checkExpiringSessions(int $withinMinutes = 15): \Illuminate\Support\Collection
    {
        return HotspotSession::active()
            ->where('expires_at', '<=', now()->addMinutes($withinMinutes))
            ->get();
    }

    /**
     * Enforce time-based expiry and data limits in a single pass.
     *
     * Handles 'active' sessions whose expires_at has passed, plus 'active'
     * sessions that have exceeded their data cap. Includes retry logic (up to
     * maxRetries) for router disconnect failures.
     */
    public function enforceAll(): array
    {
        $expiredCount = 0;
        $dataLimitedCount = 0;
        $skippedCount = 0;

        // --- Time-based expiry: only active sessions past expires_at ---
        $expired = HotspotSession::query()
            ->where('expires_at', '<=', now())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('mikrotik_data->disconnected_at')
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('mikrotik_data->disconnect_attempts')
                            ->orWhere('mikrotik_data->disconnect_attempts', '<', $this->maxRetries);
                    });
            })
            ->get();

        foreach ($expired as $session) {
            $result = $this->disconnectSession($session);
            if ($result === 'skipped') {
                $skippedCount++;
            } else {
                $expiredCount++;
            }
        }

        // --- Data-limit expiry: active sessions that still have time but exceeded data cap ---
        $dataSessions = HotspotSession::active()->with('package')->get();

        foreach ($dataSessions as $session) {
            $remaining = $session->getRemainingData();
            if ($remaining !== null && $remaining <= 0) {
                $result = $this->disconnectSession($session);
                if ($result === 'skipped') {
                    $skippedCount++;
                } else {
                    $dataLimitedCount++;
                }
            }
        }

        $total = $expiredCount + $dataLimitedCount;
        if ($total > 0 || $skippedCount > 0) {
            Log::info("SessionExpiryService: enforced expiry on {$expiredCount} sessions, data limit on {$dataLimitedCount} sessions, skipped {$skippedCount} (not logged in)");
        }

        return [
            'expired' => $expiredCount,
            'data_limited' => $dataLimitedCount,
            'skipped' => $skippedCount,
        ];
    }

    /**
     * Disconnect a session: attempt router disconnect, then update status.
     * Returns 'disconnected', 'failed', or 'skipped'.
     */
    public function disconnectSession(HotspotSession $session): string
    {
        // Skip sessions where the user never actually connected through
        // the captive portal. The hotspot user exists on the router but
        // was never used — removing it would prevent the user from
        // logging in later.
        $hasUsedData = ($session->bytes_total ?? 0) > 0
            || !empty($session->mikrotik_data['uptime_seconds']);

        if (!$hasUsedData) {
            return 'skipped';
        }

        $data = $session->mikrotik_data ?? [];
        $attempts = $data['disconnect_attempts'] ?? 0;

        try {
            if ($session->package && $session->package->router) {
                $success = $this->mikrotikService->disconnectUser($session);
            } else {
                $success = true;
            }

            if ($success) {
                $data['disconnected_at'] = now()->toIso8601String();
            } else {
                $data['disconnect_attempts'] = $attempts + 1;
                $data['last_disconnect_attempt_at'] = now()->toIso8601String();
                Log::warning('SessionExpiryService: router disconnect failed, will retry', [
                    'session_id' => $session->session_id,
                    'attempts' => $attempts + 1,
                ]);
            }
        } catch (\Exception $e) {
            $data['disconnect_attempts'] = $attempts + 1;
            $data['last_disconnect_attempt_at'] = now()->toIso8601String();
            Log::warning('SessionExpiryService: router disconnect exception, marking expired anyway', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
        }

        $session->update([
            'status' => 'expired',
            'expires_at' => $session->expires_at ?: now(),
            'mikrotik_data' => $data,
        ]);

        Log::info('SessionExpiryService: session expired', [
            'session_id' => $session->session_id,
            'user_id' => $session->user_id,
            'attempts' => $data['disconnect_attempts'] ?? 0,
        ]);

        return 'disconnected';
    }
}
