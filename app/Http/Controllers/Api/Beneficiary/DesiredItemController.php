<?php

namespace App\Http\Controllers\Api\Beneficiary;

use App\Http\Controllers\Controller;
use App\Http\Requests\BeneficiaryDesiredItemRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesiredItemController extends Controller
{
    /**
     * Get all desired items for the authenticated beneficiary, grouped by category
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get desired items with product and category details
        $desiredItems = $user->desiredItems()
            ->with(['category'])
            ->active() // Only show active products
            ->get();

        // Group by category
        $groupedByCategory = $desiredItems->groupBy('category_id')->map(function ($items, $categoryId) {
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
                        'count' => $product->pivot->quantity,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return response()->json($groupedByCategory);
    }

    /**
     * Add or update desired items (replaces all items)
     */
    public function store(BeneficiaryDesiredItemRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Prepare sync data
            $syncData = [];
            foreach ($request->items as $item) {
                $syncData[$item['product_id']] = [
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Sync items (this will replace all existing items)
            $user->desiredItems()->sync($syncData);

            // Get updated items
            $desiredItems = $user->desiredItems()
                ->with(['category'])
                ->active()
                ->get();

            // Group by category
            $groupedByCategory = $desiredItems->groupBy('category_id')->map(function ($items, $categoryId) {
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
                            'count' => $product->pivot->quantity,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Desired items updated successfully.',
                'data' => $groupedByCategory,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update desired items: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a single desired item quantity
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Validate input
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        try {
            // Check if the product is in user's desired items
            if (!$user->desiredItems()->where('product_id', $product->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in your desired items.',
                ], 404);
            }

            // Update quantity
            $user->desiredItems()->updateExistingPivot($product->id, [
                'quantity' => $request->quantity,
                'updated_at' => now(),
            ]);

            // Get updated product
            $updatedProduct = $user->desiredItems()
                ->with(['category'])
                ->where('product_id', $product->id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Item quantity updated successfully.',
                'data' => [
                    'product_id' => $updatedProduct->id,
                    'title' => $updatedProduct->title,
                    'quantity' => $updatedProduct->pivot->quantity,
                    'updated_at' => $updatedProduct->pivot->updated_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a specific product from desired items
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        try {
            // Check if the product is in user's desired items
            if (!$user->desiredItems()->where('product_id', $product->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in your desired items.',
                ], 404);
            }

            // Detach the product
            $user->desiredItems()->detach($product->id);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from desired items successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all categories with their products for beneficiaries to browse and select
     */
    public function catalog(Request $request): JsonResponse
    {
        try {
            // Get all categories with their active products
            $categories = Category::with(['products' => function ($query) {
                $query->active() // Only active products
                      ->select('id', 'title', 'image_url', 'price', 'currency', 'category_id')
                      ->orderBy('title', 'asc');
            }])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'icon_url']);

            // Format response according to specification
            $formattedCategories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'icon' => $category->icon_url,
                    'name' => $category->name,
                    'items' => $category->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'image' => $product->image_url,
                            'name' => $product->title,
                            // 'price' => $product->price,
                            // 'currency' => $product->currency,
                           // 'max' => 100, // Maximum quantity a beneficiary can request
                        ];
                    })->values()->toArray(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedCategories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product catalog: ' . $e->getMessage(),
            ], 500);
        }
    }
}
