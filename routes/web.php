<?php

use App\Http\Controllers\AddComment;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillingDownload;
use App\Http\Controllers\CaptivePortalController;
use App\Http\Controllers\ChangePackageController;
use App\Http\Controllers\CloseTicket;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisableDueUser;
use App\Http\Controllers\InvoiceDownload;
use App\Http\Controllers\OpenTicket;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentDownload;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShowUser;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDisable;
use App\Http\Controllers\UserDownload;
use App\Http\Controllers\UserEnable;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/administration', function () {return view('administration');})->name('administration');

    Route::resource('/packages', PackageController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/billing', BillingController::class);
    Route::resource('/payment', PaymentController::class)->only(['index', 'store']);
    Route::resource('/ticket', TicketController::class);
    Route::resource('/router', RouterController::class);
    Route::post('/router/{router}/test-connection', [RouterController::class, 'testConnection'])->name('router.test-connection');
    Route::get('/router/{router}/system-info', [RouterController::class, 'getSystemInfo'])->name('router.system-info');
    Route::get('/router/{router}/interfaces', [RouterController::class, 'getInterfaces'])->name('router.interfaces');

    Route::get('/payment/create/{param}', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');

    Route::get('/isp', [CompanyController::class, 'edit'])->name('company.edit');
    Route::patch('/isp', [CompanyController::class, 'update'])->name('company.update');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    // Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

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

    Route::group(['middleware' => ['web']], function () {
        // Payment Routes for bKash
        Route::get('/bkash/payment', [App\Http\Controllers\BkashTokenizePaymentController::class,'index']);
        Route::get('/bkash/create-payment/{param}', [App\Http\Controllers\BkashTokenizePaymentController::class,'createPayment'])->name('bkash-create-payment');
        Route::get('/bkash/callback', [App\Http\Controllers\BkashTokenizePaymentController::class,'callBack'])->name('bkash-callBack');
    
        //search payment
        Route::get('/bkash/search/{trxID}', [App\Http\Controllers\BkashTokenizePaymentController::class,'searchTnx'])->name('bkash-serach');
    
        //refund payment routes
        Route::get('/bkash/refund', [App\Http\Controllers\BkashTokenizePaymentController::class,'refund'])->name('bkash-refund');
        Route::get('/bkash/refund/status', [App\Http\Controllers\BkashTokenizePaymentController::class,'refundStatus'])->name('bkash-refund-status');
    
    });
});

require __DIR__.'/auth.php';

// Captive Portal Routes (Public - No Authentication Required)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [CaptivePortalController::class, 'index'])->name('index');
    Route::get('/package/{package}', [CaptivePortalController::class, 'package'])->name('package');
    Route::post('/package/{package}/purchase', [CaptivePortalController::class, 'purchase'])->name('purchase');
    
    // API Routes for AJAX requests
    Route::get('/api/packages/{router?}', [CaptivePortalController::class, 'apiPackages'])->name('api.packages');
});
