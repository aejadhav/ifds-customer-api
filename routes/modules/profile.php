<?php

use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.customer', 'throttle:60,1'])->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::patch('profile/preferences', [ProfileController::class, 'updatePreferences']);

    Route::get('profile/addresses', [ProfileController::class, 'addresses']);
    Route::post('profile/addresses', [ProfileController::class, 'storeAddress']);
    Route::delete('profile/addresses/{id}', [ProfileController::class, 'deleteAddress']);
});
