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
            // SMS/Notification Settings
            $table->string('sms_api_key')->nullable();
            $table->string('sms_sender_id')->nullable();
            $table->boolean('billing_alerts_enabled')->default(true)->nullable();
            $table->boolean('expiry_warnings_enabled')->default(true)->nullable();

            // Payment Gateway Settings
            $table->string('mpesa_api_key')->nullable();
            $table->string('mpesa_shortcode')->nullable();
            $table->string('mpesa_passkey')->nullable();
            $table->string('card_payment_gateway')->nullable();
            $table->string('card_payment_api_key')->nullable();
            $table->integer('payment_timeout_minutes')->default(15)->nullable();

            // Email Settings
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('email_from_name')->nullable();

            // Security Settings
            $table->integer('password_min_length')->default(8)->nullable();
            $table->boolean('password_require_uppercase')->default(true)->nullable();
            $table->boolean('password_require_number')->default(true)->nullable();
            $table->boolean('password_require_special')->default(true)->nullable();
            $table->integer('session_timeout_minutes')->default(60)->nullable();
            $table->boolean('two_factor_enabled')->default(false)->nullable();

            // Support/Contact Settings
            $table->string('support_phone')->nullable();
            $table->string('support_email')->nullable();
            $table->string('business_hours')->nullable();
            $table->string('emergency_contact')->nullable();

            // Free Package Settings
            $table->integer('free_package_data_limit_mb')->default(500)->nullable();
            $table->integer('free_package_validity_hours')->default(24)->nullable();
            $table->boolean('anti_abuse_enabled')->default(true)->nullable();

            // Invoice Settings
            $table->string('invoice_prefix')->default('INV-')->nullable();
            $table->string('payment_terms')->default('Net 30')->nullable();
            $table->text('invoice_footer')->nullable();

            // Branding Settings
            $table->string('company_logo')->nullable();
            $table->string('favicon')->nullable();
            $table->text('custom_css')->nullable();

            // Backup Settings
            $table->string('backup_schedule')->default('daily')->nullable();
            $table->integer('backup_retention_days')->default(30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_api_key',
                'sms_sender_id',
                'billing_alerts_enabled',
                'expiry_warnings_enabled',
                'mpesa_api_key',
                'mpesa_shortcode',
                'mpesa_passkey',
                'card_payment_gateway',
                'card_payment_api_key',
                'payment_timeout_minutes',
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption',
                'email_from_address',
                'email_from_name',
                'password_min_length',
                'password_require_uppercase',
                'password_require_number',
                'password_require_special',
                'session_timeout_minutes',
                'two_factor_enabled',
                'support_phone',
                'support_email',
                'business_hours',
                'emergency_contact',
                'free_package_data_limit_mb',
                'free_package_validity_hours',
                'anti_abuse_enabled',
                'invoice_prefix',
                'payment_terms',
                'invoice_footer',
                'company_logo',
                'favicon',
                'custom_css',
                'backup_schedule',
                'backup_retention_days',
            ]);
        });
    }
};
