<?php

use App\Http\Controllers\Api\V1\AuthorizationController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\RouterController;
use App\Http\Middleware\AuthenticateTenantCredential;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::middleware([AuthenticateTenantCredential::class, 'throttle:api'])->group(function () {
        Route::post('/routers', [RouterController::class, 'store']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::post('/packages', [PackageController::class, 'store']);
        Route::post('/authorizations', [AuthorizationController::class, 'store']);
        Route::patch('/authorizations/{external_id}', [AuthorizationController::class, 'update']);
    });
});
