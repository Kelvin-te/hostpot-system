<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;


/**
 * Setting
 *
 * @mixin Eloquent
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_server',
        'mail_username',
        'mail_password',
        'mail_port',
        'bill_at',
        'app_name',
        'timezone',
        'currency',
        'walled_garden_domains',
        'walled_garden_ips',
        'walled_garden_enabled',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
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
        'disconnect_at',
    ];

    public static function loadSettings()
    {
        $settings = self::firstOrFail();

        Config::set('app.name', $settings->app_name ? $settings->app_name : 'visp');
        Config::set('app.timezone', $settings->timezone ? $settings->timezone : 'UTC');
    }
}
