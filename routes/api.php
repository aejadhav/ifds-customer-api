<?php

use Illuminate\Support\Facades\Route;

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
