<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    protected $fillable = [
        'user_id',
        'family_size',
        'address',
        'city',
        'state',
        'zip_code',
        'affected_event',
        'statement',
        'family_photo_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
