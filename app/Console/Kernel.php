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
        $schedule->command('session:enforce-expiry')->everyMinute();
        $schedule->command('sessions:sync')->everyMinute();
        $schedule->command('session:send-expiry-alerts')->everyFiveMinutes();
        $schedule->command('wallet:check-auto-renewals')->everyFiveMinutes();
        $schedule->command('packages:sync-profiles')->everyFifteenMinutes();
        $schedule->command('users:prune-orphans')->everyFifteenMinutes();
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
