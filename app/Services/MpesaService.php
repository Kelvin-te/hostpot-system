<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MpesaService
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $shortcode;
    protected $passkey;
    protected $callbackUrl;

    public function __construct()
    {
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortcode = config('mpesa.shortcode');
        $this->passkey = config('mpesa.passkey');
        $this->callbackUrl = config('mpesa.callback_url');
    }

    /**
     * Get M-Pesa access token
     */
    public function getAccessToken(): ?string
    {
        // Validate configuration
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            Log::error('M-Pesa consumer key/secret not configured');
            return null;
        }

        try {

            $url = config('mpesa.urls.oauth');
            $response = Http::withOptions([
                'verify' => false,
            ])->withBasicAuth($this->consumerKey, $this->consumerSecret)->get($url);

            Log::info('M-Pesa access is good', [
                'response' => $response,
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                return $accessToken;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception getting M-Pesa access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Initiate STK Push payment
     */
    public function stkPush(string $phoneNumber, float $amount, string $accountReference, string $transactionDesc): array
    {
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to get access token'
            ];
        }

        Log::info('M-Pesa access token retrieved', [
            'token_received' => true, 
            'token' => $accessToken
        ]);

        try {
            // Format phone number (remove leading 0 and add 254)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Validate required config for STK Push
            if (empty($this->shortcode) || empty($this->passkey)) {
                Log::error('M-Pesa shortcode or passkey not configured');
                return [
                    'success' => false,
                    'message' => 'Payment provider not configured'
                ];
            }

            // Generate timestamp
            $timestamp = date('YmdHis');
            
            // Generate password
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $url = config('mpesa.urls.stk_push');

            $requestData = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $amount,
                'PartyA' => $formattedPhone,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $formattedPhone,
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $transactionDesc
            ];

            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ]
            ])->post($url, $requestData);

            Log::info('M-Pesa STK Push response', [
                'token' => $accessToken,
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                ]
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                // Create payment transaction record
                $transaction = PaymentTransaction::create([
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'merchant_request_id' => $responseData['MerchantRequestID'],
                    'phone_number' => $phoneNumber,
                    'amount' => $amount,
                    'account_reference' => $accountReference,
                    'transaction_desc' => $transactionDesc,
                    'status' => 'pending',
                    'response_code' => $responseData['ResponseCode'],
                    'response_description' => $responseData['ResponseDescription'],
                    'customer_message' => $responseData['CustomerMessage'] ?? null
                ]);

                Log::info('STK Push initiated successfully', [
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'phone' => $phoneNumber,
                    'amount' => $amount
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['CustomerMessage'] ?? 'Payment request sent to your phone',
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'merchant_request_id' => $responseData['MerchantRequestID'],
                    'transaction_id' => $transaction->id
                ];
            }

            $requestLog = $requestData;
            unset($requestLog['Password']);
            Log::error('STK Push failed', [
                'response' => $responseData,
                'request' => $requestLog
            ]);

            return [
                'success' => false,
                'message' => $responseData['errorMessage'] ?? 'Payment request failed'
            ];

        } catch (\Exception $e) {
            Log::error('Exception during STK Push', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'amount' => $amount
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ];
        }
    }

    /**
     * Query STK Push transaction status
     */
    public function queryTransaction(string $checkoutRequestId): array
    {
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to get access token'
            ];
        }

        try {
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $url = config('mpesa.urls.stk_query');

            $requestData = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($url, $requestData);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to query transaction status'
            ];

        } catch (\Exception $e) {
            Log::error('Exception querying STK Push status', [
                'error' => $e->getMessage(),
                'checkout_request_id' => $checkoutRequestId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check payment status'
            ];
        }
    }

    /**
     * Handle M-Pesa callback
     */
    public function handleCallback(array $callbackData): bool
    {
        try {
            $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? null;
            $resultDesc = $callbackData['Body']['stkCallback']['ResultDesc'] ?? null;

            if (!$checkoutRequestId) {
                Log::error('Invalid M-Pesa callback data', $callbackData);
                return false;
            }

            // Find the transaction
            $transaction = PaymentTransaction::where('checkout_request_id', $checkoutRequestId)->first();

            if (!$transaction) {
                Log::error('Transaction not found for callback', [
                    'checkout_request_id' => $checkoutRequestId
                ]);
                return false;
            }

            // Update transaction status
            if ($resultCode == 0) {
                // Payment successful
                $callbackMetadata = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
                $mpesaReceiptNumber = null;
                $transactionDate = null;
                $phoneNumber = null;

                foreach ($callbackMetadata as $item) {
                    switch ($item['Name']) {
                        case 'MpesaReceiptNumber':
                            $mpesaReceiptNumber = $item['Value'];
                            break;
                        case 'TransactionDate':
                            $transactionDate = $item['Value'];
                            break;
                        case 'PhoneNumber':
                            $phoneNumber = $item['Value'];
                            break;
                    }
                }

                $transaction->update([
                    'status' => 'completed',
                    'mpesa_receipt_number' => $mpesaReceiptNumber,
                    'transaction_date' => $transactionDate ? Carbon::createFromFormat('YmdHis', $transactionDate) : null,
                    'result_code' => $resultCode,
                    'result_description' => $resultDesc,
                    'callback_data' => $callbackData
                ]);

                Log::info('Payment completed successfully', [
                    'transaction_id' => $transaction->id,
                    'mpesa_receipt' => $mpesaReceiptNumber
                ]);

            } else {
                // Payment failed
                $transaction->update([
                    'status' => 'failed',
                    'result_code' => $resultCode,
                    'result_description' => $resultDesc,
                    'callback_data' => $callbackData
                ]);

                Log::info('Payment failed', [
                    'transaction_id' => $transaction->id,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Exception handling M-Pesa callback', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            return false;
        }
    }

    /**
     * Format phone number for M-Pesa (254XXXXXXXXX format)
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle different formats
        if (substr($phone, 0, 3) === '254') {
            return $phone; // Already in correct format
        } elseif (substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1); // Remove leading 0 and add 254
        } elseif (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') {
            return '254' . $phone; // Add 254 prefix
        }
        
        return $phone; // Return as is if format is unclear
    }

}
