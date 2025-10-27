<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectedEvent extends Model
{
    protected $fillable = [
        'name',
        'image_url',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Scope to get only active events
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only featured events
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by featured first, then by name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_featured', 'desc')->orderBy('name');
    }

    /**
     * Get the beneficiaries associated with this affected event
     */
    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class, 'affected_event', 'name');
    }
}
