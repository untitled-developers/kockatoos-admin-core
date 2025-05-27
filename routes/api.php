<?php


use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AuthenticationController;

Route::post('/login', [AuthenticationController::class, 'login'])->middleware('web');
Route::post('/logout', [AuthenticationController::class, 'logout'])->middleware(['web', 'auth:sanctum']);

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthenticationController::class, 'login'])->middleware('web')->name('auth.login');
    Route::post('/logout', [AuthenticationController::class, 'logout'])->middleware(['web', 'auth:sanctum']);
    Route::get('/me', [AuthenticationController::class, 'me'])->middleware(['web', 'auth:sanctum']);

});
