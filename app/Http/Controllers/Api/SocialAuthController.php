<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialDonorCompleteProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Apple\Provider as AppleProvider;

class SocialAuthController extends Controller
{

    /**
     * Redirect to social provider for authentication
     */
    public function redirect(string $provider): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle social provider callback
     */
    public function callback(string $provider, Request $request): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Find or create user
            $user = $this->findOrCreateUser($socialUser, $provider);
            
            // Generate token
            $token = $user->createToken('social-auth')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Social authentication successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'social_provider' => $user->social_provider,
                        'is_profile_complete' => $user->is_profile_complete,
                        'social_avatar_url' => $user->social_avatar_url,
                    ],
                    'token' => $token,
                    'next_step' => $user->is_profile_complete ? 'dashboard' : 'complete_profile'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social authentication failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete profile after social authentication
     */
    public function completeProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate user can complete profile
        if ($user->is_profile_complete) {
            return response()->json([
                'success' => false,
                'message' => 'Profile already completed'
            ], 400);
        }

        // Determine validation rules based on user role
        if ($user->role === User::ROLE_DONOR) {
            $request->validate([
                'phone_number' => 'required|string|max:20',
            ]);
        } else {
            // Beneficiary validation
            $request->validate([
                'phone_number' => 'required|string|max:20',
                'family_size' => 'required|integer|min:1|max:20',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'zip_code' => 'required|string|max:20',
                'affected_event' => 'required|string|max:255',
                'statement' => 'required|string|max:1000',
                'family_photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        try {
            if ($user->role === User::ROLE_DONOR) {
                // Complete donor profile
                $this->completeDonorProfile($user, $request);
            } else {
                // Create beneficiary profile
                $beneficiary = $this->createBeneficiaryProfile($user, $request);
            }
            
            // Mark profile as complete and activate account
            $user->markProfileComplete();

            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'social_provider' => $user->social_provider,
                    'is_profile_complete' => true,
                ],
                'next_step' => 'dashboard'
            ];

            // Add role-specific data
            if ($user->role === User::ROLE_DONOR) {
                $responseData['donor_id'] = $user->id;
            } else {
                $responseData['beneficiary_id'] = $beneficiary->id;
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile completed and account activated successfully.',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate social provider
     */
    private function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'apple'])) {
            throw new \InvalidArgumentException('Invalid social provider');
        }
    }

    /**
     * Find or create user from social provider data
     */
    private function findOrCreateUser($socialUser, string $provider): User
    {
        // Try to find existing user by social ID
        $user = User::where($provider . '_id', $socialUser->getId())->first();
        
        if ($user) {
            return $user;
        }

        // Try to find existing user by email
        $user = User::where('email', $socialUser->getEmail())->first();
        
        if ($user) {
            // Link social account to existing user
            $user->update([
                $provider . '_id' => $socialUser->getId(),
                'social_provider' => $provider,
                'social_avatar_url' => $socialUser->getAvatar(),
            ]);
            
            return $user;
        }

        // Create new user
        $user = User::create([
            'first_name' => $socialUser->getName() ? explode(' ', $socialUser->getName())[0] : 'Social',
            'last_name' => $socialUser->getName() ? implode(' ', array_slice(explode(' ', $socialUser->getName()), 1)) : 'User',
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Random password for social users
            'role' => User::ROLE_DONOR, // Default to donor for social signup
            $provider . '_id' => $socialUser->getId(),
            'social_provider' => $provider,
            'social_avatar_url' => $socialUser->getAvatar(),
            'is_profile_complete' => false,
        ]);

        return $user;
    }

    /**
     * Complete donor profile
     */
    private function completeDonorProfile(User $user, Request $request): void
    {
        // Update user with phone number
        $user->update([
            'phone_number' => $request->phone_number,
        ]);
    }

    /**
     * Create beneficiary profile
     */
    private function createBeneficiaryProfile(User $user, Request $request)
    {
        $fileUploadService = app(\App\Services\FileUploadService::class);
        $imageUrl = null;

        // Handle family photo upload
        if ($request->hasFile('family_photo')) {
            $imageUrl = $fileUploadService->uploadToS3(
                $request->file('family_photo'),
                'beneficiaries'
            );
        }

        // Create beneficiary profile
        return $user->beneficiary()->create([
            'family_size' => $request->family_size,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'affected_event' => $request->affected_event,
            'statement' => $request->statement,
            'family_photo_url' => $imageUrl,
            'status' => 'pending',
        ]);
    }
}