<?php

namespace App\Http\Controllers\Api\Donor;

use App\Http\Controllers\Controller;
use App\Models\AffectedEvent;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get donor home data including recent events, nearby families, and recently affected
     */
    public function index(Request $request): JsonResponse
    {
        // Validate optional location parameters
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:500',
        ]);

        try {
            $data = [
                'recent_events' => $this->getRecentEvents(),
                'recently_affected' => $this->getRecentlyAffected(),
            ];

            // If location provided, fetch nearby families sorted by distance
            // Otherwise, return all approved families
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $data['family_near_you'] = $this->getNearbyFamilies(
                    $request->latitude,
                    $request->longitude,
                    $request->input('radius', 50)
                );
            } else {
                $data['family_near_you'] = $this->getAllApprovedFamilies();
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch donor home data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent/featured affected events
     */
    private function getRecentEvents(int $limit = 5): array
    {
        $events = AffectedEvent::active()
            ->where(function ($query) {
                $query->where('is_featured', true)
                      ->orWhereNotNull('id'); // Include all active events
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'name', 'image_url']);

        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'image' => $event->image_url,
                'title' => $event->name,
            ];
        })->toArray();
    }

    /**
     * Get nearby families based on location
     */
    private function getNearbyFamilies(float $latitude, float $longitude, int $radiusMiles = 50, int $limit = 10): array
    {
        $beneficiaries = Beneficiary::approved()
            ->nearby($latitude, $longitude, $radiusMiles)
            ->with(['user.desiredItems.category'])
            ->limit($limit)
            ->get();

        return $beneficiaries->map(function ($beneficiary) {
            return $this->formatBeneficiaryData($beneficiary, true);
        })->toArray();
    }

    /**
     * Get recently affected families
     */
    private function getRecentlyAffected(int $limit = 10): array
    {
        $beneficiaries = Beneficiary::approved()
            ->with(['user.desiredItems.category'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $beneficiaries->map(function ($beneficiary) {
            return $this->formatBeneficiaryData($beneficiary, false);
        })->toArray();
    }

    /**
     * Get all approved families (when no location is provided)
     */
    private function getAllApprovedFamilies(int $limit = 20): array
    {
        $beneficiaries = Beneficiary::approved()
            ->with(['user.desiredItems.category'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $beneficiaries->map(function ($beneficiary) {
            return $this->formatBeneficiaryData($beneficiary, false);
        })->toArray();
    }

    /**
     * Format beneficiary data for response
     */
    private function formatBeneficiaryData(Beneficiary $beneficiary, bool $includeDistance = false): array
    {
        $user = $beneficiary->user;
        
        // Get desired items grouped by category with full product details
        $desiredItemsByCategory = $user->desiredItems()
            ->with('category')
            ->active()
            ->get()
            ->groupBy('category_id')
            ->map(function ($items) {
                $category = $items->first()->category;
                return [
                    'id' => $category->id,
                    'icon' => $category->icon_url,
                    'name' => $category->name,
                    'items' => $items->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'image' => $product->image_url,
                            'name' => $product->title,
                            'price' => $product->price,
                            'currency' => $product->currency,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $data = [
            'id' => $beneficiary->id,
            'image' => $beneficiary->family_photo_url,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'location' => $beneficiary->location_string,
            'affected_event' => $beneficiary->affected_event,
            'desired_items' => $desiredItemsByCategory,
        ];

        // Add distance if available
        if ($includeDistance && isset($beneficiary->distance_miles)) {
            $data['distance_miles'] = round($beneficiary->distance_miles, 2);
        }

        return $data;
    }
}
