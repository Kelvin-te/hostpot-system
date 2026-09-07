<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPackageProfiles extends Command
{
    protected $signature = 'packages:sync-profiles';

    protected $description = 'Sync package hotspot user profiles to all active routers';

    public function handle()
    {
        $mikrotikService = app(MikroTikService::class);

        $routers = Router::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            $this->warn('No active routers found.');
            return 0;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($routers as $router) {
            $this->info("Syncing package profiles for router {$router->name} ({$router->id})...");

            try {
                $result = $mikrotikService->syncPackageProfiles($router);

                if ($result['success'] ?? false) {
                    $this->info("  OK: {$result['message']}");
                    $successCount++;
                } else {
                    $this->error("  FAILED: {$result['message']}");
                    $failCount++;
                }
            } catch (\Exception $e) {
                Log::error('Scheduled package profile sync failed', [
                    'router_id' => $router->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  EXCEPTION: {$e->getMessage()}");
                $failCount++;
            }
        }

        $this->info("Done. Success: {$successCount}, Failed: {$failCount}");

        return $failCount > 0 ? 1 : 0;
    }
}
