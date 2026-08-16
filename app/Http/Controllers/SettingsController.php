<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

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
            'mpesa_api_key' => 'nullable|string',
            'mpesa_shortcode' => 'nullable|string',
            'mpesa_passkey' => 'nullable|string',
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
}
