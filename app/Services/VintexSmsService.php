<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VintexSmsService
{
    protected string $apiUrl;
    protected string $email;
    protected string $bearerToken;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.vintex.api_url', 'https://sms.vintextechnologies.com/api/sendMessage');
        $this->email = config('services.vintex.email', 'admin@vintextechnologies.com');
        $this->bearerToken = config('services.vintex.bearer_token');
        $this->senderId = config('services.vintex.sender_id', 'STERKE');
    }

    /**
     * Send SMS message
     */
    public function sendSms(string $phone, string $message): array
    {
        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phone);
            
            if (!$normalizedPhone) {
                throw new Exception('Invalid phone number format');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->post($this->apiUrl . '?email=' . $this->email, [
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
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Send OTP SMS
     */
    public function sendOtp(string $phone, string $otp): array
    {
        $message = "Your verification code is: {$otp}. This code will expire in 10 minutes. Do not share this code with anyone.\n\nRegards, Sterke Digital.";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send welcome SMS for new signup
     */
    public function sendWelcomeSms(string $phone, string $name): array
    {
        $message = "Hello {$name},\nWelcome to Sterke Digital! You now have 500MB of free internet access. Enjoy browsing!\n\nRegards, Sterke Digital.";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send package activation SMS
     */
    public function sendPackageActivationSms(string $phone, string $packageName, string $validity): array
    {
        $message = "Your {$packageName} package has been activated successfully. Valid for {$validity}. Enjoy your internet access!\n\nRegards, Sterke Digital.";
        
        return $this->sendSms($phone, $message);
    }

    /**
     * Send payment confirmation SMS
     */
    public function sendPaymentConfirmationSms(string $phone, string $amount, string $packageName, string $mpesaCode): array
    {
        $message = "Payment of KES {$amount} received for {$packageName}. M-Pesa Code: {$mpesaCode}. Your internet is now active!\n\nRegards, Sterke Digital.";
        
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
            // use endpoint https://sms.vintextechnologies.com/api/getUnitBalance
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get('https://sms.vintextechnologies.com/api/getUnitBalance');
            
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
