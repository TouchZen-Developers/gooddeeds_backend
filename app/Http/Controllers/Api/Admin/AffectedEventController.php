<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AffectedEventRequest;
use App\Models\AffectedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffectedEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AffectedEvent::query();

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name if provided
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $affectedEvents = $query->ordered()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $affectedEvents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AffectedEventRequest $request): JsonResponse
    {
        $affectedEvent = AffectedEvent::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Affected event created successfully.',
            'data' => $affectedEvent,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AffectedEvent $affectedEvent): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $affectedEvent,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AffectedEventRequest $request, AffectedEvent $affectedEvent): JsonResponse
    {
        $affectedEvent->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Affected event updated successfully.',
            'data' => $affectedEvent,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AffectedEvent $affectedEvent): JsonResponse
    {
        // Check if any beneficiaries are using this affected event
        $beneficiariesCount = $affectedEvent->beneficiaries()->count();
        
        if ($beneficiariesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete affected event. It is being used by {$beneficiariesCount} beneficiary(ies).",
            ], 422);
        }

        $affectedEvent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Affected event deleted successfully.',
        ]);
    }
}
