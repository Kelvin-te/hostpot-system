<?php

namespace App\Http\Controllers;

use App\Exceptions\ActiveSessionConflictException;
use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Voucher;
use App\Services\DeviceIdentificationService;
use App\Services\HotspotAuthorizationService;
use App\Services\HotspotSessionService;
use App\Services\MpesaService;
use App\Services\VintexSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HotspotApiController extends Controller
{
    protected HotspotSessionService $sessionService;
    protected DeviceIdentificationService $deviceService;
    protected HotspotAuthorizationService $authorizationService;
    protected MpesaService $mpesaService;
    protected VintexSmsService $smsService;

    public function __construct(
        HotspotSessionService $sessionService,
        DeviceIdentificationService $deviceService,
        HotspotAuthorizationService $authorizationService,
        MpesaService $mpesaService,
        VintexSmsService $smsService
    ) {
        $this->sessionService = $sessionService;
        $this->deviceService = $deviceService;
        $this->authorizationService = $authorizationService;
        $this->mpesaService = $mpesaService;
        $this->smsService = $smsService;
    }

    /**
     * Resolve router from platformID (router identifier) or host (IP).
     */
    protected function resolveRouter(Request $request): ?Router
    {
        $platformID = $request->input('platformID');
        $host = $request->input('host');

        if ($platformID) {
            $router = Router::where('identifier', $platformID)->first();
            if ($router) {
                return $router;
            }
        }

        if ($host) {
            $router = Router::where('ip_address', $host)->first();
            if ($router) {
                return $router;
            }
        }

        return null;
    }

    /**
     * Merge MAC/IP from JSON body into request query params so
     * DeviceIdentificationService can find them.
     */
    protected function prepareRequest(Request $request): Request
    {
        $mac = $request->input('mac');
        $ip = $request->input('ip');

        if ($mac) {
            $request->merge(['mac' => $mac]);
        }

        if ($ip) {
            $request->merge(['ip' => $ip]);
        }

        return $request;
    }

    /**
     * Format packages for API response (matches reference shape).
     */
    protected function formatPackages($packages, Router $router): array
    {
        $result = [];
        foreach ($packages as $pkg) {
            $result[] = [
                'id' => (string) $pkg->id,
                'name' => $pkg->name,
                'price' => (string) $pkg->price,
                'period' => $pkg->getValidityDisplay(),
                'speed' => $this->formatSpeed($pkg),
                'devices' => (string) ($pkg->shared_users ?: 1),
                'usage' => $pkg->data_cap ?: 'Unlimited',
                'data_cap' => $pkg->data_cap,
                'rate_limit' => $this->formatRateLimitForApi($pkg),
            ];
        }
        return $result;
    }

    protected function formatSpeed(Package $pkg): string
    {
        $up = $pkg->bandwidth_upload ?? 0;
        $down = $pkg->bandwidth_download ?? 0;
        if ($up && $down) {
            return $down . 'M/' . $up . 'M';
        }
        return 'Unlimited';
    }

    protected function formatRateLimitForApi(Package $pkg): string
    {
        $up = $pkg->bandwidth_upload ?? 0;
        $down = $pkg->bandwidth_download ?? 0;
        if (!$up && !$down) {
            return '';
        }
        $dl = $down ? (int) $down . 'M' : '0';
        $ul = $up ? (int) $up . 'M' : '0';
        return $ul . '/' . $dl;
    }

    /**
     * POST /api/hotspot/packages
     * Fetch packages for a router.
     */
    public function packages(Request $request)
    {
        $router = $this->resolveRouter($request);

        if (!$router) {
            return response()->json([
                'type' => 'error',
                'message' => 'Router not identified. Please reconnect to the WiFi hotspot.',
            ], 200);
        }

        $packages = Package::where('router_id', $router->id)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        // Filter out free packages if device has already used them
        $packages = $this->filterFreePackagesForDevice($request, $packages);

        return response()->json([
            'type' => 'success',
            'packages' => $this->formatPackages($packages, $router),
        ]);
    }

    /**
     * Filter free packages for devices that have already used them.
     */
    protected function filterFreePackagesForDevice(Request $request, $packages)
    {
        $mac = $request->input('mac');
        if (!$mac) {
            return $packages;
        }

        $macAddress = $this->deviceService->normalizeMacAddress($mac);
        if (!$macAddress) {
            return $packages;
        }

        // Get the IDs of free packages this device has already used
        $usedFreePackageIds = HotspotSession::where('mac_address', $macAddress)
            ->whereHas('package', function ($q) {
                $q->where('price', 0);
            })
            ->pluck('package_id')
            ->unique()
            ->toArray();

        if (!empty($usedFreePackageIds)) {
            $packages = $packages->filter(function ($pkg) use ($usedFreePackageIds) {
                return $pkg->price > 0 || !in_array($pkg->id, $usedFreePackageIds);
            });
        }

        return $packages;
    }

    /**
     * POST /api/hotspot/activate-free
     * Activate a free package immediately without payment.
     */
    public function activateFreePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packageId' => 'required|exists:packages,id',
            'platformID' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $router = $this->resolveRouter($request);
        $package = Package::findOrFail($request->input('packageId'));

        if (!$router || $router->id !== $package->router_id) {
            return response()->json([
                'success' => false,
                'message' => 'Router not identified.',
            ]);
        }

        if (floatval($package->price) != 0) {
            return response()->json([
                'success' => false,
                'message' => 'Package is not free.',
            ]);
        }

        $this->prepareRequest($request);

        // Prevent the same device from claiming the same free package more than once
        $macAddress = $this->deviceService->getMacAddress($request);
        if ($macAddress) {
            $hasUsedThisPackage = HotspotSession::where('mac_address', $macAddress)
                ->where('package_id', $package->id)
                ->exists();

            if ($hasUsedThisPackage) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already used this free package on this device.',
                ]);
            }
        }

        try {
            $session = $this->sessionService->createSessionForPackage(
                $request,
                $package,
                null,
                $this->deviceService->getStableClientIdentifier($request),
                null
            );

            $authorization = $session->authorization;

            Log::info('Hotspot API: Free package activated', [
                'session_id' => $session->session_id,
                'package_id' => $package->id,
                'device_info' => $this->deviceService->getDeviceInfo($request),
            ]);

            return response()->json([
                'success' => true,
                'username' => $authorization?->hotspot_username ?? $session->mikrotik_username,
                'password' => $authorization?->hotspotPassword() ?? $session->session_id,
                'message' => 'Free package activated. Connecting you...',
            ]);
        } catch (ActiveSessionConflictException $e) {
            $existingSession = $e->existingSession;
            $authorization = $existingSession->authorization;

            return response()->json([
                'success' => true,
                'username' => $authorization?->hotspot_username ?? $existingSession->mikrotik_username,
                'password' => $authorization?->hotspotPassword() ?? $existingSession->session_id,
                'message' => 'You already have an active session. Connecting you...',
            ]);
        } catch (\Exception $e) {
            Log::error('Hotspot API: Free package activation failed', [
                'error' => $e->getMessage(),
                'package_id' => $package->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Activation failed. Please try again.',
            ]);
        }
    }

    /**
     * POST /api/hotspot/stkpush
     * Initiate M-PESA STK push for a package.
     */
    public function stkPush(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
            'packageId' => 'required|exists:packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $router = $this->resolveRouter($request);
        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'Router not identified.',
            ]);
        }

        $package = Package::findOrFail($request->input('packageId'));
        if ($package->router_id !== $router->id) {
            return response()->json([
                'success' => false,
                'message' => 'Package not available on this router.',
            ]);
        }

        try {
            $tenantId = strtoupper(preg_replace('/[^A-Z0-9]/i', '', config('mpesa.tenant_id', 'default')));
            $accountReference = substr('HSP' . $tenantId, 0, 12);
            $transactionDesc = $package->name . ' - Internet Package';

            $stkResult = $this->mpesaService->stkPush(
                $request->input('phone'),
                $package->price,
                $accountReference,
                $transactionDesc
            );

            if (!$stkResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $stkResult['message'],
                ]);
            }

            $transaction = PaymentTransaction::where('checkout_request_id', $stkResult['checkout_request_id'])->first();
            if ($transaction) {
                $transaction->update([
                    'package_id' => $package->id,
                    'router_id' => $router->id,
                    'account_reference' => $accountReference,
                ]);
            }

            Log::info('Hotspot API: STK Push initiated', [
                'checkout_request_id' => $stkResult['checkout_request_id'],
                'package' => $package->name,
                'router_id' => $router->id,
                'mac' => $request->input('mac'),
            ]);

            return response()->json([
                'success' => true,
                'message' => $stkResult['message'] ?? 'Payment request sent to your phone',
                'checkoutRequestId' => $stkResult['checkout_request_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('Hotspot API: STK Push failed', [
                'error' => $e->getMessage(),
                'package_id' => $package->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.',
            ]);
        }
    }

    /**
     * POST /api/hotspot/payment-status
     * Poll payment status. On success, creates hotspot session and returns credentials.
     */
    public function paymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkoutRequestId' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Invalid request.',
            ]);
        }

        $checkoutRequestId = $request->input('checkoutRequestId');
        $transaction = PaymentTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Transaction not found.',
            ]);
        }

        $callbackReceived = $transaction->callback_data !== null;
        $statusResolved = !$transaction->isPending() || $callbackReceived;

        // If still pending, query Safaricom as fallback
        if ($transaction->isPending() && !$transaction->isExpired() && !$callbackReceived) {
            $queryResult = $this->mpesaService->queryTransaction($checkoutRequestId);

            if ($queryResult['success']) {
                $responseData = $queryResult['data'];
                $resultCode = $responseData['ResultCode'] ?? null;
                $resultDesc = $responseData['ResultDesc'] ?? null;

                $isProcessing = $resultDesc && (
                    str_contains(strtolower($resultDesc), 'processing')
                    || str_contains(strtolower($resultDesc), 'under process')
                );
                $knownPendingCodes = [4999];

                if ($isProcessing || in_array((int) $resultCode, $knownPendingCodes, true)) {
                    // Keep pending
                } elseif ($resultCode == 0) {
                    $transaction->markAsCompleted([
                        'result_code' => $resultCode,
                        'result_description' => $resultDesc ?? 'Payment successful',
                    ]);
                } elseif ($resultCode && $resultCode != 1032) {
                    $transaction->markAsFailed(
                        $resultDesc ?? 'Payment failed',
                        $resultCode
                    );
                }
            }
        }

        if ($transaction->isCompleted()) {
            try {
                // Idempotency: if session already created, return existing credentials
                if ($transaction->session_id) {
                    $existingSession = HotspotSession::where('session_id', $transaction->session_id)->first();
                    if ($existingSession) {
                        $authorization = $existingSession->authorization;
                        return response()->json([
                            'status' => 'COMPLETE',
                            'loginCode' => $authorization?->hotspot_username ?? $existingSession->mikrotik_username,
                            'password' => $authorization?->hotspotPassword() ?? $existingSession->session_id,
                            'message' => 'Payment successful. Connecting you...',
                        ]);
                    }
                }

                // Create session
                $this->prepareRequest($request);
                $session = $this->sessionService->createSessionForPackage(
                    $request,
                    $transaction->package,
                    null,
                    $request->input('phone'),
                    $transaction->id
                );

                $transaction->update(['session_id' => $session->session_id]);

                $authorization = $session->authorization;

                Log::info('Hotspot API: Session created after payment', [
                    'session_id' => $session->session_id,
                    'transaction_id' => $transaction->id,
                ]);

                return response()->json([
                    'status' => 'COMPLETE',
                    'loginCode' => $authorization?->hotspot_username ?? $session->mikrotik_username,
                    'password' => $authorization?->hotspotPassword() ?? $session->session_id,
                    'message' => 'Payment successful. Connecting you...',
                ]);
            } catch (ActiveSessionConflictException $e) {
                $existingSession = $e->existingSession;
                return response()->json([
                    'status' => 'COMPLETE',
                    'loginCode' => $existingSession->authorization?->hotspot_username ?? $existingSession->mikrotik_username,
                    'password' => $existingSession->authorization?->hotspotPassword() ?? $existingSession->session_id,
                    'message' => 'You already have an active session. Connecting you...',
                ]);
            } catch (\Exception $e) {
                Log::error('Hotspot API: Failed to create session after payment', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id,
                ]);

                return response()->json([
                    'status' => 'FAILED',
                    'message' => 'Payment received but activation failed. Please contact support.',
                ]);
            }
        }

        if ($transaction->isFailed()) {
            return response()->json([
                'status' => 'FAILED',
                'message' => $transaction->result_description ?? 'Payment failed. Please try again.',
            ]);
        }

        return response()->json([
            'status' => 'PENDING',
            'message' => 'Waiting for payment confirmation...',
        ]);
    }

    /**
     * POST /api/hotspot/verify-voucher
     * Verify a voucher code and return credentials for CHAP login.
     */
    public function verifyVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|min:3|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid voucher code.',
            ]);
        }

        $voucher = Voucher::findByCode($request->input('code'));

        if (!$voucher || !$voucher->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found or expired.',
            ]);
        }

        $router = $this->resolveRouter($request);
        $voucherPackage = $voucher->package;

        // Vouchers are open across routers. If the voucher's package belongs to
        // a different router, find an equivalent package on the current router
        // so the session is provisioned where the user actually is.
        $targetPackage = $voucherPackage;
        if ($router && $voucherPackage && $voucherPackage->router_id !== $router->id) {
            $targetPackage = Package::where('router_id', $router->id)
                ->where('is_active', true)
                ->where('price', $voucherPackage->price)
                ->where(function ($q) use ($voucherPackage) {
                    $q->where('validity_minutes', $voucherPackage->validity_minutes)
                      ->orWhere('session_timeout', $voucherPackage->session_timeout)
                      ->orWhere('validity_days', $voucherPackage->validity_days);
                })
                ->where('data_cap', $voucherPackage->data_cap)
                ->where('bandwidth_upload', $voucherPackage->bandwidth_upload)
                ->where('bandwidth_download', $voucherPackage->bandwidth_download)
                ->first();

            if (!$targetPackage) {
                Log::warning('Hotspot API: Voucher used on router with no equivalent package', [
                    'voucher_id' => $voucher->id,
                    'voucher_package_id' => $voucherPackage->id,
                    'voucher_router_id' => $voucherPackage->router_id,
                    'current_router_id' => $router->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'This voucher is not valid on this hotspot.',
                ]);
            }
        }

        if ($voucher->isUsed()) {
            // Check if same device
            $mac = $request->input('mac');
            $macAddress = $mac ? $this->deviceService->normalizeMacAddress($mac) : null;
            $existingSession = $voucher->session;

            if ($existingSession && $macAddress && $existingSession->mac_address === $macAddress) {
                if ($existingSession->isActive() || $existingSession->isReconnectable()) {
                    $authorization = $existingSession->authorization;
                    return response()->json([
                        'success' => true,
                        'code' => $authorization?->hotspot_username ?? $existingSession->mikrotik_username,
                        'password' => $authorization?->hotspotPassword() ?? $existingSession->session_id,
                        'message' => 'Voucher verified. Connecting you...',
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'This voucher has already been used.',
            ]);
        }

        try {
            $this->prepareRequest($request);
            $session = $this->sessionService->authenticateUser(
                $request,
                $request->input('code'),
                null,
                $targetPackage
            );

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher verification failed.',
                ]);
            }

            $authorization = $session->authorization;

            return response()->json([
                'success' => true,
                'code' => $authorization?->hotspot_username ?? $session->mikrotik_username,
                'password' => $authorization?->hotspotPassword() ?? $session->session_id,
                'message' => 'Voucher verified. Connecting you...',
            ]);
        } catch (\Exception $e) {
            Log::error('Hotspot API: Voucher verification failed', [
                'error' => $e->getMessage(),
                'code' => $request->input('code'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Voucher verification failed. Please try again.',
            ]);
        }
    }

    /**
     * POST /api/hotspot/lookup-code
     * Lookup a payment by phone number or M-PESA code.
     */
    public function lookupCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'platformID' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.',
            ]);
        }

        $router = $this->resolveRouter($request);
        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'Router not identified.',
            ]);
        }

        $input = $request->input('phone');
        $mac = $request->input('mac');
        $macAddress = $mac ? $this->deviceService->normalizeMacAddress($mac) : null;

        // Try to find by M-PESA receipt number
        $transaction = PaymentTransaction::where('mpesa_receipt_number', $input)
            ->where('router_id', $router->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        // Try by phone number
        if (!$transaction) {
            $transaction = PaymentTransaction::where('phone_number', $input)
                ->where('router_id', $router->id)
                ->where('status', 'completed')
                ->latest()
                ->first();
        }

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No active session found. Please try again.',
            ]);
        }

        $package = $transaction->package;

        // An expired subscription should not create a new session. The payment
        // is only valid for the package's duration starting from when it completed.
        $completedAt = $transaction->completed_at ?? $transaction->updated_at;
        if ($completedAt && $package) {
            $validityMinutes = $package->getValidityMinutes();
            $sessionTimeoutHours = $package->session_timeout;
            $validityDays = $package->validity_days;

            $validUntil = null;
            if ($validityMinutes) {
                $validUntil = $completedAt->copy()->addMinutes($validityMinutes);
            } elseif ($sessionTimeoutHours) {
                $validUntil = $completedAt->copy()->addHours($sessionTimeoutHours);
            } elseif ($validityDays) {
                $validUntil = $completedAt->copy()->addDays($validityDays);
            }

            if ($validUntil && $validUntil <= now()) {
                Log::info('Hotspot API: Already-paid lookup rejected — payment has expired', [
                    'transaction_id' => $transaction->id,
                    'completed_at' => $completedAt->toDateTimeString(),
                    'valid_until' => $validUntil->toDateTimeString(),
                    'package_id' => $package->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Your previous payment has expired. Please make a new payment.',
                ]);
            }
        }

        $this->prepareRequest($request);
        $macAddress = $request->input('mac') ? $this->deviceService->normalizeMacAddress($request->input('mac')) : null;

        // If this device already has an active/reconnectable session for this
        // router, return it instead of creating another one.
        $existingSession = null;
        if ($macAddress) {
            $existingSession = HotspotSession::where('mac_address', $macAddress)
                ->whereHas('package', function ($q) use ($router) {
                    $q->where('router_id', $router->id);
                })
                ->where(function ($q) {
                    $q->where('status', 'active')
                      ->orWhere(function ($sq) {
                          $sq->where('status', 'disconnected')
                             ->where('expires_at', '>', now());
                      });
                })
                ->latest('created_at')
                ->first();
        }

        if ($existingSession) {
            $authorization = $existingSession->authorization;
            Log::info('Hotspot API: Already-paid lookup returned existing device session', [
                'transaction_id' => $transaction->id,
                'session_id' => $existingSession->session_id,
            ]);

            return response()->json([
                'success' => true,
                'username' => $authorization?->hotspot_username ?? $existingSession->mikrotik_username,
                'password' => $authorization?->hotspotPassword() ?? $existingSession->session_id,
                'message' => 'Code found. Connecting you...',
            ]);
        }

        // Check if session exists
        if ($transaction->session_id) {
            $session = HotspotSession::where('session_id', $transaction->session_id)
                ->where(function ($q) {
                    $q->where('status', 'active')
                      ->orWhere(function ($sq) {
                          $sq->where('status', 'disconnected')
                             ->where('expires_at', '>', now());
                      });
                })
                ->first();

            if ($session) {
                $authorization = $session->authorization;
                return response()->json([
                    'success' => true,
                    'username' => $authorization?->hotspot_username ?? $session->mikrotik_username,
                    'password' => $authorization?->hotspotPassword() ?? $session->session_id,
                    'message' => 'Code found. Connecting you...',
                ]);
            }
        }

        // Transaction completed but no active session — create one if still valid
        try {
            $session = $this->sessionService->createSessionForPackage(
                $request,
                $transaction->package,
                null,
                $transaction->phone_number,
                $transaction->id
            );

            $transaction->update(['session_id' => $session->session_id]);

            $authorization = $session->authorization;

            Log::info('Hotspot API: Session created after payment lookup', [
                'session_id' => $session->session_id,
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'username' => $authorization?->hotspot_username ?? $session->mikrotik_username,
                'password' => $authorization?->hotspotPassword() ?? $session->session_id,
                'message' => 'Code found. Connecting you...',
            ]);
        } catch (ActiveSessionConflictException $e) {
            $existingSession = $e->existingSession;
            $authorization = $existingSession->authorization;
            return response()->json([
                'success' => true,
                'username' => $authorization?->hotspot_username ?? $existingSession->mikrotik_username,
                'password' => $authorization?->hotspotPassword() ?? $existingSession->session_id,
                'message' => 'Code found. Connecting you...',
            ]);
        } catch (\Exception $e) {
            Log::error('Hotspot API: Code lookup failed', [
                'error' => $e->getMessage(),
                'input' => $input,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not activate session. Please contact support.',
            ]);
        }
    }

    /**
     * POST /api/hotspot/logout-notify
     * Notify the app that a user has logged out from the MikroTik hotspot.
     */
    public function logoutNotify(Request $request)
    {
        $mac = $request->input('mac');
        $router = $this->resolveRouter($request);

        if (!$mac || !$router) {
            return response()->json(['success' => false]);
        }

        $macAddress = $this->deviceService->normalizeMacAddress($mac);

        $session = HotspotSession::where('mac_address', $macAddress)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($session) {
            try {
                $this->sessionService->terminateSession($session);
                Log::info('Hotspot API: Logout notification processed', [
                    'session_id' => $session->session_id,
                    'mac' => $macAddress,
                ]);
            } catch (\Exception $e) {
                Log::error('Hotspot API: Logout notification failed', [
                    'error' => $e->getMessage(),
                    'session_id' => $session->session_id,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle M-Pesa callback
     */
    public function mpesaCallback(Request $request)
    {
        // Log as much as possible before any processing so we can diagnose
        // delivery issues even if parsing or service logic fails.
        Log::info('M-Pesa callback raw request received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'content' => $request->getContent(),
            'parsed' => $request->all(),
        ]);

        try {
            $callbackData = $request->all();

            $success = $this->mpesaService->handleCallback($callbackData);

            if ($success) {
                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success'
                ]);
            }

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Failed to process callback'
            ]);

        } catch (\Exception $e) {
            Log::error('Exception handling M-Pesa callback', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Internal server error'
            ]);
        }
    }

    /**
     * Public callback reachability test.
     * Safaricom callbacks are POST, but this GET endpoint lets you confirm
     * DNS, SSL, routing, and that the path is not blocked by middleware/CDN.
     */
    public function mpesaCallbackTest(Request $request)
    {
        return response()->json([
            'status' => 'callback endpoint reachable',
            'method' => $request->method(),
            'time' => now()->toDateTimeString(),
            'ip' => $request->ip(),
        ]);
    }
}
