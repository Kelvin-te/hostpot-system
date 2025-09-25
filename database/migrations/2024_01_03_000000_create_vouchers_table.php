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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'used', 'expired', 'disabled'])->default('active')->index();
            $table->timestamp('used_at')->nullable();
            $table->string('used_by_mac')->nullable(); // MAC address of device that used it
            $table->string('used_by_ip')->nullable();
            $table->foreignId('session_id')->nullable()->constrained('hotspot_sessions')->onDelete('set null');
            $table->timestamp('expires_at')->nullable(); // Voucher expiry (different from session expiry)
            $table->json('metadata')->nullable(); // Additional voucher info
            $table->timestamps();

            // Indexes for performance
            $table->index(['code', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
