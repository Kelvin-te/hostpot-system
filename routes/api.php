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

// Hotspot API endpoints for MikroTik local hotspot pages
Route::prefix('hotspot')->group(function () {
    Route::post('/packages', [HotspotApiController::class, 'packages']);
    Route::post('/activate-free', [HotspotApiController::class, 'activateFreePackage']);
    Route::post('/stkpush', [HotspotApiController::class, 'stkPush']);
    Route::post('/payment-status', [HotspotApiController::class, 'paymentStatus']);
    Route::post('/verify-voucher', [HotspotApiController::class, 'verifyVoucher']);
    Route::post('/lookup-code', [HotspotApiController::class, 'lookupCode']);
    Route::post('/logout-notify', [HotspotApiController::class, 'logoutNotify']);
});

// M-Pesa callback (must be public and CSRF-free)
Route::post('/mobile/m/callback', [HotspotApiController::class, 'mpesaCallback'])->name('mpesa.callback');
Route::get('/mobile/m/callback', [HotspotApiController::class, 'mpesaCallbackTest']);
