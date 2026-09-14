<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('mail_server')->nullable();
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->integer('mail_port')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('app_name')->nullable();
            $table->string('db')->nullable();
            $table->string('db_username')->nullable();
            $table->string('db_password')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency')->nullable();
            $table->unsignedInteger('bill_at')->default(0);
            $table->unsignedInteger('disconnect_at')->default(0);
            $table->json('walled_garden_domains')->nullable();
            $table->json('walled_garden_ips')->nullable();
            $table->boolean('walled_garden_enabled')->default(true);
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('sms_api_key')->nullable();
            $table->string('sms_sender_id')->nullable();
            $table->boolean('billing_alerts_enabled')->default(true);
            $table->boolean('expiry_warnings_enabled')->default(true);
            $table->string('mpesa_api_key')->nullable();
            $table->string('mpesa_shortcode')->nullable();
            $table->string('mpesa_passkey')->nullable();
            $table->string('card_payment_gateway')->nullable();
            $table->string('card_payment_api_key')->nullable();
            $table->integer('payment_timeout_minutes')->default(15);
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('email_from_name')->nullable();
            $table->integer('password_min_length')->default(8);
            $table->boolean('password_require_uppercase')->default(true);
            $table->boolean('password_require_number')->default(true);
            $table->boolean('password_require_special')->default(true);
            $table->integer('session_timeout_minutes')->default(60);
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('support_phone')->nullable();
            $table->string('support_email')->nullable();
            $table->string('business_hours')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->integer('free_package_data_limit_mb')->default(500);
            $table->integer('free_package_validity_hours')->default(24);
            $table->boolean('anti_abuse_enabled')->default(true);
            $table->string('invoice_prefix')->default('INV-');
            $table->string('payment_terms')->default('Net 30');
            $table->text('invoice_footer')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('favicon')->nullable();
            $table->text('custom_css')->nullable();
            $table->string('backup_schedule')->default('daily');
            $table->integer('backup_retention_days')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};