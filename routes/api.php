<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeneficiarySignupController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\SignupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes (for all user types)
Route::post('/login', [AuthController::class, 'login']);

// Signup Routes (public)
Route::post('/auth/signup', [SignupController::class, 'signup']);
Route::post('/auth/signup/verify-otp', [SignupController::class, 'verifyOtp']);

// Beneficiary Signup Routes (public) - verify uses unified controller
Route::post('/auth/beneficiaries/signup', [BeneficiarySignupController::class, 'signup']);
Route::post('/auth/beneficiaries/verify-otp', [SignupController::class, 'verifyOtp']);

// Forgot Password Routes (public)
Route::prefix('forgot-password')->group(function () {
    Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp']);
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('/reset', [ForgotPasswordController::class, 'resetPassword']);
});

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Admin-only routes (requires admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::post('categories/upload', [CategoryController::class, 'uploadIcon']);
});

// Public API route for testing
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now(),
    ]);
});
