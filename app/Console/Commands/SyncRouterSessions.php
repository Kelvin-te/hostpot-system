<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HotspotSessionService;

class SyncRouterSessions extends Command
{
    protected $signature = 'sessions:sync';
    protected $description = 'Sync local sessions with router data via MikroTik API';

    public function handle()
    {
        $service = app(HotspotSessionService::class);

        $this->info('Syncing sessions via MikroTik API...');

        $result = $service->syncSessionsWithRouters();

        if (!$result['success']) {
            $this->error($result['message'] ?? 'Sync failed');
            return 1;
        }

        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Sessions Updated', $result['synced'] ?? 0],
                ['Sessions Disconnected', $result['stopped'] ?? 0],
                ['Users Re-created', $result['recreated'] ?? 0],
            ]
        );

        return 0;
    }
}
