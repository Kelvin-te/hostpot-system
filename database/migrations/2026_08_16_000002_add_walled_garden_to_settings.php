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
        Schema::table('settings', function (Blueprint $table) {
            $table->json('walled_garden_domains')->nullable();
            $table->json('walled_garden_ips')->nullable()->after('walled_garden_domains');
            $table->boolean('walled_garden_enabled')->default(true)->after('walled_garden_ips');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['walled_garden_domains', 'walled_garden_ips', 'walled_garden_enabled']);
        });
    }
};
