<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\BulkProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        // Filter by provider
        if ($request->has('provider')) {
            $query->byProvider($request->provider);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(ProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->addProduct(
                $request->url,
                $request->category_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Product added successfully',
                'data' => $product->load('category')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to add product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified product
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $product->load('category')
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        try {
            $product->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load('category')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bulk import products from URLs
     */
    public function bulkImport(BulkProductRequest $request): JsonResponse
    {
        try {
            $results = $this->productService->bulkImport(
                $request->urls,
                $request->category_id
            );

            $successCount = collect($results)->where('success', true)->count();
            $failureCount = collect($results)->where('success', false)->count();

            return response()->json([
                'success' => true,
                'message' => "Bulk import completed. {$successCount} successful, {$failureCount} failed.",
                'data' => [
                    'results' => $results,
                    'summary' => [
                        'total' => count($results),
                        'successful' => $successCount,
                        'failed' => $failureCount
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to bulk import products: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk import products: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Refresh product details from API
     */
    public function refresh(Product $product): JsonResponse
    {
        try {
            $this->productService->fetchProductDetails($product);

            return response()->json([
                'success' => true,
                'message' => 'Product details refreshed successfully',
                'data' => $product->fresh()->load('category')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to refresh product details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh product details: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Toggle product active status
     */
    public function toggleStatus(Product $product): JsonResponse
    {
        try {
            $product->update(['is_active' => !$product->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Product status updated successfully',
                'data' => $product->load('category')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle product status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product status: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Toggle product featured status
     */
    public function toggleFeatured(Product $product): JsonResponse
    {
        try {
            $product->update(['is_featured' => !$product->is_featured]);

            return response()->json([
                'success' => true,
                'message' => 'Product featured status updated successfully',
                'data' => $product->load('category')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle product featured status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product featured status: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get product statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::active()->count(),
            'featured_products' => Product::featured()->count(),
            'by_provider' => Product::selectRaw('provider, COUNT(*) as count')
                ->groupBy('provider')
                ->pluck('count', 'provider'),
            'by_category' => Product::with('category')
                ->selectRaw('category_id, COUNT(*) as count')
                ->groupBy('category_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->category->name ?? 'Uncategorized' => $item->count];
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
