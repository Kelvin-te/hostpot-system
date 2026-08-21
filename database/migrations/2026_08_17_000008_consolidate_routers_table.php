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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('identifier')->unique();
            $table->string('location');
            $table->ipAddress('ip');
            $table->string('ip_address')->nullable();
            $table->string('username');
            $table->string('password');
            $table->unsignedInteger('api_port')->default(8728);
            $table->boolean('hotspot_enabled')->default(false);
            $table->string('hotspot_interface')->nullable();
            $table->string('hotspot_server_ip')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->integer('packages_sync_count')->default(0);
            $table->integer('packages_unsync_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
