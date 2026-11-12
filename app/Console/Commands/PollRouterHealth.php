<?php

namespace App\Console\Commands;

use App\Jobs\PollRouterHealthJob;
use App\Models\Router;
use Illuminate\Console\Command;

class PollRouterHealth extends Command
{
    protected $signature = 'routers:health-check {--inline : Poll routers in-process instead of dispatching jobs}';
    protected $description = 'Poll health/traffic for all active routers and record snapshots';

    public function handle()
    {
        $routers = Router::where('is_active', true)->get();

        if ($this->option('inline')) {
            $service = app(\App\Services\RouterHealthService::class);
            foreach ($routers as $router) {
                $service->poll($router);
            }
            $this->info("Polled {$routers->count()} router(s) inline.");
            return 0;
        }

        $routers->each(fn ($router) => PollRouterHealthJob::dispatch($router->id));

        $this->info("Dispatched health checks for {$routers->count()} router(s).");
        return 0;
    }
}
