<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use App\Models\HotspotSession;
use App\Models\PaymentTransaction;
use App\Models\Voucher;
use App\Models\User;
use App\Models\SmsVerification;
use App\Services\DeviceIdentificationService;
use App\Services\HotspotSessionService;
use App\Services\MikroTikService;
use App\Services\MpesaService;
use App\Services\VintexSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CaptivePortalController extends Controller
{
    protected HotspotSessionService $sessionService;
    protected DeviceIdentificationService $deviceService;
    protected MikroTikService $mikrotikService;
    protected MpesaService $mpesaService;
    protected VintexSmsService $smsService;

    public function __construct(HotspotSessionService $sessionService, DeviceIdentificationService $deviceService, MikroTikService $mikrotikService, MpesaService $mpesaService, VintexSmsService $smsService)
    {
        $this->sessionService = $sessionService;
        $this->deviceService = $deviceService;
        $this->mikrotikService = $mikrotikService;
        $this->mpesaService = $mpesaService;
        $this->smsService = $smsService;
    }

    /**
     * Show forgot password page
     */
    public function showForgotPassword(Request $request)
    {
        return view('captive-portal.forgot-password');
    }

    /**
     * Send OTP for password reset
     */
    public function sendPasswordResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid phone number format.']);
        }

        try {
            $normalizedPhone = $this->smsService->normalizePhoneNumber($request->phone);
            if (!$normalizedPhone) {
                return response()->json(['success' => false, 'message' => 'Invalid phone number format.']);
            }

            // Create OTP record (reuse signup table)
            $otpRecord = SmsVerification::createForPhone(
                $normalizedPhone,
                $request->ip(),
                $request->userAgent()
            );

            $smsResult = $this->smsService->sendOtp($normalizedPhone, $otpRecord->otp);
            if ($smsResult['success']) {
                return response()->json(['success' => true, 'message' => 'Password reset code sent to your phone.']);
            }
            return response()->json(['success' => false, 'message' => 'Failed to send reset code. Please try again.']);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send reset code. Please try again.']);
        }
    }

    /**
     * Process password reset
     */
    public function processPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $normalizedPhone = $this->smsService->normalizePhoneNumber($request->phone);
            if (!$normalizedPhone) {
                return back()->with('error', 'Invalid phone number format.')->withInput();
            }

            // Find user by phone
            $user = User::where('phone', $normalizedPhone)->first();
            if (!$user) {
                return back()->with('error', 'No account found with that phone number.')->withInput();
            }

            // Verify OTP
            $otpRecord = SmsVerification::findValidOtp($normalizedPhone, $request->otp);
            if (!$otpRecord) {
                return back()->with('error', 'Invalid or expired verification code.')->withInput();
            }

            $otpRecord->markAsVerified();

            // Update password
            $user->update(['password' => bcrypt($request->password)]);

            // Notify user via SMS
            $this->smsService->sendSms($normalizedPhone, 'Your hotspot password has been reset successfully. If this was not you, contact support immediately.');

            return redirect()->route('portal.login')->with('success', 'Password reset successful. Please log in.');
        } catch (\Exception $e) {
            Log::error('Password reset failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Password reset failed. Please try again.');
        }
    }

    /**
     * Display the captive portal page with packages
     */
    public function index(Request $request)
    {
        // Check if device already has an active session
        $activeSession = $this->sessionService->getActiveSession($request);
        
        if ($activeSession) {
            // Device already has active session, redirect to status page or allow internet
            return $this->showSessionStatus($activeSession);
        }

        // Try to detect router by various methods
        $router = $this->detectRouter($request);
        
        if (!$router) {
            // Fallback: show all packages if router can't be detected
            $packages = Package::with('router')->orderBy('price')->get();
            $routerName = 'Available Packages';
        } else {
            // Show packages for the detected router
            $packages = Package::where('router_id', $router->id)->orderBy('price')->get();
            $routerName = $router->name;
        }

        // Filter out free packages if user has already used them
        $packages = $this->filterFreePackagesForDevice($request, $packages);

        // Check if user has already used free package (for signup button visibility)
        $hasUsedFreePackage = $this->hasUsedFreePackage($request);

        return view('captive-portal.index', compact('packages', 'routerName', 'router', 'hasUsedFreePackage'));
    }

    /**
     * Show package details
     */
    public function package(Request $request, $packageId)
    {
        $package = Package::with('router')->findOrFail($packageId);
        $router = $this->detectRouter($request);
        
        return view('captive-portal.package', compact('package', 'router'));
    }

    /**
     * Handle package purchase/selection
     */
    public function purchase(Request $request, $packageId)
    {
        // Check if device already has an active session
        $activeSession = $this->sessionService->getActiveSession($request);
        
        if ($activeSession) {
            return $this->showSessionStatus($activeSession);
        }

        $package = Package::with('router')->findOrFail($packageId);
        $router = $this->detectRouter($request);
        
        // Prefill phone from session or authenticated user if available
        $defaultPhone = session('customer_phone') ?? (auth()->check() ? (auth()->user()->phone ?? null) : null);

        return view('captive-portal.purchase', compact('package', 'router', 'defaultPhone'));
    }

    /**
     * Process payment for a package
     */
    public function processPayment(Request $request, $packageId)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
            'name' => 'required|string|max:255',
            'mode' => 'nullable|in:activate,voucher'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $package = Package::findOrFail($packageId);
        
        // Check if device already has an active session
        $activeSession = $this->sessionService->getActiveSession($request);
        if ($activeSession) {
            return redirect()->route('portal.status')->with('info', 'You already have an active session.');
        }

        try {
            // Initiate M-Pesa STK Push
            // PayBill AccountReference must be <= 12 alphanumeric chars
            $rawRef = 'HSP-' . $package->id;
            $accountReference = substr(preg_replace('/[^A-Z0-9]/i', '', strtoupper($rawRef)), 0, 12);
            $transactionDesc = $package->name . ' - Internet Package';
            
            $stkResult = $this->mpesaService->stkPush(
                $request->phone,
                $package->price,
                $accountReference,
                $transactionDesc
            );

            if (!$stkResult['success']) {
                return back()->with('error', $stkResult['message']);
            }

            // Update the payment transaction with package and session info
            $transaction = PaymentTransaction::where('checkout_request_id', $stkResult['checkout_request_id'])->first();
            if ($transaction) {
                $transaction->update([
                    'package_id' => $package->id,
                    'account_reference' => $accountReference
                ]);
            }

            // Store transaction ID in session for status checking
            session([
                'payment_transaction_id' => $transaction->id ?? null,
                'checkout_request_id' => $stkResult['checkout_request_id'],
                'package_id' => $package->id,
                'customer_phone' => $request->phone,
                'customer_name' => $request->name,
                'purchase_mode' => $request->input('mode', 'activate')
            ]);

            Log::info('M-Pesa STK Push initiated', [
                'checkout_request_id' => $stkResult['checkout_request_id'],
                'package' => $package->name,
                'phone' => $request->phone,
                'amount' => $package->price,
                'device_info' => $this->deviceService->getDeviceInfo($request)
            ]);

            // Redirect to payment status page
            return redirect()->route('portal.payment-status')->with('success', $stkResult['message']);
            
        } catch (\Exception $e) {
            Log::error('Failed to process M-Pesa payment', [
                'error' => $e->getMessage(),
                'package_id' => $packageId,
                'request_data' => $request->except('phone') // Don't log sensitive phone data in error
            ]);
            
            return back()->with('error', 'Payment processing failed. Please try again.');
        }
    }

    /**
     * Show payment status page
     */
    public function showPaymentStatus(Request $request)
    {
        $checkoutRequestId = session('checkout_request_id');
        $packageId = session('package_id');
        
        if (!$checkoutRequestId || !$packageId) {
            return redirect()->route('portal.index')->with('error', 'No payment session found.');
        }

        $package = Package::findOrFail($packageId);
        $transaction = PaymentTransaction::where('checkout_request_id', $checkoutRequestId)->first();
        $router = $this->detectRouter($request);

        return view('captive-portal.payment-status', compact('package', 'transaction', 'router', 'checkoutRequestId'));
    }

    /**
     * Check payment status via AJAX
     */
    public function checkPaymentStatus(Request $request)
    {
        $checkoutRequestId = $request->input('checkout_request_id') ?? session('checkout_request_id');
        
        if (!$checkoutRequestId) {
            return response()->json([
                'success' => false,
                'message' => 'No payment session found'
            ]);
        }

        $transaction = PaymentTransaction::where('checkout_request_id', $checkoutRequestId)->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ]);
        }

        // If transaction is still pending and not expired, query M-Pesa
        if ($transaction->isPending() && !$transaction->isExpired()) {
            $queryResult = $this->mpesaService->queryTransaction($checkoutRequestId);
            
            if ($queryResult['success']) {
                $responseData = $queryResult['data'];
                $resultCode = $responseData['ResultCode'] ?? null;
                
                if ($resultCode == 0) {
                    // Payment successful - update transaction
                    $transaction->markAsCompleted([
                        'result_code' => $resultCode,
                        'result_description' => $responseData['ResultDesc'] ?? 'Payment successful'
                    ]);
                } elseif ($resultCode && $resultCode != 1032) { // 1032 = Request cancelled by user (still pending)
                    // Payment failed
                    $transaction->markAsFailed(
                        $responseData['ResultDesc'] ?? 'Payment failed',
                        $resultCode
                    );
                }
            }
        }

        // Check if payment is completed and create session or generate voucher
        if ($transaction->isCompleted()) {
            try {
                $mode = session('purchase_mode', 'activate');
                if ($mode === 'voucher') {
                    // Generate a voucher and send via SMS
                    $expiresAt = now()->addDays(30);
                    $pkg = $transaction->package; // lazy-load ok
                    $created = Voucher::createBatch($pkg->id, 1, $expiresAt);
                    $voucher = $created[0];

                    // Send voucher to customer's phone via SMS
                    $phone = session('customer_phone');
                    $this->smsService->sendVoucherSms(
                        $phone,
                        $voucher->code,
                        $pkg->name,
                        $voucher->expires_at?->format('j M Y')
                    );

                    // Clear payment session data
                    session()->forget(['payment_transaction_id', 'checkout_request_id', 'package_id', 'customer_phone', 'customer_name', 'purchase_mode']);

                    Log::info('Voucher generated and sent via SMS after payment', [
                        'voucher_code' => $voucher->code,
                        'transaction_id' => $transaction->id,
                    ]);

                    return response()->json([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Payment successful! Your voucher has been sent via SMS.',
                        'redirect_url' => route('portal.index')
                    ]);
                } else {
                    // Create hotspot session (activate now)
                    $session = $this->sessionService->createSessionForPackage(
                        $request,
                        $transaction->package,
                        null, // user (for guest purchases)
                        session('customer_phone') // username/identifier
                    );

                    // Update transaction with session ID
                    $transaction->update(['session_id' => $session->session_id]);

                    // Clear payment session data
                    session()->forget(['payment_transaction_id', 'checkout_request_id', 'package_id', 'customer_phone', 'customer_name', 'purchase_mode']);

                    Log::info('Hotspot session created after successful payment', [
                        'session_id' => $session->session_id,
                        'transaction_id' => $transaction->id,
                        'mpesa_receipt' => $transaction->mpesa_receipt_number
                    ]);

                    return response()->json([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Payment successful! You are now connected to the internet.',
                        'redirect_url' => route('portal.status')
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Failed to create session after payment', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Payment successful but failed to activate internet. Please contact support.'
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status,
            'message' => $this->getStatusMessage($transaction),
            'is_expired' => $transaction->isExpired()
        ]);
    }

    /**
     * Get user-friendly status message
     */
    private function getStatusMessage(PaymentTransaction $transaction): string
    {
        if ($transaction->isCompleted()) {
            return 'Payment completed successfully!';
        } elseif ($transaction->isFailed()) {
            return $transaction->result_description ?? 'Payment failed. Please try again.';
        } elseif ($transaction->isExpired()) {
            return 'Payment request expired. Please try again.';
        } else {
            return 'Waiting for payment confirmation. Please complete the payment on your phone.';
        }
    }

    /**
     * Show login page
     */
    public function showLogin(Request $request)
    {
        return view('captive-portal.login');
    }

    /**
     * Show signup page
     */
    public function showSignup(Request $request)
    {
        return view('captive-portal.signup');
    }

    /**
     * Authenticate user with voucher or credentials
     */
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $session = $this->sessionService->authenticateUser(
                $request,
                $request->username,
                $request->password
            );

            if (!$session) {
                return back()->with('error', 'Invalid voucher code or credentials. Please check and try again.');
            }

            Log::info('User authenticated successfully', [
                'session_id' => $session->session_id,
                'username' => $request->username,
                'device_info' => $this->deviceService->getDeviceInfo($request)
            ]);

            return redirect()->route('portal.status')->with('success', 'Login successful! You are now connected to the internet.');
            
        } catch (\Exception $e) {
            Log::error('Authentication failed', [
                'error' => $e->getMessage(),
                'username' => $request->username,
            ]);
            
            return back()->with('error', 'Authentication failed. Please try again.');
        }
    }

    /**
     * Show session status page
     */
    public function showStatus(Request $request)
    {
        $activeSession = $this->sessionService->getActiveSession($request);
        
        if (!$activeSession) {
            return redirect()->route('portal.index')->with('info', 'No active session found. Please select a package or login.');
        }

        $sessionStatus = $this->sessionService->getSessionStatus($activeSession);
        $router = $this->detectRouter($request);

        return view('captive-portal.status', compact('activeSession', 'sessionStatus', 'router'));
    }

    /**
     * Show session status (internal method)
     */
    protected function showSessionStatus(HotspotSession $session)
    {
        if ($session->isExpired()) {
            // Session expired, redirect to packages
            return redirect()->route('portal.index')->with('info', 'Your session has expired. Please select a new package.');
        }

        // Redirect to status page
        return redirect()->route('portal.status');
    }

    /**
     * Disconnect/logout user
     */
    public function disconnect(Request $request)
    {
        $activeSession = $this->sessionService->getActiveSession($request);
        
        if ($activeSession) {
            $this->sessionService->terminateSession($activeSession);
            
            Log::info('User disconnected', [
                'session_id' => $activeSession->session_id,
                'device_info' => $this->deviceService->getDeviceInfo($request)
            ]);
        }

        return redirect()->route('portal.index')->with('success', 'You have been disconnected successfully.');
    }

    /**
     * Handle user signup for free 500MB package
     */
    public function processSignup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Normalize phone number
            $normalizedPhone = $this->smsService->normalizePhoneNumber($request->phone);
            if (!$normalizedPhone) {
                return back()->with('error', 'Invalid phone number format.')->withInput();
            }

            // Check if phone has already been used for free package
            if ($this->hasPhoneUsedFreePackage($normalizedPhone)) {
                return back()->with('error', 'This phone number has already been used for a free package.')->withInput();
            }

            // Verify OTP
            $otpRecord = SmsVerification::findValidOtp($normalizedPhone, $request->otp);
            if (!$otpRecord) {
                return back()->with('error', 'Invalid or expired verification code.')->withInput();
            }

            // Check if device already has an active session
            $activeSession = $this->sessionService->getActiveSession($request);
            if ($activeSession) {
                return redirect()->route('portal.status')->with('info', 'You already have an active session.');
            }

            // Mark OTP as verified
            $otpRecord->markAsVerified();

            // Create user account
            $user = User::create([
                'name' => $request->name,
                'email' => $normalizedPhone . '@hotspot.local', // Generate email from phone
                'phone' => $normalizedPhone,
                'password' => bcrypt($request->password),
                'email_verified_at' => now(), // Auto-verify for hotspot users
            ]);

            // Find or create free 500MB package
            $freePackage = $this->getOrCreateFreePackage();

            // Create session for the free package
            $session = $this->sessionService->createSessionForPackage(
                $request, 
                $freePackage, 
                $user,
                $normalizedPhone
            );

            // Send welcome SMS
            $this->smsService->sendWelcomeSms($normalizedPhone, $request->name);

            Log::info('New user signup with free package', [
                'user_id' => $user->id,
                'session_id' => $session->session_id,
                'phone' => $normalizedPhone,
                'device_info' => $this->deviceService->getDeviceInfo($request)
            ]);

            return redirect()->route('portal.status')->with('success', 'Account created successfully! You now have 500MB of free internet access.');
            
        } catch (\Exception $e) {
            Log::error('Signup failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password', 'otp'])
            ]);
            
            return back()->with('error', 'Signup failed. Please try again.');
        }
    }

    /**
     * Send OTP for signup verification
     */
    public function sendSignupOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format.'
            ]);
        }

        try {
            // Normalize phone number
            $normalizedPhone = $this->smsService->normalizePhoneNumber($request->phone);
            if (!$normalizedPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format.'
                ]);
            }

            // Check if phone has already been used for free package
            if ($this->hasPhoneUsedFreePackage($normalizedPhone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number has already been used for a free package.'
                ]);
            }

            // Create OTP record
            $otpRecord = SmsVerification::createForPhone(
                $normalizedPhone,
                $request->ip(),
                $request->userAgent()
            );

            // Send OTP SMS
            $smsResult = $this->smsService->sendOtp($normalizedPhone, $otpRecord->otp);

            if ($smsResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Verification code sent to your phone.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send verification code. Please try again.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send signup OTP', [
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.'
            ]);
        }
    }

    /**
     * Get or create the free 500MB package
     */
    protected function getOrCreateFreePackage(): Package
    {
        // Try to find existing free package
        $freePackage = Package::where('name', 'Free 500MB')
                             ->where('price', 0)
                             ->first();

        if (!$freePackage) {
            // Get the first available router, or create without router_id if none exists
            $firstRouter = Router::first();
            
            if (!$firstRouter) {
                // If no routers exist, we need to handle this case
                Log::warning('No routers found when creating free package');
                throw new \Exception('No routers available. Please create a router first.');
            }
            
            // Create free package if it doesn't exist
            $freePackage = Package::create([
                'name' => 'Free 500MB',
                'price' => 0.00,
                'rate_limit' => '500MB',
                'bandwidth_download' => 10, // 10 Mbps
                'bandwidth_upload' => 5,    // 5 Mbps
                'validity_days' => 1,       // 24 hours
                'session_timeout' => 24,    // 24 hours
                'shared_users' => 1,        // Single device
                'is_active' => true,
                'description' => 'Free starter package for new users',
                'router_id' => $firstRouter->id, // Assign to first router
            ]);
        }

        return $freePackage;
    }

    /**
     * Filter out free packages if the device has already used them
     */
    protected function filterFreePackagesForDevice(Request $request, $packages)
    {
        $hasUsedFreePackage = $this->hasUsedFreePackage($request);
        
        if ($hasUsedFreePackage) {
            // Filter out free packages (price = 0)
            $packages = $packages->filter(function ($package) {
                return $package->price > 0;
            });
        }
        
        return $packages;
    }

    /**
     * Check if user/device has already used a free package (comprehensive approach)
     */
    protected function hasUsedFreePackage(Request $request): bool
    {
        // Method 1: Check by IP address for recent usage (strongest protection against MAC spoofing)
        $clientIp = $request->ip();
        if ($this->hasRecentFreeUsageFromIP($clientIp)) {
            return true;
        }

        // Method 2: Check by device fingerprint and MAC address
        $deviceFingerprint = $this->deviceService->generateDeviceFingerprint($request);
        $macAddress = $this->deviceService->getMacAddress($request);
        
        if ($this->hasDeviceUsedFreePackage($deviceFingerprint, $macAddress)) {
            return true;
        }

        // Method 3: Check for suspicious behavior patterns
        if ($this->detectSuspiciousBehavior($request)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a device has already used a free package (legacy method)
     */
    protected function hasDeviceUsedFreePackage(?string $deviceFingerprint, ?string $macAddress): bool
    {
        // If neither fingerprint nor MAC is available, we can't identify the device
        if (!$deviceFingerprint && !$macAddress) {
            return false;
        }

        // Build query to check for sessions with free packages
        $query = HotspotSession::whereHas('package', function ($q) {
            $q->where('price', 0);
        });

        // Add device identification conditions
        $query->where(function ($q) use ($deviceFingerprint, $macAddress) {
            if ($deviceFingerprint) {
                $q->where('device_fingerprint', $deviceFingerprint);
            }
            
            if ($macAddress) {
                $q->orWhere('mac_address', $macAddress);
            }
        });

        return $query->exists();
    }

    /**
     * Check if phone number has already been used for free package
     */
    protected function hasPhoneUsedFreePackage(string $phone): bool
    {
        $normalizedPhone = $this->smsService->normalizePhoneNumber($phone);
        
        if (!$normalizedPhone) {
            return false;
        }

        // Check if user exists with this phone
        if (User::where('phone', $normalizedPhone)->exists()) {
            return true;
        }

        // Check if any session was created with this phone as username
        return HotspotSession::where('username', $normalizedPhone)
                           ->whereHas('package', function ($q) {
                               $q->where('price', 0);
                           })
                           ->exists();
    }

    /**
     * Check for recent free package usage from same IP/subnet
     */
    protected function hasRecentFreeUsageFromIP(string $ip): bool
    {
        // Get subnet (first 3 octets)
        $subnet = substr($ip, 0, strrpos($ip, '.'));
        
        // Check for recent free package usage from this subnet
        $recentUsage = HotspotSession::where('ip_address', 'LIKE', $subnet . '%')
                                   ->whereHas('package', function ($q) {
                                       $q->where('price', 0);
                                   })
                                   ->where('created_at', '>', now()->subDays(7))
                                   ->count();

        // Allow max 2 free packages per subnet per week
        return $recentUsage >= 2;
    }

    /**
     * Detect suspicious behavior patterns
     */
    protected function detectSuspiciousBehavior(Request $request): bool
    {
        $clientIp = $request->ip();
        
        // Check for rapid MAC changes from same IP (indicates MAC spoofing)
        $recentSessions = HotspotSession::where('ip_address', $clientIp)
                                       ->where('created_at', '>', now()->subHours(2))
                                       ->distinct('mac_address')
                                       ->count('mac_address');
        
        if ($recentSessions > 3) {
            Log::warning('Suspicious behavior detected: Multiple MAC addresses from same IP', [
                'ip' => $clientIp,
                'mac_count' => $recentSessions,
                'user_agent' => $request->userAgent()
            ]);
            return true;
        }

        return false;
    }

    /**
     * Debug endpoint to show device information
     */
    public function debugDevice(Request $request)
    {
        if (!config('app.debug')) {
            abort(404);
        }

        $deviceInfo = $this->sessionService->getDeviceDebugInfo($request);
        $activeSession = $this->sessionService->getActiveSession($request);
        
        return response()->json([
            'device_info' => $deviceInfo,
            'active_session' => $activeSession,
            'router_detection' => $this->detectRouter($request),
        ]);
    }

    /**
     * Detect which router the user is connected through
     */
    private function detectRouter(Request $request)
    {
        // Method 1: Check if router_id is passed as parameter
        if ($request->has('router_id')) {
            $router = Router::find($request->router_id);
            if ($router) {
                return $router;
            }
        }

        // Method 2: Check if router IP is passed as parameter (common in MikroTik)
        if ($request->has('router_ip')) {
            $router = Router::where('ip', $request->router_ip)->first();
            if ($router) {
                return $router;
            }
        }

        // Method 3: Try to detect by HTTP_HOST or SERVER_NAME
        $serverName = $request->server('HTTP_HOST') ?? $request->server('SERVER_NAME');
        if ($serverName) {
            $router = Router::where('ip', $serverName)->first();
            if ($router) {
                return $router;
            }
        }

        // Method 4: Check X-Forwarded-For or other headers that might contain router IP
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                $router = Router::where('ip', $ip)->first();
                if ($router) {
                    return $router;
                }
            }
        }

        // Method 5: Check client IP ranges (if routers use specific IP ranges)
        $clientIp = $request->ip();
        if ($clientIp) {
            // Try to match router by IP subnet (basic implementation)
            $routers = Router::all();
            foreach ($routers as $router) {
                // Simple check if client IP starts with router IP subnet
                $routerSubnet = substr($router->ip, 0, strrpos($router->ip, '.'));
                $clientSubnet = substr($clientIp, 0, strrpos($clientIp, '.'));
                
                if ($routerSubnet === $clientSubnet) {
                    return $router;
                }
            }
        }

        // Log the detection attempt for debugging
        Log::info('Router detection failed', [
            'router_id' => $request->get('router_id'),
            'router_ip' => $request->get('router_ip'),
            'http_host' => $request->server('HTTP_HOST'),
            'server_name' => $request->server('SERVER_NAME'),
            'client_ip' => $request->ip(),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
        ]);

        return null;
    }

    /**
     * API endpoint to get packages for a specific router
     */
    public function apiPackages(Request $request, $routerId = null)
    {
        if ($routerId) {
            $packages = Package::where('router_id', $routerId)->orderBy('price')->get();
        } else {
            $router = $this->detectRouter($request);
            if ($router) {
                $packages = Package::where('router_id', $router->id)->orderBy('price')->get();
            } else {
                $packages = Package::with('router')->orderBy('price')->get();
            }
        }

        // Filter out free packages if user has already used them
        $packages = $this->filterFreePackagesForDevice($request, $packages);

        return response()->json([
            'success' => true,
            'packages' => $packages,
            'router_detected' => $router ?? null
        ]);
    }


    /**
     * Handle M-Pesa callback
     */
    public function mpesaCallback(Request $request)
    {
        try {
            $callbackData = $request->all();
            
            Log::info('M-Pesa callback received', $callbackData);
            
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
}
