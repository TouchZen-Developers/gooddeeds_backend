<?php

namespace App\Http\Controllers\Api\Beneficiary;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLocationRequest;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * Update beneficiary's location (latitude and longitude)
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get beneficiary profile
            $beneficiary = $user->beneficiary;

            if (!$beneficiary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beneficiary profile not found.',
                ], 404);
            }

            // Get validated data
            $validated = $request->validated();

            // Update location
            $beneficiary->update([
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);

            // Refresh to get updated data
            $beneficiary->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully.',
                'data' => [
                    'latitude' => $beneficiary->latitude,
                    'longitude' => $beneficiary->longitude,
                    'location_string' => $beneficiary->location_string,
                    'city' => $beneficiary->city,
                    'state' => $beneficiary->state,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location: ' . $e->getMessage(),
            ], 500);
        }
    }
}

