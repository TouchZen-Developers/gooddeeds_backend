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
        'total_items',
        'icon_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_items' => 'integer',
    ];

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
}
