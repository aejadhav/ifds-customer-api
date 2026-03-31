<?php

use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.customer')->group(function () {
    Route::get('invoices', [PaymentController::class, 'invoices']);
    Route::get('invoices/{id}', [PaymentController::class, 'showInvoice']);
    Route::get('payments', [PaymentController::class, 'payments']);
    Route::post('payments', [PaymentController::class, 'store']);
});
