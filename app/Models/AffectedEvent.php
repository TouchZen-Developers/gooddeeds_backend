<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectedEvent extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active events
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Get the beneficiaries associated with this affected event
     */
    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class, 'affected_event', 'name');
    }
}
