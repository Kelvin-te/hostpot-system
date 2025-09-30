<?php

use App\Http\Controllers\CaptivePortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Captive Portal Routes
|--------------------------------------------------------------------------
|
| These routes handle the captive portal functionality for hotspot users.
| They are designed to work with MikroTik routers and handle device
| identification, session management, and package purchases.
|
*/

// Main captive portal routes
Route::prefix('portal')->name('portal.')->group(function () {
    
    // Main portal page - shows packages
    Route::get('/', [CaptivePortalController::class, 'index'])->name('index');
    
    // Package details
    Route::get('/package/{package}', [CaptivePortalController::class, 'package'])->name('package');
    
    // Purchase flow
    Route::get('/package/{package}/purchase', [CaptivePortalController::class, 'purchase'])->name('purchase');
    Route::post('/package/{package}/process-payment', [CaptivePortalController::class, 'processPayment'])->name('process-payment');
    
    // Payment status
    Route::get('/payment-status', [CaptivePortalController::class, 'showPaymentStatus'])->name('payment-status');
    Route::post('/check-payment-status', [CaptivePortalController::class, 'checkPaymentStatus'])->name('check-payment-status');
    
    // Login for existing users/vouchers
    Route::get('/login', [CaptivePortalController::class, 'showLogin'])->name('login');
    Route::post('/authenticate', [CaptivePortalController::class, 'authenticate'])->name('authenticate');
    // Forgot password (OTP reset)
    Route::get('/forgot-password', [CaptivePortalController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password/send-otp', [CaptivePortalController::class, 'sendPasswordResetOtp'])->name('forgot-password.send-otp');
    Route::post('/forgot-password/reset', [CaptivePortalController::class, 'processPasswordReset'])->name('forgot-password.reset');
    
    // Signup for free 500MB package
    Route::get('/signup', [CaptivePortalController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [CaptivePortalController::class, 'processSignup'])->name('process-signup');
    
    // Session status and management
    Route::get('/status', [CaptivePortalController::class, 'showStatus'])->name('status');
    Route::post('/disconnect', [CaptivePortalController::class, 'disconnect'])->name('disconnect');
    
    // API endpoints for packages (for AJAX/mobile apps)
    Route::get('/api/packages/{router?}', [CaptivePortalController::class, 'apiPackages'])->name('api.packages');
    
    // Debug endpoint (only in debug mode)
    Route::get('/debug/device', [CaptivePortalController::class, 'debugDevice'])->name('debug.device');
    Route::get('/debug/mikrotik-test', [CaptivePortalController::class, 'testMikroTikIntegration'])->name('debug.mikrotik-test');
});

// Legacy routes for MikroTik compatibility (if needed)
Route::group(['prefix' => 'hotspot'], function () {
    Route::get('/', [CaptivePortalController::class, 'index']);
    Route::get('/login', [CaptivePortalController::class, 'showLogin']);
    Route::post('/login', [CaptivePortalController::class, 'authenticate']);
});
