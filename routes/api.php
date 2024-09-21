<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
});

//Generate new token for user during signup
Route::middleware('throttle:generate-or-refresh-token')
    ->post('/generate-token', [\App\Http\Controllers\TokenManagerController::class, 'generateToken']);

//Filter texts incoming though API tester in welcome page
Route::middleware('throttle:text-filter-tester')
    ->post('/text-filter-tester', [\App\Http\Controllers\TextFiltrationController::class, 'textFilterTester']);


Route::middleware([\App\Http\Middleware\TokenVerificationMiddleware::class])->group(function () {

    //refresh API key
    Route::middleware('throttle:generate-or-refresh-token')
        ->post('/refresh-token', [\App\Http\Controllers\TokenManagerController::class, 'refreshToken']);

    //Main text filtration endpoint
    Route::prefix('v1')->group(function () {
        Route::middleware(['throttle:text-filter', \App\Http\Middleware\CheckIsUserActiveMiddleware::class])
            ->post('/text-filter', [\App\Http\Controllers\TextFiltrationController::class, 'textFilter']);
    });
});

