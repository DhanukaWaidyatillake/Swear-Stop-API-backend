<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
});

//Generate new token for user during signup
Route::post('/generate-token', [\App\Http\Controllers\TokenManagerController::class, 'generateToken']);

Route::post('/refresh-token', [\App\Http\Controllers\TokenManagerController::class, 'refreshToken'])
    ->middleware([\App\Http\Middleware\TokenVerificationMiddleware::class]);



