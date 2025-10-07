<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Otp extends Model
{
    use HasFactory;

    protected $table = 'otps';

    /** @var array<int, string> */
    protected $fillable = [
        'email',
        'otp',
        'context',
        'metadata',
        'expires_at',
        'is_used',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'metadata' => 'array',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }
}
