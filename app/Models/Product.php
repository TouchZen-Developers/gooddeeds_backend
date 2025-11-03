<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    /**
     * Provider constants
     */
    const PROVIDER_AMAZON = 'amazon';
    const PROVIDER_EBAY = 'ebay';
    const PROVIDER_WALMART = 'walmart';
    const PROVIDER_TARGET = 'target';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'provider',
        'external_id',
        'domain',
        'title',
        'description',
        'price',
        'currency',
        'image_url',
        'features',
        'specifications',
        'availability',
        'rating',
        'review_count',
        'brand',
        'model',
        'category_id',
        'is_active',
        'is_featured',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'features' => 'array',
        'specifications' => 'array',
        'raw_data' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'raw_data',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the beneficiaries who need this product.
     */
    public function beneficiariesWhoNeed(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'beneficiary_desired_items', 'product_id', 'beneficiary_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Check if product is from Amazon.
     */
    public function isAmazon(): bool
    {
        return $this->provider === self::PROVIDER_AMAZON;
    }

    /**
     * Check if product is from eBay.
     */
    public function isEbay(): bool
    {
        return $this->provider === self::PROVIDER_EBAY;
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        if (!$this->price || !$this->currency) {
            return 'Price not available';
        }

        return $this->currency . ' ' . number_format($this->price, 2);
    }

    /**
     * Get product availability status.
     */
    public function getAvailabilityStatusAttribute(): string
    {
        return $this->availability ?? 'Unknown';
    }
}
