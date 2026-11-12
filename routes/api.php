<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\HotspotApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Hotspot API endpoints for MikroTik local hotspot pages (per-router token auth)
Route::prefix('hotspot')->middleware(\App\Http\Middleware\AuthenticateHotspotRouter::class)->group(function () {
    Route::post('/packages', [HotspotApiController::class, 'packages']);
    Route::post('/activate-free', [HotspotApiController::class, 'activateFreePackage'])->middleware('throttle:5,1');
    Route::post('/stkpush', [HotspotApiController::class, 'stkPush'])->middleware('throttle:5,1');
    Route::post('/payment-status', [HotspotApiController::class, 'paymentStatus']);
    Route::post('/verify-voucher', [HotspotApiController::class, 'verifyVoucher'])->middleware('throttle:10,1');
    Route::post('/lookup-code', [HotspotApiController::class, 'lookupCode'])->middleware('throttle:10,1');
    Route::post('/logout-notify', [HotspotApiController::class, 'logoutNotify']);
});

// M-Pesa callback (must be public and CSRF-free, Safaricom IPs only)
Route::post('/mobile/m/callback', [HotspotApiController::class, 'mpesaCallback'])
    ->middleware(\App\Http\Middleware\VerifyMpesaCallbackSource::class)
    ->name('mpesa.callback');
Route::get('/mobile/m/callback', [HotspotApiController::class, 'mpesaCallbackTest']);
