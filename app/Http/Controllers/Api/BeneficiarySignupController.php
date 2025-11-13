<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BeneficiarySignupRequest;
use App\Http\Requests\BeneficiaryVerifyOtpRequest;
use App\Models\Beneficiary;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class BeneficiarySignupController extends Controller
{
    protected $otpService;
    protected $fileUploadService;

    public function __construct(OtpService $otpService, FileUploadService $fileUploadService)
    {
        $this->otpService = $otpService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Beneficiary signup - Store user data and send OTP
     */
    public function signup(BeneficiarySignupRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle family photo upload if present
        $familyPhotoUrl = null;
        if ($request->hasFile('family_photo')) {
            try {
                // Validate the image first
                $validationErrors = $this->fileUploadService->validateImage($request->file('family_photo'));
                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Family photo validation failed: ' . implode(', ', $validationErrors)
                    ], 400);
                }

                // Upload to S3
                $familyPhotoUrl = $this->fileUploadService->uploadToS3(
                    $request->file('family_photo'),
                    'beneficiaries/family-photos'
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload family photo: ' . $e->getMessage()
                ], 400);
            }
        }

        // Handle identity proof upload if present
        $identityProofUrl = null;
        if ($request->hasFile('identity_proof')) {
            try {
                // Validate the file first
                $validationErrors = $this->fileUploadService->validateImage($request->file('identity_proof'));
                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Identity proof validation failed: ' . implode(', ', $validationErrors)
                    ], 400);
                }

                // Upload to S3
                $identityProofUrl = $this->fileUploadService->uploadToS3(
                    $request->file('identity_proof'),
                    'beneficiaries/identity-proofs'
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload identity proof: ' . $e->getMessage()
                ], 400);
            }
        }

        // Hash password and prepare metadata with all user data
        $hashedPassword = Hash::make($data['password']);
        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'password_hash' => $hashedPassword,
            'role' => User::ROLE_BENEFICIARY,
        ];

        // Prepare beneficiary profile data
        $beneficiaryData = [
            'family_size' => $data['family_size'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
            'affected_event' => $data['affected_event'] ?? null,
            'statement' => $data['statement'] ?? null,
            'family_photo_url' => $familyPhotoUrl,
            'identity_proof' => $identityProofUrl,
        ];

        // Combine user and beneficiary data for OTP metadata
        $metadata = array_merge($userData, $beneficiaryData);

        $result = $this->otpService->sendOtpWithData($data['email'], 'signup_beneficiary', $metadata);

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Verify OTP and create beneficiary account
     */
    public function verifyOtp(BeneficiaryVerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->otpService->verifyOtpAndCreateBeneficiary($data['email'], $data['otp']);

        $statusCode = $result['success'] ? 201 : 400; // 201 for resource created
        return response()->json($result, $statusCode);
    }
}
