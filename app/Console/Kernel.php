<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('session:enforce-expiry')->everyMinute()->withoutOverlapping()->onOneServer();
        $schedule->command('sessions:sync')->everyMinute()->withoutOverlapping(10)->onOneServer();
        $schedule->command('session:send-expiry-alerts')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command('wallet:check-auto-renewals')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command('packages:sync-profiles')->everyFifteenMinutes()->withoutOverlapping(30)->onOneServer();
        $schedule->command('users:prune-orphans')->everyFifteenMinutes()->withoutOverlapping(30)->onOneServer();
        $schedule->command('routers:health-check')->everyFiveMinutes()->withoutOverlapping(10)->onOneServer();
        $schedule->command('pppoe:enforce-expiry')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
