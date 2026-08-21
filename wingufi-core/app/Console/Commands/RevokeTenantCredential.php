<?php

namespace App\Console\Commands;

use App\Models\TenantCredential;
use Illuminate\Console\Command;

class RevokeTenantCredential extends Command
{
    protected $signature = 'wingufi:credential:revoke {credential : The ID of the credential to revoke}';

    protected $description = 'Revoke an active tenant credential';

    public function handle(): int
    {
        $credentialId = $this->argument('credential');

        $credential = TenantCredential::find($credentialId);

        if (! $credential) {
            $this->error('Credential not found.');
            return self::FAILURE;
        }

        if ($credential->status !== 'active') {
            $this->warn('Credential is already '.$credential->status.'.');
            return self::SUCCESS;
        }

        $credential->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->info('Credential revoked successfully.');

        return self::SUCCESS;
    }
}
