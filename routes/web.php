<?php

use App\Http\Controllers\AddComment;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillingDownload;
use App\Http\Controllers\CaptivePortalController;
use App\Http\Controllers\ChangePackageController;
use App\Http\Controllers\CloseTicket;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisableDueUser;
use App\Http\Controllers\InvoiceDownload;
use App\Http\Controllers\OpenTicket;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentDownload;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShowUser;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\UserDisable;
use App\Http\Controllers\UserDownload;
use App\Http\Controllers\UserEnable;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// Public landing page -> Captive Portal
Route::get('/', [CaptivePortalController::class, 'index'])->name('portal.landing');

Route::middleware(['auth:staff', 'set-staff-guard'])->group(function () {
    // Admin Dashboard now at /dashboard (keeps name 'dashboard')
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/administration', function () {return view('administration');})->name('administration');

    // Package cloning (must be before resource to avoid /packages/{package} capturing 'clone')
    Route::get('/packages/clone', [PackageController::class, 'cloneForm'])->name('packages.clone.form');
    Route::post('/packages/clone', [PackageController::class, 'clone'])->name('packages.clone');
    Route::resource('/packages', PackageController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/billing', BillingController::class);
    Route::resource('/payment', PaymentController::class)->only(['index', 'store']);
    Route::resource('/ticket', TicketController::class);
    
    // Router routes - use explicit routes to avoid conflicts
    Route::get('/router', [RouterController::class, 'index'])->name('router.index');
    Route::get('/router/create', [RouterController::class, 'create'])->name('router.create');
    Route::post('/router', [RouterController::class, 'store'])->name('router.store');
    Route::get('/router/{router}/edit', [RouterController::class, 'edit'])->name('router.edit');
    Route::put('/router/{router}', [RouterController::class, 'update'])->name('router.update');
    Route::delete('/router/{router}', [RouterController::class, 'destroy'])->name('router.destroy');
    
    // Custom router routes - use specific patterns before show route
    Route::post('/router/test-connection', [RouterController::class, 'testConnection'])->name('router.test-connection');
    Route::get('/router/{router}/system-info', [RouterController::class, 'getSystemInfo'])->name('router.system-info')->where('router', '[0-9]+');
    Route::get('/router/{router}/interfaces', [RouterController::class, 'getInterfaces'])->name('router.interfaces')->where('router', '[0-9]+');
    Route::get('/router/status/all', [RouterController::class, 'getAllStatuses'])->name('router.status.all');
    Route::post('/router/{router}/provision-radius', [RouterController::class, 'provisionRadius'])->name('router.provision-radius')->where('router', '[0-9]+');
    Route::post('/router/{router}/configure-portal', [RouterController::class, 'configurePortal'])->name('router.configure-portal')->where('router', '[0-9]+');
    Route::post('/router/{router}/sync-hotspot-info', [RouterController::class, 'syncHotspotInfo'])->name('router.sync-hotspot-info')->where('router', '[0-9]+');
    Route::get('/router/{router}/hotspot-files', [RouterController::class, 'downloadHotspotFiles'])->name('router.hotspot-files')->where('router', '[0-9]+');
    Route::post('/router/{router}/apply-walled-garden', [RouterController::class, 'applyWalledGarden'])->name('router.apply-walled-garden')->where('router', '[0-9]+');
    Route::post('/router/{router}/reboot', [RouterController::class, 'reboot'])->name('router.reboot')->where('router', '[0-9]+');
    Route::post('/router/{router}/backup', [RouterController::class, 'backup'])->name('router.backup')->where('router', '[0-9]+');
    Route::get('/router/{router}/config', [RouterController::class, 'getConfig'])->name('router.config')->where('router', '[0-9]+');
    Route::get('/router/{router}/diagnostics', [RouterController::class, 'getDiagnostics'])->name('router.diagnostics')->where('router', '[0-9]+');
    
    // Show route must be last to avoid conflicts with specific routes
    Route::get('/router/{router}', [RouterController::class, 'show'])->name('router.show')->where('router', '[0-9]+');

    Route::get('/payment/create/{param}', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/change-package/{user}/edit', [ChangePackageController::class, 'edit'])->name('package-change');
    Route::patch('/change-package/{user}', [ChangePackageController::class, 'update'])->name('package-update');
    Route::patch('/user-disable/{user}', UserDisable::class)->name('user.disable');
    Route::patch('/user-enable/{user}', UserEnable::class)->name('user.enable');

    Route::post('/due-user-disable', DisableDueUser::class)->name('due.user.disable');
    Route::get('/log/{param}', \App\Http\Controllers\Log::class)->name('log');

    Route::post('/open-ticket/{ticket}', OpenTicket::class)->name('open.ticket');
    Route::post('/close-ticket/{ticket}', CloseTicket::class)->name('close.ticket');
    Route::post('/add-comment', AddComment::class)->name('add.comment');

    Route::get('/user-download', UserDownload::class)->name('user.download');
    Route::get('/billing-download', BillingDownload::class)->name('billing.download');
    Route::get('/payment-download', PaymentDownload::class)->name('payment.download');
    Route::get('/single-download/{user}', ShowUser::class)->name('single.download');
    Route::get('/invoice-download/{row}', InvoiceDownload::class)->name('invoice.download');

    // Voucher management (admin)
    Route::resource('/vouchers', VoucherController::class)->only(['index','create','store']);
    Route::get('/vouchers/export', [VoucherController::class, 'export'])->name('vouchers.export');

    // Session management (admin)
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/history', [SessionController::class, 'history'])->name('sessions.history');
    Route::get('/sessions/live-data', [SessionController::class, 'liveData'])->name('sessions.live-data');
    Route::get('/sessions/export', [SessionController::class, 'export'])->name('sessions.export');
    Route::post('/sessions/sync', [SessionController::class, 'syncWithRouter'])->name('sessions.sync');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/disconnect', [SessionController::class, 'disconnect'])->name('sessions.disconnect');

    // Reports and Analytics (admin)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/usage', [ReportController::class, 'usage'])->name('reports.usage');
    Route::get('/reports/packages', [ReportController::class, 'packages'])->name('reports.packages');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

}); // end staff admin routes

// User Self-Service Portal
Route::middleware('auth')->group(function () {
    Route::get('/my-dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/my-sessions', [UserDashboardController::class, 'sessions'])->name('user.sessions');
    Route::get('/my-purchases', [UserDashboardController::class, 'purchases'])->name('user.purchases');
    Route::get('/my-recharge', [UserDashboardController::class, 'recharge'])->name('user.recharge');
    Route::get('/my-settings', [UserDashboardController::class, 'settings'])->name('user.settings');
    Route::post('/my-settings', [UserDashboardController::class, 'updateSettings'])->name('user.settings.update');
    Route::get('/my-sessions/live-data', [UserDashboardController::class, 'activeSessionData'])->name('user.sessions.live-data');

    // Payment Routes for bKash
    Route::get('/bkash/payment', [App\Http\Controllers\BkashTokenizePaymentController::class,'index']);
    Route::get('/bkash/create-payment/{param}', [App\Http\Controllers\BkashTokenizePaymentController::class,'createPayment'])->name('bkash-create-payment');
    Route::get('/bkash/callback', [App\Http\Controllers\BkashTokenizePaymentController::class,'callBack'])->name('bkash-callBack');
    Route::get('/bkash/search/{trxID}', [App\Http\Controllers\BkashTokenizePaymentController::class,'searchTnx'])->name('bkash-serach');
    Route::get('/bkash/refund', [App\Http\Controllers\BkashTokenizePaymentController::class,'refund'])->name('bkash-refund');
    Route::get('/bkash/refund/status', [App\Http\Controllers\BkashTokenizePaymentController::class,'refundStatus'])->name('bkash-refund-status');
});

require __DIR__.'/auth.php';

// Staff authentication and management
Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login')->middleware('guest:staff');
Route::post('/staff/login', [StaffAuthController::class, 'store'])->name('staff.login.store')->middleware('guest:staff');

Route::get('/staff/forgot-password', [\App\Http\Controllers\Staff\ForgotPasswordController::class, 'create'])
    ->middleware('guest:staff')
    ->name('staff.password.request');
Route::post('/staff/forgot-password', [\App\Http\Controllers\Staff\ForgotPasswordController::class, 'store'])
    ->middleware('guest:staff')
    ->name('staff.password.email');
Route::get('/staff/reset-password/{token}', [\App\Http\Controllers\Staff\ResetPasswordController::class, 'create'])
    ->middleware('guest:staff')
    ->name('staff.password.reset');
Route::post('/staff/reset-password', [\App\Http\Controllers\Staff\ResetPasswordController::class, 'store'])
    ->middleware('guest:staff')
    ->name('staff.password.update');

Route::middleware(['auth:staff', 'set-staff-guard'])->prefix('staff')->name('staff.')->group(function () {
    Route::post('/logout', [StaffAuthController::class, 'destroy'])->name('logout');
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::get('/create', [StaffController::class, 'create'])->name('create');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
    Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
    Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
});

// Payment Gateway Callback (Public - No Authentication Required)
// Deliberately opaque URL to avoid advertising the payment provider
Route::post('/api/mobile/m/callback', [CaptivePortalController::class, 'mpesaCallback'])->name('mpesa.callback');

// Include Captive Portal Routes (Public - No Authentication Required)
require __DIR__.'/captive-portal.php';
