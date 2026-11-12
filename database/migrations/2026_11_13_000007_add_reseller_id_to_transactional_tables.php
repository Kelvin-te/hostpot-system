<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables scoped through their package (reseller-owned packages).
     */
    private array $packageScoped = [
        'hotspot_sessions',
        'hotspot_authorizations',
        'vouchers',
    ];

    /**
     * Tables scoped through their subscriber (users.reseller_id).
     */
    private array $userScoped = [
        'payment_transactions',
        'billings',
        'payments',
        'wallet_transactions',
        'tickets',
    ];

    public function up(): void
    {
        foreach (array_merge($this->packageScoped, $this->userScoped) as $table) {
            if (!Schema::hasColumn($table, 'reseller_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->foreignId('reseller_id')->nullable()->after('id')
                        ->constrained('resellers')->nullOnDelete();
                });
            }
        }

        // Backfill from packages
        foreach ($this->packageScoped as $table) {
            if (!Schema::hasColumn($table, 'package_id')) {
                continue;
            }
            DB::statement("UPDATE {$table} t JOIN packages p ON t.package_id = p.id SET t.reseller_id = p.reseller_id WHERE t.reseller_id IS NULL AND p.reseller_id IS NOT NULL");
        }

        // Backfill from users
        foreach ($this->userScoped as $table) {
            if (!Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            DB::statement("UPDATE {$table} t JOIN users u ON t.user_id = u.id SET t.reseller_id = u.reseller_id WHERE t.reseller_id IS NULL AND u.reseller_id IS NOT NULL");
        }
    }

    public function down(): void
    {
        foreach (array_merge($this->packageScoped, $this->userScoped) as $table) {
            if (Schema::hasColumn($table, 'reseller_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropConstrainedForeignId('reseller_id');
                });
            }
        }
    }
};
