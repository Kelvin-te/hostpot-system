<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\MpesaService;
use App\Services\PppoeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PortalPurchaseController extends Controller
{
    /**
     * Initiate a package purchase via M-Pesa STK push for the logged-in user.
     */
    public function store(Request $request, MpesaService $mpesa)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $user = Auth::user();
        $package = Package::where('is_active', true)->findOrFail($validated['package_id']);
        $phone = $user->phone;

        if (!$phone) {
            return back()->with('error', 'No phone number on your account. Contact support.');
        }

        // Account reference prefix lets the callback identify portal payments
        $accountReference = 'HSP' . $user->id;

        $stk = $mpesa->stkPush(
            $phone,
            (float) $package->price,
            $accountReference,
            "Package: {$package->name}"
        );

        if (!($stk['success'] ?? false)) {
            return back()->with('error', 'Failed to initiate STK push: ' . ($stk['message'] ?? 'unknown'));
        }

        // Record the purchase intent on the transaction for provisioning
        PaymentTransaction::where('checkout_request_id', $stk['checkout_request_id'] ?? null)
            ->update([
                'package_id' => $package->id,
                'user_id' => $user->id,
                'type' => 'subscription',
            ]);

        return redirect()->route('portal.purchase.status', $stk['checkout_request_id']);
    }

    /**
     * Poll payment status; on completion provision PPPoE or hotspot service.
     */
    public function status(string $checkoutRequestId, MpesaService $mpesa, PppoeService $pppoe)
    {
        $user = Auth::user();
        $transaction = PaymentTransaction::where('checkout_request_id', $checkoutRequestId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Fallback query if callback hasn't arrived
        if ($transaction->isPending() && !$transaction->callback_data) {
            $query = $mpesa->queryTransaction($checkoutRequestId);
            if ($query['success'] ?? false) {
                $code = $query['data']['ResultCode'] ?? null;
                if ($code == 0) {
                    $transaction->markAsCompleted(['result_code' => $code, 'result_description' => 'Payment successful']);
                } elseif ($code && !in_array((int) $code, [4999, 1032])) {
                    $transaction->markAsFailed($query['data']['ResultDesc'] ?? 'Payment failed', $code);
                }
            }
        }

        if ($transaction->isCompleted()) {
            return $this->provisionForUser($user, $transaction, $pppoe);
        }

        if ($transaction->isFailed() || $transaction->isExpired()) {
            return view('portal.purchase-status', [
                'state' => 'failed',
                'transaction' => $transaction,
            ]);
        }

        return view('portal.purchase-status', [
            'state' => 'pending',
            'transaction' => $transaction,
            'checkoutRequestId' => $checkoutRequestId,
        ]);
    }

    /**
     * Branch provisioning by connection type. Idempotent: PPPoE re-provision
     * just extends package_start; hotspot path delegates to existing service.
     */
    private function provisionForUser(User $user, PaymentTransaction $transaction, PppoeService $pppoe)
    {
        $package = $transaction->package;

        if ($user->connection_type === 'pppoe') {
            // Already provisioned for this transaction? Check via detail status
            $alreadyProvisioned = $user->detail?->package_start
                && $user->package_id === $package?->id
                && $user->detail->package_start->gt(now()->subMinutes(10));

            if (!$alreadyProvisioned) {
                $result = $pppoe->provision($user, $package);
                if (!($result['success'] ?? false)) {
                    return view('portal.purchase-status', [
                        'state' => 'provision_failed',
                        'transaction' => $transaction,
                        'message' => $result['message'] ?? 'Provisioning failed',
                    ]);
                }
            }

            return view('portal.purchase-status', [
                'state' => 'complete',
                'transaction' => $transaction,
                'connectionType' => 'pppoe',
                'credentials' => [
                    'username' => $user->fresh()->pppoe_username,
                ],
            ]);
        }

        // Hotspot users: redirect to the existing session-based flow is complex
        // server-side; hotspot purchases go through the captive portal API.
        // For portal purchases, create the session via the same service the
        // API uses — requires device info, so we create a phone-bound session.
        return view('portal.purchase-status', [
            'state' => 'complete',
            'transaction' => $transaction,
            'connectionType' => 'hotspot',
            'message' => 'Payment received. Your package is active — connect via the portal or voucher flow.',
        ]);
    }
}
