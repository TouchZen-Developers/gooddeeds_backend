<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AffectedEventRequest;
use App\Models\AffectedEvent;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AffectedEventController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
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

        // Filter by featured status if provided
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Search by name if provided
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $affectedEvents = $query->withCount('beneficiaries as total_families')->ordered()->paginate(15);

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
        $data = $request->validated();
        $imageUrl = null;

        // Handle image upload if present
        if ($request->hasFile('image')) {
            try {
                $validationErrors = $this->fileUploadService->validateImage($request->file('image'));
                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image validation failed: ' . implode(', ', $validationErrors)
                    ], 400);
                }

                $imageUrl = $this->fileUploadService->uploadToS3(
                    $request->file('image'),
                    'affected-events'
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image: ' . $e->getMessage()
                ], 400);
            }
        }

        // Remove image from data array and add image_url
        unset($data['image']);
        $data['image_url'] = $imageUrl;

        $affectedEvent = AffectedEvent::create($data);

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
        $affectedEvent->loadCount('beneficiaries as total_families');
        
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
        $data = $request->validated();

        // Handle image upload if present
        if ($request->hasFile('image')) {
            try {
                $validationErrors = $this->fileUploadService->validateImage($request->file('image'));
                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image validation failed: ' . implode(', ', $validationErrors)
                    ], 400);
                }

                // Delete old image if exists
                if ($affectedEvent->image_url) {
                    $this->fileUploadService->deleteFromS3($affectedEvent->image_url);
                }

                // Upload new image
                $imageUrl = $this->fileUploadService->uploadToS3(
                    $request->file('image'),
                    'affected-events'
                );
                $data['image_url'] = $imageUrl;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image: ' . $e->getMessage()
                ], 400);
            }
        }

        // Remove image from data array if not uploaded
        unset($data['image']);

        $affectedEvent->update($data);

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

        // Delete image from S3 if exists
        if ($affectedEvent->image_url) {
            try {
                $this->fileUploadService->deleteFromS3($affectedEvent->image_url);
            } catch (\Exception $e) {
                // Log error but don't fail the deletion
                Log::warning('Failed to delete image from S3: ' . $e->getMessage());
            }
        }

        $affectedEvent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Affected event deleted successfully.',
        ]);
    }

    /**
     * Get recent/featured affected events (public, no authentication required)
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $limit = min($limit, 50); // Max 50

        $events = AffectedEvent::active()
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'name', 'image_url', 'is_featured']);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}
