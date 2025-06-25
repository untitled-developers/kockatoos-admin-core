<?php


use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AdminsController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AuthenticationController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\BlobsController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\RoutesController;

Route::post('api/login', [AuthenticationController::class, 'login'])->middleware('web')->name('auth.login');
Route::post('api/logout', [AuthenticationController::class, 'logout'])->middleware(['web', 'auth:sanctum']);
Route::get('api/me', [AuthenticationController::class, 'me'])->middleware(['web', 'auth:sanctum']);

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
    });
});
