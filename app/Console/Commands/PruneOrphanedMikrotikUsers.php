<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class PruneOrphanedMikrotikUsers extends Command
{
    protected $signature = 'users:prune-orphans';

    protected $description = 'Remove MikroTik hotspot users that have no active/reconnectable session or whose package no longer exists';

    public function handle()
    {
        $mikrotikService = app(MikroTikService::class);
        $routers = Router::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            $this->warn('No active routers found.');
            return 0;
        }

        $totalRemoved = 0;
        $totalSkipped = 0;

        foreach ($routers as $router) {
            $this->info("Checking router {$router->name} ({$router->id})...");

            try {
                $client = $mikrotikService->connectToRouter($router);
                if (!$client) {
                    $this->warn("  Could not connect, skipping.");
                    continue;
                }

                $userQuery = new Query('/ip/hotspot/user/print');
                $users = $client->query($userQuery)->read();

                $removed = 0;
                $skipped = 0;

                foreach ($users as $user) {
                    $profileName = $user['profile'] ?? '';
                    $username = $user['name'] ?? '';
                    $userId = $user['.id'] ?? null;

                    // Only manage users that belong to our package profiles
                    if (!str_starts_with($profileName, 'pkg_') || !$userId) {
                        $skipped++;
                        continue;
                    }

                    // Parse package ID from profile name: pkg_<id>_<name>
                    $packageId = null;
                    if (preg_match('/^pkg_(\d+)_/', $profileName, $matches)) {
                        $packageId = (int) $matches[1];
                    }

                    $shouldRemove = false;

                    // Reason 1: package no longer exists or is inactive
                    if ($packageId) {
                        $package = Package::find($packageId);
                        if (!$package || !$package->is_active) {
                            $shouldRemove = true;
                        }
                    } else {
                        // Could not parse package ID — treat as orphan
                        $shouldRemove = true;
                    }

                    // Reason 2: no active/authorized/reconnectable session for this user
                    if (!$shouldRemove) {
                        $hasLiveSession = \App\Models\HotspotSession::where(function ($q) use ($username) {
                                $q->where('mikrotik_username', $username)
                                  ->orWhere('username', $username)
                                  ->orWhere('session_id', $username);
                            })
                            ->whereIn('status', ['active', 'authorized', 'disconnected'])
                            ->where('expires_at', '>', now())
                            ->exists();

                        if (!$hasLiveSession) {
                            $shouldRemove = true;
                        }
                    }

                    if ($shouldRemove) {
                        $removeQuery = (new Query('/ip/hotspot/user/remove'))->equal('.id', $userId);
                        $client->query($removeQuery)->read();
                        $removed++;

                        Log::info('Pruned orphaned MikroTik hotspot user', [
                            'router_id' => $router->id,
                            'username' => $username,
                            'profile' => $profileName,
                            'package_id' => $packageId,
                        ]);
                    } else {
                        $skipped++;
                    }
                }

                $this->info("  Removed: {$removed}, Skipped: {$skipped}");
                $totalRemoved += $removed;
                $totalSkipped += $skipped;
            } catch (\Exception $e) {
                Log::error('Failed to prune orphaned MikroTik users', [
                    'router_id' => $router->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Error: {$e->getMessage()}");
            }
        }

        $this->info("Done. Total removed: {$totalRemoved}, total skipped: {$totalSkipped}");

        return 0;
    }
}
