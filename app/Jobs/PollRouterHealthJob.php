<?php

namespace App\Jobs;

use App\Services\RouterHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollRouterHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public int $routerId) {}

    public function handle(RouterHealthService $health): void
    {
        $router = \App\Models\Router::find($this->routerId);

        if ($router && $router->is_active) {
            $health->poll($router);
        }
    }
}
