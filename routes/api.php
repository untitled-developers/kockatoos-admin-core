<?php


use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AdminsController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AuthenticationController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\BlobsController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\MfaController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\RoutesController;

Route::post('api/login', [AuthenticationController::class, 'login'])->name('auth.login');
Route::post('api/logout', [AuthenticationController::class, 'logout'])->middleware(['web', 'auth:sanctum']);
Route::get('api/me', [AuthenticationController::class, 'me'])->middleware(['web', 'auth:sanctum']);

// Public MFA routes
Route::post('api/mfa/hasMfa', [MfaController::class, 'hasMfa'])->name('mfa.hasMfa');

Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::prefix('api')->group(function () {
        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/formData', [AdminsController::class, 'getFormData']);
            RoutesController::createResourcesRoutes(AdminsController::class);
            Route::put('/{id}/toggleLocked', [AdminsController::class, 'toggleLocked']);
        });

        Route::prefix('blobs')->name('blobs.')->group(function () {
            RoutesController::createResourcesRoutes(BlobsController::class);
        });

        Route::prefix('mfa')->name('mfa.')->group(function () {
            Route::post('/toggle', [MfaController::class, 'toggle']);
            Route::get('/qrcode', [MfaController::class, 'getQrCode']);
        });
    });
});
