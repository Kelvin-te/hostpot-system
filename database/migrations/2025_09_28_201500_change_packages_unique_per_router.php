<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Change global unique on name to composite unique (router_id, name)
            try {
                $table->dropUnique('packages_name_unique');
            } catch (\Throwable $e) {
                // Index may already be dropped on some environments; ignore
            }
            $table->unique(['router_id', 'name'], 'packages_router_id_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            try {
                $table->dropUnique('packages_router_id_name_unique');
            } catch (\Throwable $e) {
                // ignore if not present
            }
            $table->unique('name', 'packages_name_unique');
        });
    }
};
