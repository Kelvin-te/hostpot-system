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
        Schema::create('hotspot_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('authorization_key')->unique();
            $table->string('wingufi_core_authorization_id')->nullable()->index();
            $table->foreignId('router_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_transaction_id')->nullable();
            $table->string('client_identifier')->nullable();
            $table->string('radius_username')->nullable()->index();
            $table->text('radius_password_encrypted')->nullable();
            $table->string('client_mac')->nullable()->index();
            $table->enum('status', ['pending', 'authorized', 'active', 'expired', 'revoked', 'cancelled'])->default('pending')->index();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->integer('session_timeout')->nullable();
            $table->integer('idle_timeout')->nullable();
            $table->string('rate_limit')->nullable();
            $table->integer('simultaneous_sessions')->default(1);
            $table->json('authorization_attributes')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['authorization_key', 'status']);
            $table->index(['client_identifier', 'status']);
            $table->index(['expires_at', 'status']);
            $table->unique(
                ['payment_transaction_id', 'package_id'],
                'hotspot_authorizations_payment_package_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_authorizations');
    }
};
