<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VintexSmsService
{
    protected string $apiUrl;
    protected ?string $email;
    protected ?string $bearerToken;
    protected string $senderId;
    protected string $companyName;
    protected string $companyPhone;
    protected bool $hasCustomSenderName;

    public function __construct()
    {
        $this->apiUrl = config('services.vintex.api_url', 'https://sms.vintextechnologies.com/api/sendMessage');
        $this->email = config('services.vintex.email');
        $this->bearerToken = config('services.vintex.bearer_token');

        // Load company details from Settings table (graceful fallback if table doesn't exist)
        $settings = null;
        try {
            $settings = \App\Models\Setting::first();
        } catch (Exception $e) {
            // Settings table might not exist during migrations or tests
        }

        // Sender ID: check Setting.sms_sender_id first, fall back to config
        $settingSenderId = $settings?->sms_sender_id;
        $this->hasCustomSenderName = !empty($settingSenderId);
        $this->senderId = $this->hasCustomSenderName
            ? $settingSenderId
            : config('services.vintex.sender_id', 'STERKE');

        // Company details from Settings, with fallback to app name
        $this->companyName = $settings?->company_name ?: config('app.name', 'MatuNet');
        $this->companyPhone = $settings?->company_phone ?: '';
    }

    /**
     * Send SMS message
     */
    public function sendSms(string $phone, string $message): array
    {
        try {
            if (empty($this->email) || empty($this->bearerToken)) {
                Log::error('Vintex SMS credentials are not configured');
                return [
                    'success' => false,
                    'message' => 'SMS credentials not configured',
                    'data' => null,
                ];
            }

            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phone);
            
            if (!$normalizedPhone) {
                throw new Exception('Invalid phone number format');
            }

            // Append company signature when no custom sender name is configured
            $message = $this->appendSignature($message);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?email=' . urlencode($this->email), [
                'recipients' => $normalizedPhone,
                'senderID' => $this->senderId,
                'message' => $message
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['status']['type']) && $result['status']['type'] === 'success') {
                Log::info('SMS sent successfully', [
                    'phone' => $normalizedPhone,
                    'message_length' => strlen($message),
                    'response' => $result
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $result
                ];
            }

            Log::error('SMS sending failed', [
                'phone' => $normalizedPhone,
                'response' => $result,
                'status_code' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $result['status']['message'] ?? 'Failed to send SMS',
                'data' => $result
            ];

        } catch (Exception $e) {
            Log::error('SMS service error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Append company signature to SMS message when no custom sender name is configured.
     * When a custom sender name exists, the senderID field conveys identity.
     */
    public function appendSignature(string $message): string
    {
        if ($this->hasCustomSenderName) {
            return $message;
        }

        $companyName = $this->companyName ?: config('app.name', 'MatuNet');
        $signature = "\n\nRegards, {$companyName}.";

        if (!empty($this->companyPhone)) {
            $signature .= "\nCall: {$this->companyPhone}";
        }

        return $message . $signature;
    }

    /**
     * Send OTP SMS
     */
    public function sendOtp(string $phone, string $otp): array
    {
        $message = "Your verification code is: {$otp}. This code will expire in 10 minutes. Do not share this code with anyone.";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send welcome SMS for new signup
     */
    public function sendWelcomeSms(string $phone, string $name): array
    {
        $message = "Hello {$name},\nWelcome to {$this->companyName}! You now have 500MB of free internet access. Enjoy browsing!";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send package activation SMS
     */
    public function sendPackageActivationSms(string $phone, string $packageName, string $validity): array
    {
        $message = "Your {$packageName} package has been activated successfully. Valid for {$validity}. Enjoy your internet access!";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send payment confirmation SMS
     */
    public function sendPaymentConfirmationSms(string $phone, string $amount, string $packageName, string $mpesaCode): array
    {
        $message = "Payment of KES {$amount} received for {$packageName}. M-Pesa Code: {$mpesaCode}. Your internet is now active!";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send voucher code via SMS
     */
    public function sendVoucherSms(string $phone, string $voucherCode, string $packageName, ?string $expiresOn = null): array
    {
        $expiryText = $expiresOn ? " (expires {$expiresOn})" : '';
        $message = "Your voucher for {$packageName}{$expiryText}:\n{$voucherCode}\n\nUse this code on the portal Login page under 'Voucher Code'. Do not share this code.";
        return $this->sendSms($phone, $message);
    }

    /**
     * Send expiry alert SMS
     */
    public function sendExpiryAlertSms(string $phone, string $packageName, int $minutesLeft, string $portalUrl): array
    {
        $message = "Your {$packageName} expires in {$minutesLeft} minutes. Visit {$portalUrl} to purchase more data.";
        return $this->sendSms($phone, $message);
    }

    /**
     * Send auto-renewal confirmation SMS
     */
    public function sendAutoRenewalSms(string $phone, string $packageName, string $newExpiry): array
    {
        $message = "Your {$packageName} has been auto-renewed from your wallet balance. New expiry: {$newExpiry}.";
        return $this->sendSms($phone, $message);
    }

    /**
     * Send wallet low balance SMS
     */
    public function sendWalletLowBalanceSms(string $phone, string $balance, string $packageName): array
    {
        $message = "Your wallet balance is low (KES {$balance}). Top up to auto-renew your {$packageName} and stay connected.";
        return $this->sendSms($phone, $message);
    }

    /**
     * Send data limit alert SMS
     */
    public function sendDataLimitAlertSms(string $phone, string $dataUsed, string $dataLimit): array
    {
        $message = "You have used {$dataUsed} out of {$dataLimit}. Your data is running low. Top up to continue browsing.";
        return $this->sendSms($phone, $message);
    }

    /**
     * Normalize phone number to Kenyan format
     */
    public function normalizePhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle different formats
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            // 7XXXXXXXX -> 07XXXXXXXX
            return '0' . $phone;
        } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            // 07XXXXXXXX -> keep as is
            return $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            // 2547XXXXXXXX -> 07XXXXXXXX
            return '0' . substr($phone, 3);
        } elseif (strlen($phone) === 13 && substr($phone, 0, 4) === '2540') {
            // 25407XXXXXXXX -> 07XXXXXXXX
            return substr($phone, 3);
        }
        
        // If phone number is already in correct format (07XXXXXXXX)
        if (strlen($phone) === 10 && substr($phone, 0, 2) === '07') {
            return $phone;
        }
        
        return null; // Invalid format
    }

    /**
     * Validate phone number
     */
    public function isValidPhoneNumber(string $phone): bool
    {
        return $this->normalizePhoneNumber($phone) !== null;
    }

    /**
     * Get SMS balance (if API supports it)
     */
    public function getBalance(): array
    {
        try {
            if (empty($this->email) || empty($this->bearerToken)) {
                Log::error('Vintex SMS credentials are not configured');
                return [
                    'success' => false,
                    'message' => 'SMS credentials not configured',
                    'data' => null,
                ];
            }

            // use endpoint https://sms.vintextechnologies.com/api/getUnitBalance
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get('https://sms.vintextechnologies.com/api/getUnitBalance?email=' . urlencode($this->email));
            
            $result = $response->json();
            
            if ($response->successful() && isset($result['status']['type']) && $result['status']['type'] === 'success') {
                Log::info('SMS balance checked successfully', [
                    'response' => $result
                ]);

                return [
                    'success' => true,
                    'balance' => $result['data']['balance'],
                ];
            }

            Log::error('SMS balance check failed', [
                'response' => $result,
                'status_code' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $result['status']['message'] ?? 'Failed to check balance',
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to check balance: ' . $e->getMessage()
            ];
        }
    }
}
