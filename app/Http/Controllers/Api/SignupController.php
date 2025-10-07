<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignupRequest;
use App\Http\Requests\SignupVerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class SignupController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Signup - Store user data and send OTP
     */
    public function signup(SignupRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Hash password and prepare metadata with all user data
        $hashedPassword = Hash::make($data['password']);
        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'password_hash' => $hashedPassword,
        ];
        
        $result = $this->otpService->sendSignupOtpWithData($data['email'], $userData);
        
        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Verify OTP and create user account
     */
    public function verifyOtp(SignupVerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Unified verification: detect context and create proper account
        $result = $this->otpService->verifyOtpAndCreateAccount($data['email'], $data['otp']);

        $statusCode = $result['success'] ? 201 : 400;
        return response()->json($result, $statusCode);
    }
}