<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    /**
     * Delete user account by email address
     * 
     * This permanently deletes:
     * - User record
     * - All Sanctum tokens
     * - All OTPs associated with the email
     * - Beneficiary profile (via cascade)
     * - Desired items (via cascade)
     */
    public function deleteByEmail(DeleteUserRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        try {
            DB::beginTransaction();

            // Find user by email
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Store user info for response
            $deletedUserInfo = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_social_user' => $user->isSocialUser(),
                'social_provider' => $user->social_provider,
            ];

            // Delete associated OTPs by email
            Otp::where('email', $email)->delete();

            // Delete Sanctum tokens
            $user->tokens()->delete();

            // Delete user (cascades to beneficiary profile and desired items)
            $user->delete();

            DB::commit();

            Log::info('User account deleted by admin', [
                'deleted_user' => $deletedUserInfo,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User account and all associated data have been permanently deleted.',
                'deleted_user' => $deletedUserInfo,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete user account', [
                'email' => $email,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user account: ' . $e->getMessage(),
            ], 500);
        }
    }
}

