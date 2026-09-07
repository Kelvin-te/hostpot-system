<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl;
    protected ?string $callbackUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
        $this->callbackUrl = config('services.paystack.callback_url');
    }

    /**
     * Initialize a Paystack transaction.
     */
    public function initializePayment(float $amount, string $email, string $reference, array $metadata = []): array
    {
        if (empty($this->secretKey)) {
            throw new Exception('Paystack secret key is not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/transaction/initialize", [
            'email' => $email,
            'amount' => (int)($amount * 100), // Paystack expects kobo
            'reference' => $reference,
            'callback_url' => $this->callbackUrl,
            'metadata' => $metadata,
        ]);

        $result = $response->json();

        if (!$response->successful()) {
            Log::error('Paystack payment initialization failed', [
                'reference' => $reference,
                'response' => $result,
            ]);
            throw new Exception($result['message'] ?? 'Failed to initialize Paystack payment');
        }

        return $result;
    }

    /**
     * Verify a Paystack transaction.
     */
    public function verifyTransaction(string $reference): array
    {
        if (empty($this->secretKey)) {
            throw new Exception('Paystack secret key is not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

        $result = $response->json();

        if (!$response->successful()) {
            Log::error('Paystack transaction verification failed', [
                'reference' => $reference,
                'response' => $result,
            ]);
            throw new Exception($result['message'] ?? 'Failed to verify Paystack transaction');
        }

        return $result;
    }

    /**
     * Initiate a refund via Paystack.
     */
    public function initiateRefund(PaymentTransaction $transaction): array
    {
        if (empty($this->secretKey)) {
            throw new Exception('Paystack secret key is not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/refund", [
            'transaction' => $transaction->checkout_request_id,
            'amount' => (int)($transaction->amount * 100),
        ]);

        $result = $response->json();

        if (!$response->successful()) {
            Log::error('Paystack refund failed', [
                'transaction_id' => $transaction->id,
                'response' => $result,
            ]);
            throw new Exception($result['message'] ?? 'Failed to initiate Paystack refund');
        }

        return $result;
    }

    /**
     * Handle Paystack webhook payload.
     */
    public function handleWebhook(array $payload): bool
    {
        $event = $payload['event'] ?? '';

        switch ($event) {
            case 'charge.success':
                $reference = $payload['data']['reference'] ?? null;
                if ($reference) {
                    $transaction = PaymentTransaction::where('checkout_request_id', $reference)->first();
                    if ($transaction && $transaction->isPending()) {
                        $transaction->update([
                            'status' => 'completed',
                            'mpesa_receipt_number' => $payload['data']['reference'] ?? null,
                            'callback_data' => $payload,
                        ]);
                    }
                }
                return true;

            case 'charge.failed':
                $reference = $payload['data']['reference'] ?? null;
                if ($reference) {
                    $transaction = PaymentTransaction::where('checkout_request_id', $reference)->first();
                    if ($transaction && $transaction->isPending()) {
                        $transaction->update([
                            'status' => 'failed',
                            'callback_data' => $payload,
                        ]);
                    }
                }
                return true;

            default:
                Log::info('Paystack webhook received', ['event' => $event]);
                return true;
        }
    }
}
