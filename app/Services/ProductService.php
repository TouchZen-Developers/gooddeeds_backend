<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductService
{
    /**
     * Add a product by URL and fetch its details
     */
    public function addProduct(string $url, int $categoryId): Product
    {
        // Detect provider from URL
        $provider = $this->detectProvider($url);
        
        // Extract external ID (ASIN, eBay Item ID, etc.)
        $externalId = $this->extractExternalId($url, $provider);
        
        // Extract domain
        $domain = $this->extractDomain($url);
        
        // Create product record
        $product = Product::create([
            'url' => $url,
            'provider' => $provider,
            'external_id' => $externalId,
            'domain' => $domain,
            'category_id' => $categoryId,
            'is_active' => true,
        ]);
        
        // Fetch product details
        $this->fetchProductDetails($product);
        
        return $product->fresh();
    }
    
    /**
     * Fetch product details from the appropriate API
     */
    public function fetchProductDetails(Product $product): array
    {
        try {
            $details = match ($product->provider) {
                Product::PROVIDER_AMAZON => $this->fetchAmazonProductDetails($product),
                Product::PROVIDER_EBAY => $this->fetchEbayProductDetails($product),
                Product::PROVIDER_WALMART => $this->fetchWalmartProductDetails($product),
                Product::PROVIDER_TARGET => $this->fetchTargetProductDetails($product),
                default => throw new Exception("Unsupported provider: {$product->provider}")
            };
            
            // Update product with fetched details
            $product->update($details);
            
            return $details;
            
        } catch (Exception $e) {
            Log::error("Failed to fetch product details for {$product->url}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Detect provider from URL
     */
    private function detectProvider(string $url): string
    {
        $url = strtolower($url);
        
        if (str_contains($url, 'amazon.com') || str_contains($url, 'amazon.co.uk') || str_contains($url, 'amazon.ca')) {
            return Product::PROVIDER_AMAZON;
        }
        
        if (str_contains($url, 'ebay.com') || str_contains($url, 'ebay.co.uk')) {
            return Product::PROVIDER_EBAY;
        }
        
        if (str_contains($url, 'walmart.com')) {
            return Product::PROVIDER_WALMART;
        }
        
        if (str_contains($url, 'target.com')) {
            return Product::PROVIDER_TARGET;
        }
        
        throw new Exception("Unsupported e-commerce provider for URL: {$url}");
    }
    
    /**
     * Extract external ID from URL based on provider
     */
    private function extractExternalId(string $url, string $provider): ?string
    {
        return match ($provider) {
            Product::PROVIDER_AMAZON => $this->extractAmazonAsin($url),
            Product::PROVIDER_EBAY => $this->extractEbayItemId($url),
            Product::PROVIDER_WALMART => $this->extractWalmartItemId($url),
            Product::PROVIDER_TARGET => $this->extractTargetItemId($url),
            default => null
        };
    }
    
    /**
     * Extract domain from URL
     */
    private function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;
        if ($host === null) {
            return null;
        }

        // Normalize common amazon subdomains to the expected domain format used by Rainforest
        // e.g. www.amazon.com => amazon.com, m.amazon.co.uk => amazon.co.uk
        $host = strtolower($host);
        $parts = explode('.', $host);
        $amazonIndex = array_search('amazon', $parts, true);
        if ($amazonIndex !== false) {
            $normalized = implode('.', array_slice($parts, $amazonIndex));
            return $normalized; // e.g. amazon.com, amazon.co.uk
        }

        // Default: strip leading www.
        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        }

        return $host;
    }
    
    /**
     * Extract Amazon ASIN from URL
     */
    private function extractAmazonAsin(string $url): ?string
    {
        // Pattern: /dp/ASIN or /product/ASIN or ?asin=ASIN
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/\/product\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/[?&]asin=([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract eBay Item ID from URL
     */
    private function extractEbayItemId(string $url): ?string
    {
        if (preg_match('/\/itm\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract Walmart Item ID from URL
     */
    private function extractWalmartItemId(string $url): ?string
    {
        if (preg_match('/\/ip\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract Target Item ID from URL
     */
    private function extractTargetItemId(string $url): ?string
    {
        if (preg_match('/\/p\/([A-Z0-9-]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Fetch Amazon product details using Rainforest API
     */
    private function fetchAmazonProductDetails(Product $product): array
    {
        $apiKey = config('services.rainforest.api_key');
        $baseUrl = config('services.rainforest.base_url', 'https://api.rainforestapi.com/request');
        
        $amazonDomain = $product->domain ?: 'amazon.com';

        $response = Http::get($baseUrl, [
            'api_key' => $apiKey,
            'amazon_domain' => $amazonDomain,
            'asin' => $product->external_id,
            'type' => 'product'
        ]);
        
        if (!$response->successful()) {
            throw new Exception("Rainforest API request failed: " . $response->body());
        }
        
        $data = $response->json();
        
        if (!isset($data['product'])) {
            throw new Exception("Invalid response from Rainforest API");
        }
        
        $productData = $data['product'];
        
        // Extract price from buybox_winner (Rainforest API structure)
        $price = null;
        $currency = 'USD';
        if (isset($productData['buybox_winner']['price'])) {
            $priceData = $productData['buybox_winner']['price'];
            $price = $priceData['value'] ?? $this->parsePrice($priceData['raw'] ?? null);
            $currency = $priceData['currency'] ?? 'USD';
        } elseif (isset($productData['price'])) {
            $price = $this->parsePrice($productData['price']);
        }
        
        return [
            'title' => $productData['title'] ?? null,
            'description' => $productData['description'] ?? null,
            'price' => $price,
            'currency' => $currency,
            'image_url' => $this->extractImageUrl($productData['main_image'] ?? null),
            'features' => $productData['feature_bullets'] ?? null,
            'specifications' => $productData['specifications'] ?? null,
            'availability' => $productData['buybox_winner']['availability']['raw'] ?? $productData['availability'] ?? null,
            'rating' => $productData['rating'] ?? null,
            'review_count' => $productData['reviews_total'] ?? null,
            'brand' => $productData['brand'] ?? null,
            'model' => $productData['model'] ?? null,
            'raw_data' => $data,
        ];
    }
    
    /**
     * Fetch eBay product details (placeholder for future implementation)
     */
    private function fetchEbayProductDetails(Product $product): array
    {
        // TODO: Implement eBay API integration
        return [
            'title' => 'eBay Product - ' . $product->external_id,
            'description' => 'Product details will be fetched from eBay API',
            'raw_data' => ['provider' => 'ebay', 'item_id' => $product->external_id],
        ];
    }
    
    /**
     * Fetch Walmart product details (placeholder for future implementation)
     */
    private function fetchWalmartProductDetails(Product $product): array
    {
        // TODO: Implement Walmart API integration
        return [
            'title' => 'Walmart Product - ' . $product->external_id,
            'description' => 'Product details will be fetched from Walmart API',
            'raw_data' => ['provider' => 'walmart', 'item_id' => $product->external_id],
        ];
    }
    
    /**
     * Fetch Target product details (placeholder for future implementation)
     */
    private function fetchTargetProductDetails(Product $product): array
    {
        // TODO: Implement Target API integration
        return [
            'title' => 'Target Product - ' . $product->external_id,
            'description' => 'Product details will be fetched from Target API',
            'raw_data' => ['provider' => 'target', 'item_id' => $product->external_id],
        ];
    }
    
    /**
     * Extract image URL from main_image object
     */
    private function extractImageUrl($mainImage): ?string
    {
        if (is_string($mainImage)) {
            return $mainImage;
        }
        
        if (is_array($mainImage)) {
            // Try different possible keys for the image URL
            return $mainImage['link'] ?? $mainImage['url'] ?? $mainImage['src'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Parse price from various formats
     */
    private function parsePrice($price): ?float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }
        
        if (is_string($price)) {
            // Remove currency symbols and commas
            $cleaned = preg_replace('/[^\d.,]/', '', $price);
            $cleaned = str_replace(',', '', $cleaned);
            
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }
        
        return null;
    }
    
    /**
     * Bulk import products from URLs
     */
    public function bulkImport(array $urls, int $categoryId): array
    {
        $results = [];
        
        foreach ($urls as $url) {
            try {
                $product = $this->addProduct($url, $categoryId);
                $results[] = [
                    'url' => $url,
                    'success' => true,
                    'product_id' => $product->id,
                    'title' => $product->title,
                ];
            } catch (Exception $e) {
                $results[] = [
                    'url' => $url,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }
}
