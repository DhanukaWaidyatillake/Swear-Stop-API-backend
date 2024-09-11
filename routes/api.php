<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
});

//Generate new token for user during signup
Route::post('/generate-token', [\App\Http\Controllers\TokenManagerController::class, 'generateToken']);

//Filter texts incoming though API tester in welcome page
Route::post('/text-filter-tester', [\App\Http\Controllers\TextFiltrationController::class, 'textFilterTester']);


Route::middleware([\App\Http\Middleware\TokenVerificationMiddleware::class])->group(function () {

    //refresh API key
    Route::post('/refresh-token', [\App\Http\Controllers\TokenManagerController::class, 'refreshToken']);

    //Main text filtration endpoint
    Route::prefix('v1')->group(function () {
        Route::middleware([\App\Http\Middleware\RequestAuditMiddleware::class])->post('/text-filter', [\App\Http\Controllers\TextFiltrationController::class, 'textFilter']);
    });
});

