<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
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
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Category::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                ],
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle icon upload if provided
        if ($request->hasFile('icon')) {
            $iconFile = $request->file('icon');
            
            // Validate image
            $validationErrors = $this->fileUploadService->validateImage($iconFile);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Icon validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            $data['icon_url'] = $this->fileUploadService->uploadCategoryIcon($iconFile);
        }

        $category = Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => [
                'category' => $category,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
            ],
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        // Handle icon upload if provided
        if ($request->hasFile('icon')) {
            $iconFile = $request->file('icon');
            
            // Validate image
            $validationErrors = $this->fileUploadService->validateImage($iconFile);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Icon validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Delete old icon if exists
            if ($category->icon_url) {
                $this->fileUploadService->deleteFromS3($category->icon_url);
            }

            $data['icon_url'] = $this->fileUploadService->uploadCategoryIcon($iconFile);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => [
                'category' => $category->fresh(),
            ],
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Delete icon from S3 if exists
        if ($category->icon_url) {
            $this->fileUploadService->deleteFromS3($category->icon_url);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ], 200);
    }

    /**
     * Upload category icon
     */
    public function uploadIcon(Request $request): JsonResponse
    {
        $request->validate([
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $iconFile = $request->file('icon');
        
        // Validate image
        $validationErrors = $this->fileUploadService->validateImage($iconFile);
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Icon validation failed',
                'errors' => $validationErrors,
            ], 422);
        }

        $iconUrl = $this->fileUploadService->uploadCategoryIcon($iconFile);

        return response()->json([
            'success' => true,
            'message' => 'Icon uploaded successfully',
            'data' => [
                'icon_url' => $iconUrl,
            ],
        ], 200);
    }
}
