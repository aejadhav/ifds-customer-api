<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('register',   [CustomerAuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('send-otp',   [CustomerAuthController::class, 'sendOtp'])->middleware('throttle:5,1');
    Route::post('verify-otp', [CustomerAuthController::class, 'verifyOtp'])->middleware('throttle:10,1');

    // Protected auth routes
    Route::middleware('auth.customer')->group(function () {
        Route::post('refresh', [CustomerAuthController::class, 'refresh']);
        Route::post('logout',  [CustomerAuthController::class, 'logout']);
        Route::get('me',       [CustomerAuthController::class, 'me']);
    });
});
