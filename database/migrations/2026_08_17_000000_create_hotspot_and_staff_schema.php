<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrations remain one per table; this file is intentionally no-op.
        return;

        // Core network / router tables
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

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->decimal('bandwidth_upload', 8, 2)->nullable()->comment('Upload bandwidth in Mbps');
            $table->decimal('bandwidth_download', 8, 2)->nullable()->comment('Download bandwidth in Mbps');
            $table->integer('session_timeout')->nullable()->comment('Session timeout in hours');
            $table->integer('idle_timeout')->nullable()->comment('Idle timeout in minutes');
            $table->integer('shared_users')->nullable()->default(1)->comment('Number of shared users allowed');
            $table->string('rate_limit', 50)->nullable()->comment('Custom rate limit string');
            $table->integer('validity_minutes')->nullable()->comment('Package validity in minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'router_id']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_request_id')->unique();
            $table->string('merchant_request_id')->nullable();
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('account_reference');
            $table->string('transaction_desc');
            $table->enum('status', ['pending', 'completed', 'failed', 'expired'])->default('pending');
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('response_code')->nullable();
            $table->text('response_description')->nullable();
            $table->text('customer_message')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_description')->nullable();
            $table->json('callback_data')->nullable();
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone_number');
            $table->index('mpesa_receipt_number');
            $table->index('session_id');
        });

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

        Schema::create('hotspot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable()->index();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('authorization_id')->nullable()->constrained('hotspot_authorizations')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username')->nullable();
            $table->string('mikrotik_username')->nullable();
            $table->string('mikrotik_password')->nullable();
            $table->string('mikrotik_profile')->nullable();
            $table->string('session_id')->unique();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->bigInteger('bytes_uploaded')->default(0);
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->bigInteger('bytes_total')->default(0);
            $table->enum('status', ['active', 'expired', 'blocked', 'paused'])->default('active')->index();
            $table->json('mikrotik_data')->nullable();
            $table->timestamps();

            $table->index(['mac_address', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['session_id', 'status']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'used', 'expired', 'disabled'])->default('active')->index();
            $table->timestamp('used_at')->nullable();
            $table->string('used_by_mac')->nullable();
            $table->string('used_by_ip')->nullable();
            $table->foreignId('session_id')->nullable()->constrained('hotspot_sessions')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['code', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('captive_portal_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->foreignId('router_id')->nullable()->constrained()->onDelete('set null');
            $table->string('client_mac')->nullable()->index();
            $table->string('client_ip')->nullable();
            $table->string('link_login')->nullable();
            $table->text('link_orig')->nullable();
            $table->string('chap_id')->nullable();
            $table->string('chap_challenge')->nullable();
            $table->enum('status', ['pending', 'authenticated', 'expired', 'failed'])->default('pending')->index();
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable();
            $table->foreignId('authorization_id')->nullable();
            $table->foreignId('hotspot_session_id')->nullable()->constrained('hotspot_sessions')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['session_token', 'status']);
            $table->index(['router_id', 'status']);
        });

        Schema::create('radius_nas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('nas_identifier')->unique();
            $table->string('nas_ip_address')->nullable();
            $table->string('nas_secret')->nullable();
            $table->string('nas_port')->nullable();
            $table->enum('nas_type', ['other', 'cisco', 'mikrotik'])->default('mikrotik');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('radius_accounting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('authorization_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('hotspot_session_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username')->nullable()->index();
            $table->string('nas_ip_address')->nullable();
            $table->string('nas_port')->nullable();
            $table->string('nas_identifier')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->string('framed_ip_address')->nullable();
            $table->string('calling_station_id')->nullable();
            $table->string('called_station_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamp('stop_time')->nullable();
            $table->integer('session_time')->default(0);
            $table->bigInteger('input_octets')->default(0);
            $table->bigInteger('output_octets')->default(0);
            $table->string('terminate_cause')->nullable();
            $table->enum('status', ['start', 'interim-update', 'stop'])->default('start')->index();
            $table->json('accounting_attributes')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'status']);
            $table->index(['username', 'status']);
            $table->index(['start_time', 'stop_time']);
        });

        Schema::create('sms_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->index();
            $table->string('otp', 6);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->integer('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['phone', 'verified_at']);
            $table->index(['expires_at', 'verified_at']);
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('staff');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('staff_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('staff_password_reset_tokens');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('sms_verifications');
        Schema::dropIfExists('radius_accounting');
        Schema::dropIfExists('radius_nas');
        Schema::dropIfExists('captive_portal_sessions');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('hotspot_sessions');
        Schema::dropIfExists('hotspot_authorizations');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('routers');

        Schema::enableForeignKeyConstraints();
    }
};
