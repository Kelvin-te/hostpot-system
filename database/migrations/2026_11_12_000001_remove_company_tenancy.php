<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the multi-tenant "company" layer. NetBill is deployed one
     * database per ISP, so tenant scoping is unnecessary.
     */
    public function up(): void
    {
        $tablesWithCompanyId = [
            'users',
            'routers',
            'settings',
            'payment_transactions',
            'packages',
            'hotspot_sessions',
            'hotspot_authorizations',
        ];

        foreach ($tablesWithCompanyId as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    // Drop the FK constraint first — company_id was added with
                    // ->constrained(), so a bare dropColumn fails on MySQL.
                    $blueprint->dropConstrainedForeignId('company_id');
                });
            }
        }

        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('companies');
    }

    public function down(): void
    {
        // Intentionally irreversible: tenancy was removed by design.
    }
};
