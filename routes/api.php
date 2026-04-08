<?php

use Illuminate\Support\Facades\Route;

// Public: website contact form — proxies to IFDS lead creation
Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name'    => ['required', 'string', 'max:255'],
        'email'   => ['required', 'email', 'max:255'],
        'phone'   => ['nullable', 'string', 'max:20'],
        'message' => ['required', 'string', 'max:2000'],
    ]);

    $base = rtrim(config('services.ifds.base_url', 'http://localhost:8000'), '/');
    $res  = \Illuminate\Support\Facades\Http::timeout(10)
        ->post("{$base}/api/v1/contact", $request->only('name', 'email', 'phone', 'message'));

    return response()->json(
        $res->json() ?? ['success' => false, 'message' => 'Failed to send.'],
        $res->successful() ? 200 : ($res->status() ?: 502)
    );
})->middleware('throttle:10,1');

Route::prefix('v1')->group(function () {
    require __DIR__ . '/modules/auth.php';
    require __DIR__ . '/modules/profile.php';
    require __DIR__ . '/modules/orders.php';
    require __DIR__ . '/modules/payments.php';
    require __DIR__ . '/modules/internal.php';
    require __DIR__ . '/modules/broadcasting.php';
    require __DIR__ . '/modules/notifications.php';
    require __DIR__ . '/modules/support.php';
    require __DIR__ . '/modules/reports.php';
});
