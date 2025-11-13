<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * User login (for all user types: admin, donor, beneficiary)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Create token based on user role
        $tokenName = $user->role . '-token';
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user()->loadMissing(['beneficiary']);

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'social_provider' => $user->social_provider,
            'is_social_user' => $user->isSocialUser(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];

        $beneficiaryProfile = null;

        if ($user->isBeneficiary() && $user->beneficiary) {
            $beneficiary = $user->beneficiary;

            $beneficiaryProfile = [
                'id' => $beneficiary->id,
                'status' => $beneficiary->status,
                'is_approved' => $beneficiary->isApproved(),
                'processed_at' => $beneficiary->processed_at?->toISOString(),
                'family_size' => $beneficiary->family_size,
                'address' => $beneficiary->address,
                'city' => $beneficiary->city,
                'state' => $beneficiary->state,
                'zip_code' => $beneficiary->zip_code,
                'latitude' => $beneficiary->latitude,
                'longitude' => $beneficiary->longitude,
                'affected_event' => $beneficiary->affected_event,
                'statement' => $beneficiary->statement,
                'family_photo_url' => $beneficiary->family_photo_url,
                'identity_proof' => $beneficiary->identity_proof,
                'location' => $beneficiary->location_string,
                'has_location' => $beneficiary->hasLocation(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => array_merge($userData, [
                    'beneficiary_profile' => $beneficiaryProfile
                ]),
            ],
        ], 200);
    }

    /**
     * Format the authenticated user's desired items grouped by category.
     */
    private function formatDesiredItemsByCategory(User $user): array
    {
        $desiredItems = $user->desiredItems()
            ->with('category:id,name,icon_url')
            ->active()
            ->get();

        if ($desiredItems->isEmpty()) {
            return [];
        }

        return $desiredItems->groupBy('category_id')->map(function ($items, $categoryId) {
            $category = $items->first()->category;

            return [
                'id' => $category?->id ?? (int) $categoryId,
                'category_id' => $category?->id ?? (int) $categoryId,
                'icon' => $category?->icon_url,
                'name' => $category?->name ?? 'Uncategorized',
                'items' => $items->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'image' => $product->image_url,
                        'name' => $product->title,
                        'count' => $product->pivot->quantity ?? 1,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
    }
}
