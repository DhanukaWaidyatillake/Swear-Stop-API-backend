<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
});

//Generate new token for user during signup
Route::post('/generate-token', [\App\Http\Controllers\TokenManagerController::class, 'generateToken']);

Route::middleware([\App\Http\Middleware\TokenVerificationMiddleware::class])->group(function (){

    Route::post('/refresh-token', [\App\Http\Controllers\TokenManagerController::class, 'refreshToken']);

    Route::prefix('v1')->group(function (){
        Route::post('/text-filter',[\App\Http\Controllers\TextFiltrationController::class, 'textFilter']);
    });

});

