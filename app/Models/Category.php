<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'icon_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Get the formatted icon URL
     */
    public function getIconUrlAttribute($value)
    {
        return $value ?: null;
    }

    /**
     * Scope to get categories with icons
     */
    public function scopeWithIcons($query)
    {
        return $query->whereNotNull('icon_url');
    }

    /**
     * Scope to get categories without icons
     */
    public function scopeWithoutIcons($query)
    {
        return $query->whereNull('icon_url');
    }

    /**
     * Relationship: Category has many Products
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Accessor: Dynamic product count
     */
    public function getProductCountAttribute(): int
    {
        // If loaded with withCount('products as product_count'), prefer that
        if (array_key_exists('product_count', $this->attributes)) {
            return (int) $this->attributes['product_count'];
        }

        return $this->products()->count();
    }

    /**
     * Scope: eager load product count
     */
    public function scopeWithProductCount($query)
    {
        return $query->withCount('products as product_count');
    }
}
