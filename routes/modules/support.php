<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.customer')->group(function () {
    // Stub — logs the ticket and returns success. Full implementation pending.
    Route::post('support/tickets', function (Request $request) {
        \Illuminate\Support\Facades\Log::info('Support ticket submitted', [
            'customer_id' => $request->user()?->id,
            'category'    => $request->input('category'),
            'subject'     => $request->input('subject'),
        ]);

        return response()->json(['ok' => true, 'message' => 'Your support request has been received. We will get back to you within 24 hours.'], 201);
    });
});
