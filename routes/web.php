<?php

use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\AdminUiDocsController;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\SkyForgeController;

Route::middleware(['web'])->group(function() {
    if (app()->environment('local', 'development')) {
        Route::prefix('skyforge')->group(function () {
            Route::get('/', [SkyForgeController::class, 'index'])
                ->name('skyforge.index');
            Route::get('/{table}', [SkyForgeController::class, 'table'])
                ->name('skyforge.table-details');
        });

        Route::prefix('admin-ui')->group(function () {
            Route::get('/', [AdminUiDocsController::class, 'index']);
            Route::get('/assets/{path}', [AdminUiDocsController::class, 'assets'])->where('path', '.*');
            Route::get('/{any}', [AdminUiDocsController::class, 'catchAll'])->where('any', '.*');
        });
    }
});





