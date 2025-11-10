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
    public function callback(string $provider, Request $request)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Find or create user
            $user = $this->findOrCreateUser($socialUser, $provider);
            
            // Generate token
            $token = $user->createToken('social-auth')->plainTextToken;

            // Always redirect to frontend with token and next_step
            $redirectBase = config('services.frontend.social_login_redirect_url', 'gooddeeds://auth/callback');
            $nextStep = $user->is_profile_complete ? 'dashboard' : 'complete_profile';
            $query = http_build_query([
                'token' => $token,
                'next_step' => $nextStep,
                'provider' => $provider,
            ]);

            return redirect()->away(rtrim($redirectBase, '/') . '?' . $query);

        } catch (\Exception $e) {
            // Always redirect to frontend with error information
            $errorRedirect = config('services.frontend.social_login_error_redirect_url', 'gooddeeds://auth/callback');
            $query = http_build_query(['error' => 'social_auth_failed', 'message' => $e->getMessage()]);
            return redirect()->away(rtrim($errorRedirect, '/') . '?' . $query);
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
     * Authenticate with social provider ID token (for mobile apps)
     */
    public function authenticateWithToken(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'provider' => 'required|string|in:google,apple',
            'id_token' => 'required|string',
            'role' => 'nullable|string|in:donor,beneficiary',
        ]);

        try {
            $provider = $request->provider;
            $idToken = $request->id_token;
            $role = $request->role ?? 'donor'; // Default to donor if not specified

            // Validate ID token with provider
            $socialUser = $this->validateIdToken($provider, $idToken);

            // Find or create user with specified role
            $user = $this->findOrCreateUserWithRole($socialUser, $provider, $role);

            // Generate authentication token
            $token = $user->createToken('mobile-social-auth')->plainTextToken;

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
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 401);
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
     * Find or create user from social provider data (legacy method for web OAuth)
     */
    private function findOrCreateUser($socialUser, string $provider): User
    {
        // Default to donor role for backward compatibility with web OAuth
        return $this->findOrCreateUserWithRole($socialUser, $provider, 'donor');
    }

    /**
     * Find or create user from social provider data with specified role
     */
    private function findOrCreateUserWithRole($socialUser, string $provider, string $role): User
    {
        // Determine the ID field based on socialUser type
        $socialUserId = is_object($socialUser) && method_exists($socialUser, 'getId') 
            ? $socialUser->getId() 
            : $socialUser->id;
        
        $socialUserEmail = is_object($socialUser) && method_exists($socialUser, 'getEmail')
            ? $socialUser->getEmail()
            : $socialUser->email;
            
        $socialUserName = is_object($socialUser) && method_exists($socialUser, 'getName')
            ? $socialUser->getName()
            : ($socialUser->name ?? '');
            
        $socialUserAvatar = is_object($socialUser) && method_exists($socialUser, 'getAvatar')
            ? $socialUser->getAvatar()
            : ($socialUser->avatar ?? null);

        // Try to find existing user by social ID
        $user = User::where($provider . '_id', $socialUserId)->first();
        
        if ($user) {
            // Existing user found by social ID - preserve their existing role
            return $user;
        }

        // Try to find existing user by email
        $user = User::where('email', $socialUserEmail)->first();
        
        if ($user) {
            // Link social account to existing user - preserve their existing role
            $user->update([
                $provider . '_id' => $socialUserId,
                'social_provider' => $provider,
                'social_avatar_url' => $socialUserAvatar,
            ]);
            
            return $user;
        }

        // Create new user with specified role
        $fullName = trim($socialUserName ?? '');
        $firstName = $fullName !== '' ? explode(' ', $fullName)[0] : null;
        $lastName = $fullName !== '' ? trim(implode(' ', array_slice(explode(' ', $fullName), 1))) : null;
        if (!$firstName) {
            $firstName = 'Social';
        }
        if ($lastName === '' || $lastName === null) {
            $lastName = 'User';
        }
        $name = trim($firstName . ' ' . $lastName);

        $user = User::create([
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $socialUserEmail,
            'password' => Hash::make(Str::random(32)), // Random password for social users
            'role' => $role, // Use specified role (donor or beneficiary)
            $provider . '_id' => $socialUserId,
            'social_provider' => $provider,
            'social_avatar_url' => $socialUserAvatar,
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

    /**
     * Validate ID token from social provider
     */
    private function validateIdToken(string $provider, string $idToken): object
    {
        if ($provider === 'google') {
            return $this->validateGoogleToken($idToken);
        } elseif ($provider === 'apple') {
            return $this->validateAppleToken($idToken);
        }

        throw new \InvalidArgumentException('Invalid social provider');
    }

    /**
     * Validate Google ID token using Google API Client
     */
    private function validateGoogleToken(string $idToken): object
    {
        try {
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                throw new \Exception('Invalid Google ID token');
            }

            // Return standardized user object
            return (object) [
                'id' => $payload['sub'],
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? '',
                'avatar' => $payload['picture'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Google token validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate Apple JWT token using Apple's public keys
     */
    private function validateAppleToken(string $idToken): object
    {
        try {
            // Fetch Apple's public keys
            $appleKeysUrl = 'https://appleid.apple.com/auth/keys';
            $appleKeysJson = file_get_contents($appleKeysUrl);
            if (!$appleKeysJson) {
                throw new \Exception('Failed to fetch Apple public keys');
            }

            $appleKeys = json_decode($appleKeysJson, true);
            if (!isset($appleKeys['keys'])) {
                throw new \Exception('Invalid Apple keys response');
            }

            // Use Firebase JWT to parse the key set and decode the token
            $parsedKeys = \Firebase\JWT\JWK::parseKeySet($appleKeys);
            
            // Decode and verify the JWT
            $decoded = \Firebase\JWT\JWT::decode($idToken, $parsedKeys);

            // Validate claims
            $clientId = config('services.apple.client_id');
            if ($decoded->iss !== 'https://appleid.apple.com') {
                throw new \Exception('Invalid token issuer');
            }
            if ($decoded->aud !== $clientId) {
                throw new \Exception('Invalid token audience');
            }
            if ($decoded->exp < time()) {
                throw new \Exception('Token has expired');
            }

            // Return standardized user object
            return (object) [
                'id' => $decoded->sub,
                'email' => $decoded->email ?? null,
                'name' => '', // Apple doesn't provide name in token, only on first auth
                'avatar' => null,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Apple token validation failed: ' . $e->getMessage());
        }
    }
}