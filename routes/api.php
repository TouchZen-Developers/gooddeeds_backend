<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected admin routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Public API route for testing
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now(),
    ]);
});
