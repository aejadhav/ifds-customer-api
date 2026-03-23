<?php

use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.customer')->group(function () {
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{orderNumber}', [OrderController::class, 'show']);
});
