<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'status',
        'processed_at',
        'family_size',
        'address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'affected_event',
        'statement',
        'family_photo_url',
        'identity_proof',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns the beneficiary profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include pending beneficiaries
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include approved beneficiaries
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include rejected beneficiaries
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if beneficiary is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if beneficiary is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if beneficiary is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve the beneficiary
     */
    public function approve(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Reject the beneficiary
     */
    public function reject(): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope a query to only include beneficiaries near a location
     * Uses Haversine formula to calculate distance in miles
     */
    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusMiles = 50): Builder
    {
        // Haversine formula to calculate distance in miles
        // 3959 is Earth's radius in miles
        // Use whereRaw with the full calculation instead of HAVING with alias
        return $query->selectRaw("
                *,
                (
                    3959 * acos(
                        cos(radians(?))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(latitude))
                    )
                ) AS distance_miles
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("
                (
                    3959 * acos(
                        cos(radians(?))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(latitude))
                    )
                ) <= ?
            ", [$latitude, $longitude, $latitude, $radiusMiles])
            ->orderByRaw("
                (
                    3959 * acos(
                        cos(radians(?))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(latitude))
                    )
                ) ASC
            ", [$latitude, $longitude, $latitude]);
    }

    /**
     * Get formatted location string (City, State)
     */
    public function getLocationStringAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state]);
        return implode(', ', $parts) ?: 'Location not specified';
    }

    /**
     * Check if beneficiary has location data
     */
    public function hasLocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}
