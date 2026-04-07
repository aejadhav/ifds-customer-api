<?php

use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.customer', 'throttle:60,1'])->group(function () {
    Route::get('invoices', [PaymentController::class, 'invoices']);
    Route::get('invoices/{id}', [PaymentController::class, 'showInvoice']);
    Route::get('payments', [PaymentController::class, 'payments']);
    Route::post('payments', [PaymentController::class, 'store'])->middleware('throttle:10,1');
});
