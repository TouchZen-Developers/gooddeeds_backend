<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * User role constants
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_DONOR = 'donor';
    const ROLE_BENEFICIARY = 'beneficiary';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'role',
        'google_id',
        'apple_id',
        'social_provider',
        'social_avatar_url',
        'is_profile_complete',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_profile_complete' => 'boolean',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is donor
     */
    public function isDonor(): bool
    {
        return $this->role === self::ROLE_DONOR;
    }

    /**
     * Check if user is beneficiary
     */
    public function isBeneficiary(): bool
    {
        return $this->role === self::ROLE_BENEFICIARY;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get the beneficiary profile associated with the user
     */
    public function beneficiary(): HasOne
    {
        return $this->hasOne(Beneficiary::class);
    }

    /**
     * Get the desired items (products) for this beneficiary
     */
    public function desiredItems(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'beneficiary_desired_items', 'beneficiary_id', 'product_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    /**
     * Check if user signed up via social authentication
     */
    public function isSocialUser(): bool
    {
        return !is_null($this->social_provider);
    }

    /**
     * Check if user profile is complete
     */
    public function isProfileComplete(): bool
    {
        return $this->is_profile_complete;
    }

    /**
     * Mark user profile as complete
     */
    public function markProfileComplete(): void
    {
        $this->update(['is_profile_complete' => true]);
    }

    /**
     * Get social provider name
     */
    public function getSocialProvider(): ?string
    {
        return $this->social_provider;
    }
}
