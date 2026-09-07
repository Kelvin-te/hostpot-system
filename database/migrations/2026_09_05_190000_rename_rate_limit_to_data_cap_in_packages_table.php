<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // packages table
        if (Schema::hasColumn('packages', 'rate_limit')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->renameColumn('rate_limit', 'data_cap');
            });
        }

        if (Schema::hasColumn('packages', 'data_cap')) {
            DB::statement("ALTER TABLE packages MODIFY data_cap VARCHAR(50) DEFAULT NULL COMMENT 'Aggregated data cap (e.g., 500MB, 2GB)'");
        }

        // hotspot_authorizations table
        if (Schema::hasColumn('hotspot_authorizations', 'rate_limit')) {
            Schema::table('hotspot_authorizations', function (Blueprint $table) {
                $table->renameColumn('rate_limit', 'data_cap');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotspot_authorizations', 'data_cap')) {
            Schema::table('hotspot_authorizations', function (Blueprint $table) {
                $table->renameColumn('data_cap', 'rate_limit');
            });
        }

        if (Schema::hasColumn('packages', 'data_cap')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->renameColumn('data_cap', 'rate_limit');
            });
        }

        if (Schema::hasColumn('packages', 'rate_limit')) {
            DB::statement("ALTER TABLE packages MODIFY rate_limit VARCHAR(50) DEFAULT NULL COMMENT 'Custom rate limit string'");
        }
    }
};
