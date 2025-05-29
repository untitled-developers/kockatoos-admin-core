<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;


use Illuminate\Support\Facades\Route;

class RoutesController
{
    public static function createResourcesRoutes($class){
        Route::get('', [$class, 'index']);
        Route::post('/{id}', [$class, 'update']);
        Route::delete('/{id}', [$class, 'destroy']);
        Route::post('', [$class, 'store']);
    }
}
