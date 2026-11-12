<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payments.index');
    }

    /**
     * Refund a completed transaction. For wallet payments the balance is
     * credited back automatically; for M-Pesa the record is marked refunded
     * and the operator performs the B2C/manual reversal (reconciliation
     * report picks it up).
     */
    public function refund(Request $request, PaymentTransaction $transaction, WalletService $wallet)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        if ($transaction->status !== 'completed' || !$transaction->isRefundable()) {
            return back()->with('error', 'Transaction is not refundable.');
        }

        DB::transaction(function () use ($transaction, $request, $wallet) {
            $transaction->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $request->reason,
                'refunded_by' => $request->user('staff')?->id,
            ]);

            // Reverse wallet-funded payments automatically
            if ($transaction->gateway === 'wallet' && $transaction->user) {
                $wallet->creditWallet(
                    $transaction->user,
                    (float) $transaction->amount,
                    "Refund for transaction {$transaction->id}",
                    'refund_' . $transaction->id
                );
            }
        });

        $note = $transaction->gateway === 'mpesa'
            ? ' — remember to process the M-Pesa B2C/manual reversal'
            : '';

        return back()->with('success', "Transaction refunded{$note}.");
    }
}
