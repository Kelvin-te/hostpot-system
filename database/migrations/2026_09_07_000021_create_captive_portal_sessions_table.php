<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captive_portal_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->foreignId('router_id')->nullable()->index();
            $table->string('client_mac')->nullable()->index();
            $table->string('client_ip')->nullable();
            $table->string('link_login')->nullable();
            $table->text('link_orig')->nullable();
            $table->string('chap_id')->nullable();
            $table->string('chap_challenge')->nullable();
            $table->enum('status', ['pending', 'authenticated', 'expired', 'failed'])->default('pending')->index();
            $table->foreignId('package_id')->nullable()->index();
            $table->foreignId('voucher_id')->nullable()->index();
            $table->foreignId('payment_id')->nullable()->index();
            $table->foreignId('authorization_id')->nullable()->index();
            $table->foreignId('hotspot_session_id')->nullable()->constrained('hotspot_sessions')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['session_token', 'status']);
            $table->index(['router_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captive_portal_sessions');
    }
};