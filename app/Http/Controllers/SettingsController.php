<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\VintexSmsService;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function edit()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $settings = Setting::firstOrNew();
        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // ISP Information
            'company_name' => 'nullable|string',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string',
            'company_email' => 'nullable|email',
            
            // Application Settings
            'app_name' => 'nullable|string',
            'timezone' => 'nullable|string',
            'currency' => 'nullable|string',
            
            // Walled Garden Settings
            'walled_garden_domains' => 'nullable|string',
            'walled_garden_ips' => 'nullable|string',
            'walled_garden_enabled' => 'nullable|boolean',
            
            // Billing Settings
            'bill_at' => 'nullable|integer|min:1|max:31',
            'disconnect_at' => 'nullable|integer|min:1|max:31',
            
            // SMS/Notification Settings
            'sms_api_key' => 'nullable|string',
            'sms_sender_id' => 'nullable|string',
            'billing_alerts_enabled' => 'nullable|boolean',
            'expiry_warnings_enabled' => 'nullable|boolean',
            
            // Payment Gateway Settings
            'mpesa_consumer_key' => 'nullable|string',
            'mpesa_consumer_secret' => 'nullable|string',
            'mpesa_shortcode' => 'nullable|string',
            'mpesa_passkey' => 'nullable|string',
            'mpesa_type' => 'nullable|in:paybill,buygoods',
            'mpesa_till_number' => 'nullable|string',
            'card_payment_gateway' => 'nullable|string',
            'card_payment_api_key' => 'nullable|string',
            'payment_timeout_minutes' => 'nullable|integer|min:1',
            
            // Email Settings
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string',
            'email_from_address' => 'nullable|email',
            'email_from_name' => 'nullable|string',
            
            // Security Settings
            'password_min_length' => 'nullable|integer|min:6',
            'password_require_uppercase' => 'nullable|boolean',
            'password_require_number' => 'nullable|boolean',
            'password_require_special' => 'nullable|boolean',
            'session_timeout_minutes' => 'nullable|integer|min:5',
            'two_factor_enabled' => 'nullable|boolean',
            
            // Support/Contact Settings
            'support_phone' => 'nullable|string',
            'support_email' => 'nullable|email',
            'business_hours' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            
            // Free Package Settings
            'free_package_data_limit_mb' => 'nullable|integer|min:0',
            'free_package_validity_hours' => 'nullable|integer|min:1',
            'anti_abuse_enabled' => 'nullable|boolean',
            
            // Invoice Settings
            'invoice_prefix' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'invoice_footer' => 'nullable|string',
            
            // Branding Settings
            'company_logo' => 'nullable|string',
            'favicon' => 'nullable|string',
            'custom_css' => 'nullable|string',
            
            // Backup Settings
            'backup_schedule' => 'nullable|string',
            'backup_retention_days' => 'nullable|integer|min:1',
        ]);

        $settings = Setting::firstOrNew();
        
        // Handle walled garden domains - convert comma-separated string to array then JSON
        if (isset($validated['walled_garden_domains']) && !empty($validated['walled_garden_domains'])) {
            $domains = array_map('trim', explode(',', $validated['walled_garden_domains']));
            $validated['walled_garden_domains'] = json_encode($domains);
        } else {
            $validated['walled_garden_domains'] = null;
        }
        
        // Handle walled garden IPs - convert comma-separated string to array then JSON
        if (isset($validated['walled_garden_ips']) && !empty($validated['walled_garden_ips'])) {
            $ips = array_map('trim', explode(',', $validated['walled_garden_ips']));
            $validated['walled_garden_ips'] = json_encode($ips);
        } else {
            $validated['walled_garden_ips'] = null;
        }
        
        // Handle boolean defaults
        if (!isset($validated['walled_garden_enabled'])) {
            $validated['walled_garden_enabled'] = true;
        }
        if (!isset($validated['billing_alerts_enabled'])) {
            $validated['billing_alerts_enabled'] = true;
        }
        if (!isset($validated['expiry_warnings_enabled'])) {
            $validated['expiry_warnings_enabled'] = true;
        }
        if (!isset($validated['anti_abuse_enabled'])) {
            $validated['anti_abuse_enabled'] = true;
        }

        $settings->fill($validated);
        $settings->save();

        return back()->with('success', __('Update successful'));
    }

    public function testSms()
    {
        $settings = Setting::first();
        
        if (!$settings || empty($settings->sms_api_key) || empty($settings->sms_sender_id)) {
            return back()->with('error', __('SMS API key and Sender ID are required'));
        }

        if (empty($settings->support_phone)) {
            return back()->with('error', __('Support phone number is required in settings to send test SMS'));
        }

        try {
            $smsService = new VintexSmsService();
            $result = $smsService->sendSms(
                $settings->support_phone,
                'This is a test message from your hotspot admin system. SMS configuration is working correctly.'
            );

            if ($result['success']) {
                return back()->with('success', __('Test SMS sent successfully to ') . $settings->support_phone);
            } else {
                return back()->with('error', __('Failed to send test SMS: ') . $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('SMS test failed', ['error' => $e->getMessage()]);
            return back()->with('error', __('SMS test failed: ') . $e->getMessage());
        }
    }

    public function testMpesa()
    {
        $settings = Setting::first();
        
        if (!$settings || empty($settings->mpesa_consumer_key) || empty($settings->mpesa_consumer_secret) || empty($settings->mpesa_shortcode) || empty($settings->mpesa_passkey)) {
            return back()->with('error', __('M-Pesa credentials (Consumer Key, Consumer Secret, Shortcode, and Passkey) are required'));
        }

        if (empty($settings->support_phone)) {
            return back()->with('error', __('Support phone number is required in settings to send test payment'));
        }

        try {
            $mpesaService = new MpesaService();
            $result = $mpesaService->stkPush(
                $settings->support_phone,
                1.00,
                'TEST-' . time(),
                'Test payment from hotspot admin'
            );

            if ($result['success']) {
                return back()->with('success', __('Test STK Push initiated to ') . $settings->support_phone . __('. Check your phone to complete the KES 1 payment.'));
            } else {
                return back()->with('error', __('Failed to initiate test payment: ') . $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('M-Pesa test failed', ['error' => $e->getMessage()]);
            return back()->with('error', __('M-Pesa test failed: ') . $e->getMessage());
        }
    }

    public function testEmail()
    {
        $settings = Setting::first();
        
        if (!$settings || empty($settings->smtp_host) || empty($settings->smtp_username) || empty($settings->smtp_password)) {
            return back()->with('error', __('SMTP configuration (Host, Username, and Password) is required'));
        }

        $testEmail = $settings->support_email ?? $settings->email_from_address ?? $settings->smtp_username;
        
        if (empty($testEmail)) {
            return back()->with('error', __('Support email or Email From Address is required in settings to send test email'));
        }

        try {
            // Temporarily configure SMTP settings
            config([
                'mail.mailers.smtp.host' => $settings->smtp_host,
                'mail.mailers.smtp.port' => $settings->smtp_port ?? 587,
                'mail.mailers.smtp.username' => $settings->smtp_username,
                'mail.mailers.smtp.password' => $settings->smtp_password,
                'mail.mailers.smtp.encryption' => $settings->smtp_encryption ?? 'tls',
                'mail.from.address' => $settings->email_from_address ?? $settings->smtp_username,
                'mail.from.name' => $settings->email_from_name ?? config('app.name'),
            ]);

            Mail::raw('This is a test email from your hotspot admin system. Email configuration is working correctly.', function ($message) use ($testEmail, $settings) {
                $message->to($testEmail)
                    ->subject('Test Email - Hotspot Admin')
                    ->from($settings->email_from_address ?? $settings->smtp_username, $settings->email_from_name ?? config('app.name'));
            });

            return back()->with('success', __('Test email sent successfully to ') . $testEmail);
        } catch (\Exception $e) {
            Log::error('Email test failed', ['error' => $e->getMessage()]);
            return back()->with('error', __('Email test failed: ') . $e->getMessage());
        }
    }
}
