<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncRouterSessionsJob;
use App\Models\Router;

class SyncRouterSessions extends Command
{
    protected $signature = 'sessions:sync {--inline : Run router syncs in-process instead of dispatching jobs}';
    protected $description = 'Sync local sessions with router data via MikroTik API';

    public function handle()
    {
        if (!$this->option('inline')) {
            $dispatched = 0;

            Router::where('is_active', true)->pluck('id')->each(function ($id) use (&$dispatched) {
                SyncRouterSessionsJob::dispatch($id);
                $dispatched++;
            });

            $this->info("Dispatched session sync jobs for {$dispatched} router(s).");

            return 0;
        }

        $service = app(\App\Services\HotspotSessionService::class);

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
