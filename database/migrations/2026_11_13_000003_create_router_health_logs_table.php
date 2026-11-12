<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('router_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->boolean('online')->index();
            $table->unsignedTinyInteger('cpu_load')->nullable();
            $table->unsignedTinyInteger('memory_percentage')->nullable();
            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->decimal('rx_rate_mbps', 10, 2)->nullable();
            $table->decimal('tx_rate_mbps', 10, 2)->nullable();
            $table->unsignedInteger('active_sessions')->default(0);
            $table->string('error')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();

            $table->index(['router_id', 'checked_at']);
        });

        Schema::table('routers', function (Blueprint $table) {
            $table->boolean('is_online')->nullable()->after('is_active');
            $table->timestamp('last_health_at')->nullable()->after('is_online');
            $table->timestamp('went_offline_at')->nullable()->after('last_health_at');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'last_health_at', 'went_offline_at']);
        });
        Schema::dropIfExists('router_health_logs');
    }
};
