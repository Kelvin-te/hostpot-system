<?php

namespace App\Jobs;

use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRouterSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public int $routerId) {}

    public function handle(MikroTikService $mikroTikService): void
    {
        $router = \App\Models\Router::find($this->routerId);

        if (!$router || !$router->is_active) {
            return;
        }

        $result = $mikroTikService->syncSessionsWithRouter($router);

        // Also sync PPPoE sessions on this router
        $pppoeResult = app(\App\Services\PppoeService::class)->syncRouter($router);

        Log::info('Router session sync job completed', [
            'router_id' => $this->routerId,
            'hotspot' => $result,
            'pppoe' => $pppoeResult,
        ]);
    }
}
