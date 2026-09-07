<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use App\Services\WalletService;

class PaymentService
{
    protected MpesaService $mpesaService;
    protected ?PaystackService $paystackService = null;

    public function __construct()
    {
        $this->mpesaService = app(MpesaService::class);

        if (config('services.paystack.secret_key')) {
            $this->paystackService = app(PaystackService::class);
        }
    }

    /**
     * Initiate a payment through the specified gateway.
     */
    public function initiatePayment(Package $package, string $gateway, array $customerData): ?PaymentTransaction
    {
        $reference = Str::uuid()->toString();

        $transaction = PaymentTransaction::create([
            'checkout_request_id' => $reference,
            'phone_number' => $customerData['phone'] ?? '',
            'amount' => $package->price,
            'account_reference' => $customerData['account_reference'] ?? $package->name,
            'transaction_desc' => "Payment for {$package->name}",
            'status' => 'pending',
            'gateway' => $gateway,
            'type' => $customerData['type'] ?? 'one_time',
            'package_id' => $package->id,
            'user_id' => $customerData['user_id'] ?? null,
            'router_id' => $package->router_id ?? null,
        ]);

        try {
            switch ($gateway) {
                case 'mpesa':
                    $this->mpesaService->stkPush(
                        $transaction->phone_number,
                        $transaction->amount,
                        $transaction->account_reference,
                        $transaction->transaction_desc
                    );
                    break;

                case 'paystack':
                    if (!$this->paystackService) {
                        throw new Exception('Paystack is not configured');
                    }
                    $this->paystackService->initializePayment(
                        $transaction->amount,
                        $customerData['email'] ?? '',
                        $reference,
                        ['package_id' => $package->id]
                    );
                    break;

                case 'manual':
                    $transaction->update([
                        'status' => 'completed',
                        'mpesa_receipt_number' => 'MAN-' . strtoupper(Str::random(8)),
                    ]);
                    break;

                case 'wallet':
                    $walletService = app(WalletService::class);
                    $user = User::find($customerData['user_id']);
                    if ($user && $walletService->hasSufficientBalance($user, $package->price)) {
                        $walletService->debitWallet($user, $package->price, "Payment for {$package->name}");
                        $transaction->update([
                            'status' => 'completed',
                            'mpesa_receipt_number' => 'WAL-' . strtoupper(Str::random(8)),
                        ]);
                    } else {
                        $transaction->update(['status' => 'failed', 'result_description' => 'Insufficient wallet balance']);
                    }
                    break;

                default:
                    throw new Exception("Unsupported gateway: {$gateway}");
            }
        } catch (Exception $e) {
            Log::error('Payment initiation failed', [
                'transaction_id' => $transaction->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            $transaction->update([
                'status' => 'failed',
                'result_description' => $e->getMessage(),
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Handle gateway callback.
     */
    public function handleCallback(string $gateway, array $data): PaymentTransaction
    {
        switch ($gateway) {
            case 'mpesa':
                return $this->handleMpesaCallback($data);
            case 'paystack':
                return $this->handlePaystackCallback($data);
            default:
                throw new Exception("Unsupported gateway: {$gateway}");
        }
    }

    /**
     * Check transaction status by polling the gateway.
     */
    public function checkTransactionStatus(PaymentTransaction $transaction): PaymentTransaction
    {
        if ($transaction->gateway === 'mpesa' && $transaction->isPending()) {
            // M-Pesa STK push status is delivered via callback, but we can check timeout
            if ($transaction->isExpired()) {
                $transaction->update(['status' => 'expired']);
            }
        }

        if ($transaction->gateway === 'paystack' && $transaction->isPending() && $this->paystackService) {
            $result = $this->paystackService->verifyTransaction($transaction->checkout_request_id);
            if (isset($result['data']['status'])) {
                if ($result['data']['status'] === 'success') {
                    $transaction->update([
                        'status' => 'completed',
                        'mpesa_receipt_number' => $result['data']['reference'] ?? null,
                    ]);
                } elseif (in_array($result['data']['status'], ['failed', 'abandoned'])) {
                    $transaction->update(['status' => 'failed']);
                }
            }
        }

        return $transaction->fresh();
    }

    /**
     * Initiate a refund for a completed transaction.
     */
    public function initiateRefund(PaymentTransaction $transaction): bool
    {
        if (!$transaction->isRefundable()) {
            return false;
        }

        try {
            if ($transaction->gateway === 'mpesa') {
                // M-Pesa refund API call would go here
                Log::info('M-Pesa refund initiated', ['transaction_id' => $transaction->id]);
            } elseif ($transaction->gateway === 'paystack' && $this->paystackService) {
                $this->paystackService->initiateRefund($transaction);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Refund failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function handleMpesaCallback(array $data): PaymentTransaction
    {
        $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
        $transaction = PaymentTransaction::where('checkout_request_id', $checkoutRequestId)->firstOrFail();

        $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
        $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? '';

        if ($resultCode === 0) {
            $callbackMetadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
            $receiptNumber = null;
            $transactionDate = null;

            foreach ($callbackMetadata as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $receiptNumber = $item['Value'];
                }
                if ($item['Name'] === 'TransactionDate') {
                    $transactionDate = $item['Value'];
                }
            }

            $transaction->update([
                'status' => 'completed',
                'mpesa_receipt_number' => $receiptNumber,
                'transaction_date' => $transactionDate,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'callback_data' => $data,
            ]);

            $this->processPostCompletion($transaction);
        } else {
            $transaction->update([
                'status' => 'failed',
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'callback_data' => $data,
            ]);
        }

        return $transaction->fresh();
    }

    protected function handlePaystackCallback(array $data): PaymentTransaction
    {
        $reference = $data['data']['reference'] ?? null;
        $transaction = PaymentTransaction::where('checkout_request_id', $reference)->firstOrFail();

        $status = $data['data']['status'] ?? 'failed';

        if ($status === 'success') {
            $transaction->update([
                'status' => 'completed',
                'mpesa_receipt_number' => $data['data']['reference'] ?? null,
                'callback_data' => $data,
            ]);

            $this->processPostCompletion($transaction);
        } else {
            $transaction->update([
                'status' => 'failed',
                'callback_data' => $data,
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Post-completion hook: credit wallet if this was a topup transaction.
     */
    protected function processPostCompletion(PaymentTransaction $transaction): void
    {
        if ($transaction->type === 'topup' && $transaction->user_id) {
            $user = User::find($transaction->user_id);
            if ($user) {
                $walletService = app(WalletService::class);
                $walletService->creditWallet(
                    $user,
                    (float) $transaction->amount,
                    'Wallet top-up',
                    $transaction->checkout_request_id
                );
                Log::info('Wallet credited from topup', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'amount' => $transaction->amount,
                ]);
            }
        }
    }
}
