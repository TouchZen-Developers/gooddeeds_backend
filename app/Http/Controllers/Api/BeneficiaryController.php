<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    /**
     * Get the authenticated beneficiary's status
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ensure user is a beneficiary
        if (!$user->isBeneficiary()) {
            return response()->json([
                'success' => false,
                'message' => 'Only beneficiary users can access this endpoint.',
            ], 403);
        }

        // Get beneficiary profile
        $beneficiary = $user->beneficiary;

        if (!$beneficiary) {
            return response()->json([
                'success' => false,
                'message' => 'Beneficiary profile not found.',
            ], 404);
        }

        // Prepare status message
        $statusMessages = [
            'pending' => 'Your application is currently under review. We will notify you once a decision has been made.',
            'approved' => 'Congratulations! Your application has been approved. You can now access all beneficiary features.',
            'rejected' => 'Unfortunately, your application was not approved at this time. Please contact support for more information.',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role,
                ],
                'beneficiary' => [
                    'id' => $beneficiary->id,
                    'status' => $beneficiary->status,
                    'family_size' => $beneficiary->family_size,
                    'address' => $beneficiary->address,
                    'city' => $beneficiary->city,
                    'state' => $beneficiary->state,
                    'zip_code' => $beneficiary->zip_code,
                    'affected_event' => $beneficiary->affected_event,
                    'statement' => $beneficiary->statement,
                    'family_photo_url' => $beneficiary->family_photo_url,
                    'submitted_at' => $beneficiary->created_at->toIso8601String(),
                    'processed_at' => $beneficiary->processed_at ? $beneficiary->processed_at->toIso8601String() : null,
                ],
                'message' => $statusMessages[$beneficiary->status] ?? 'Status information is not available.',
            ],
        ]);
    }
}

