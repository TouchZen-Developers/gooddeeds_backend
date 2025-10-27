<?php

use App\Http\Controllers\Api\Admin\AffectedEventController;
use App\Http\Controllers\Api\Admin\BeneficiaryManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\BeneficiarySignupController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\SignupController;
use App\Http\Controllers\Api\SocialAuthController;
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

// Social Authentication Routes (public)
Route::prefix('auth/social')->middleware('web')->group(function () {
    // Redirect to social provider
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'google|apple');
    
    // Handle social provider callback
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google|apple');
});

// Social Authentication API Routes (requires authentication)
Route::prefix('auth/social')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/complete-profile', [SocialAuthController::class, 'completeProfile']);
});

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Beneficiary Status Check (auth-protected, beneficiary only)
    Route::get('/beneficiaries/status', [BeneficiaryController::class, 'getStatus']);
});

// Admin-only routes (requires admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::post('categories/upload', [CategoryController::class, 'uploadIcon']);
    
    // Affected Events
    Route::apiResource('affected-events', AffectedEventController::class);
    
    // Beneficiary Management
    Route::prefix('beneficiaries')->group(function () {
        Route::get('/', [BeneficiaryManagementController::class, 'index']);
        Route::get('/statistics', [BeneficiaryManagementController::class, 'statistics']);
        Route::get('/{beneficiary}', [BeneficiaryManagementController::class, 'show']);
        Route::post('/{beneficiary}/approve', [BeneficiaryManagementController::class, 'approve']);
        Route::post('/{beneficiary}/reject', [BeneficiaryManagementController::class, 'reject']);
    });
});

// Public API routes
Route::get('/affected-events', function () {
    $affectedEvents = \App\Models\AffectedEvent::active()->ordered()->get(['id', 'name']);
    
    return response()->json([
        'success' => true,
        'data' => $affectedEvents,
    ]);
});

// Public API route for testing
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now(),
    ]);
});
