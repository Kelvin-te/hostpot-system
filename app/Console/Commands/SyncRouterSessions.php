<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Services\MikroTikService;

class SyncRouterSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:sync {router_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync database sessions with MikroTik router active sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $routerId = $this->argument('router_id');
        $service = app(MikroTikService::class);

        if ($routerId) {
            // Sync specific router
            $router = Router::find($routerId);
            
            if (!$router) {
                $this->error("Router not found with ID: {$routerId}");
                return 1;
            }

            $this->info("Syncing sessions for router: {$router->name}");
            $result = $service->syncSessionsWithRouter($router);
            
            if ($result['success']) {
                $this->info($result['message']);
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Synced Sessions', $result['synced']],
                        ['Created Sessions', $result['created'] ?? 0],
                        ['Expired Sessions', $result['expired'] ?? 0],
                    ]
                );
            } else {
                $this->error($result['message']);
                return 1;
            }
            
        } else {
            // Sync all routers
            $routers = Router::all();
            
            if ($routers->isEmpty()) {
                $this->warn('No routers found to sync');
                return 0;
            }

            $this->info("Syncing sessions for {$routers->count()} routers...");
            
            $totalSynced = 0;
            $totalCreated = 0;
            $totalExpired = 0;
            $progressBar = $this->output->createProgressBar($routers->count());
            
            foreach ($routers as $router) {
                $result = $service->syncSessionsWithRouter($router);
                
                if ($result['success']) {
                    $totalSynced += $result['synced'];
                    $totalCreated += $result['created'] ?? 0;
                    $totalExpired += $result['expired'] ?? 0;
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->info('Sync completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Routers', $routers->count()],
                    ['Total Synced Sessions', $totalSynced],
                    ['Total Created Sessions', $totalCreated],
                    ['Total Expired Sessions', $totalExpired],
                ]
            );
        }

        return 0;
    }
}
