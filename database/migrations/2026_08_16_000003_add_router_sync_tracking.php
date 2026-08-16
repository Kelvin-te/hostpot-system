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
        Schema::table('routers', function (Blueprint $table) {
            $table->boolean('hotspot_enabled')->default(false)->after('api_port');
            $table->string('hotspot_interface')->nullable()->after('hotspot_enabled');
            $table->string('hotspot_server_ip')->nullable()->after('hotspot_interface');
            $table->timestamp('last_synced_at')->nullable()->after('hotspot_server_ip');
            $table->integer('packages_sync_count')->default(0)->after('last_synced_at');
            $table->integer('packages_unsync_count')->default(0)->after('packages_sync_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['hotspot_enabled', 'hotspot_interface', 'hotspot_server_ip', 'last_synced_at', 'packages_sync_count', 'packages_unsync_count']);
        });
    }
};
