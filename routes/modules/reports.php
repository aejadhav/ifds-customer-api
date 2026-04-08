<?php

use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.customer', 'throttle:60,1'])->group(function () {
    Route::get('reports/summary', [ReportController::class, 'summary']);
});
