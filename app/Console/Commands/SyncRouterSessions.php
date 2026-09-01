<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HotspotSessionService;

class SyncRouterSessions extends Command
{
    protected $signature = 'sessions:sync';
    protected $description = 'Sync local sessions with RADIUS accounting data from WinguFi Core';

    public function handle()
    {
        $service = app(HotspotSessionService::class);

        $this->info('Syncing sessions with WinguFi Core...');

        $result = $service->syncSessionsWithCore();

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
                ['RADIUS Sessions (no local match)', $result['not_found'] ?? 0],
            ]
        );

        return 0;
    }
}
