<?php

use App\Http\Controllers\Internal\SyncController;
use Illuminate\Support\Facades\Route;

// Internal endpoints — no JWT auth, secured by HMAC signature in controller
Route::post('internal/sync-result', [SyncController::class, 'syncResult']);
