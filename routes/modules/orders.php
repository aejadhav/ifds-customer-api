<?php

use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.customer', 'throttle:60,1'])->group(function () {
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('orders/{orderNumber}/cancel', [OrderController::class, 'cancel'])
        ->middleware('throttle:10,1');
    Route::get('orders/{orderNumber}/delivery-note', [OrderController::class, 'deliveryNote'])
        ->middleware('throttle:30,1');
});
