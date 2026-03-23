<?php

use App\Http\Controllers\Broadcasting\BroadcastAuthController;
use Illuminate\Support\Facades\Route;

// JWT-authenticated broadcasting channel auth endpoint
Route::middleware('auth.customer')
    ->post('broadcasting/auth', [BroadcastAuthController::class, 'auth']);
