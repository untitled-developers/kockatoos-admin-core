<?php


use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AuthenticationController;

Route::post('/login', [AuthenticationController::class, 'login'])->middleware('web');
Route::post('/logout', [AuthenticationController::class, 'logout'])->middleware(['web', 'auth:sanctum']);
