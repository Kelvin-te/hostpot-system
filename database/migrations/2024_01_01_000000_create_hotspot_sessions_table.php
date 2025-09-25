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
        Schema::create('hotspot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable()->index();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username')->nullable(); // For voucher codes or phone numbers
            $table->string('session_id')->unique();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->bigInteger('bytes_uploaded')->default(0);
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->bigInteger('bytes_total')->default(0);
            $table->enum('status', ['active', 'expired', 'blocked', 'paused'])->default('active')->index();
            $table->json('mikrotik_data')->nullable(); // Store MikroTik specific data
            $table->timestamps();

            // Indexes for performance
            $table->index(['mac_address', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_sessions');
    }
};
