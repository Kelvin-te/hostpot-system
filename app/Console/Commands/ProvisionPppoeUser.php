<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\User;
use App\Services\PppoeService;
use Illuminate\Console\Command;

class ProvisionPppoeUser extends Command
{
    protected $signature = 'pppoe:provision {user : User ID} {package : Package ID} {--password= : Override generated password}';
    protected $description = 'Provision PPPoE credentials for a subscriber on the package\'s router';

    public function handle(PppoeService $pppoe): int
    {
        $user = User::find($this->argument('user'));
        $package = Package::find($this->argument('package'));

        if (!$user || !$package) {
            $this->error('User or package not found.');
            return self::FAILURE;
        }

        $result = $pppoe->provision($user, $package, $this->option('password'));

        if (!$result['success']) {
            $this->error($result['message']);
            return self::FAILURE;
        }

        $this->info($result['message']);
        $this->line("Username: {$result['username']}");
        $this->line("Password: {$result['password']}");
        $this->warn('Share these credentials with the subscriber — the password is not shown again.');

        return self::SUCCESS;
    }
}
