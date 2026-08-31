<?php

namespace App\Console\Commands;

use App\Models\HotspotSession;
use App\Services\MikroTikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DisconnectExpiredHotspotSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotspot:disconnect-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disconnect expired hotspot sessions from MikroTik routers';

    /**
     * Maximum number of disconnect retries before giving up on a session.
     *
     * @var int
     */
    protected int $maxRetries = 5;

    /**
     * Execute the console command.
     */
    public function handle(MikroTikService $mikroTikService): int
    {
        $sessions = HotspotSession::query()
            ->where('expires_at', '<', now())
            ->where(function ($query) {
                $query->whereNull('mikrotik_data->disconnected_at')
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('mikrotik_data->disconnect_attempts')
                            ->orWhere('mikrotik_data->disconnect_attempts', '<', $this->maxRetries);
                    });
            })
            ->get();

        if ($sessions->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Found {$sessions->count()} expired session(s) to disconnect.");

        foreach ($sessions as $session) {
            $data = $session->mikrotik_data ?? [];
            $attempts = $data['disconnect_attempts'] ?? 0;

            $this->info("Disconnecting session [{$session->session_id}] for user [{$session->username}] (attempt " . ($attempts + 1) . "/{$this->maxRetries})");

            $success = $mikroTikService->disconnectUser($session);

            if ($success) {
                $data['disconnected_at'] = now()->toIso8601String();
                $this->info("Successfully disconnected session [{$session->session_id}].");
            } else {
                $data['disconnect_attempts'] = $attempts + 1;
                $data['last_disconnect_attempt_at'] = now()->toIso8601String();
                $this->warn("Failed to disconnect session [{$session->session_id}]. Will retry if under attempt limit.");
            }

            $session->update([
                'status' => 'expired',
                'expires_at' => $session->expires_at ?: now(),
                'mikrotik_data' => $data,
            ]);

            Log::info('Expired hotspot session disconnect processed', [
                'session_id' => $session->session_id,
                'username' => $session->username,
                'success' => $success,
                'attempts' => $data['disconnect_attempts'] ?? 0,
            ]);
        }

        return self::SUCCESS;
    }
}
