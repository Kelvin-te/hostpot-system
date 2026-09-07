<?php

namespace App\Console\Commands;

use App\Services\SessionExpiryService;
use Illuminate\Console\Command;

class EnforceSessionExpiry extends Command
{
    protected $signature = 'session:enforce-expiry';
    protected $description = 'Disconnect sessions that have passed their expiry time or data limit';

    public function handle(SessionExpiryService $service): int
    {
        $result = $service->enforceAll();

        $this->info("Enforced expiry: {$result['expired']} sessions | Data limit: {$result['data_limited']} sessions | Skipped: {$result['skipped']} (not logged in)");

        return self::SUCCESS;
    }
}
