<?php

namespace App\Http\Controllers\Api\Donor;

use App\Http\Controllers\Controller;
use App\Models\AffectedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Get detailed information about a specific event including affected families
     * 
     * @param Request $request
     * @param AffectedEvent $event
     * @return JsonResponse
     */
    public function show(Request $request, AffectedEvent $event): JsonResponse
    {
        try {
            // Get approved beneficiaries associated with this event
            $beneficiaries = $event->beneficiaries()
                ->where('status', 'approved')
                ->with([
                    'user:id,first_name,last_name,email',
                    'user.desiredItems' => function ($query) {
                        $query->active()->with('category:id,name,icon_url');
                    },
                ])
                ->get();

            // Format the affected families data
            $affectedFamilies = $beneficiaries->map(function ($beneficiary) {
                // Get user's full name
                $fullName = trim($beneficiary->user->first_name . ' ' . $beneficiary->user->last_name);
                
                // Format location as "City, State"
                $location = $beneficiary->location_string;
                
                // Group desired items by category
                $desiredItemsGrouped = $beneficiary->user->desiredItems
                    ->groupBy('category_id')
                    ->map(function ($items, $categoryId) {
                        $category = $items->first()->category;
                        
                        return [
                            'id' => (int) $categoryId,
                            'category_id' => (int) $categoryId,
                            'icon' => $category->icon_url ?? null,
                            'name' => $category->name ?? 'Uncategorized',
                            'items' => $items->map(function ($product) {
                                return [
                                    'id' => $product->id,
                                    'image' => $product->image_url,
                                    'name' => $product->title,
                                    'count' => $product->pivot->quantity ?? 1,
                                ];
                            })->values()->toArray(),
                        ];
                    })
                    ->values()
                    ->toArray();

                return [
                    'id' => (string) $beneficiary->user_id,
                    'image' => $beneficiary->family_photo_url,
                    'name' => $fullName,
                    'location' => $location,
                    'desired_items' => $desiredItemsGrouped,
                ];
            })->values();

            // Format the final response
            $response = [
                'id' => (string) $event->id,
                'image' => $event->image_url,
                'title' => $event->name,
                'affected_families' => $affectedFamilies,
            ];

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch event details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active events for donors to browse
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Get all active events with beneficiary count
            $events = AffectedEvent::active()
                ->withCount(['beneficiaries' => function ($query) {
                    $query->where('status', 'approved');
                }])
                ->ordered()
                ->get();

            // Format the response
            $formattedEvents = $events->map(function ($event) {
                return [
                    'id' => (string) $event->id,
                    'image' => $event->image_url,
                    'title' => $event->name,
                    'is_featured' => $event->is_featured,
                    'affected_families_count' => $event->beneficiaries_count,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedEvents,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch events: ' . $e->getMessage(),
            ], 500);
        }
    }
}



