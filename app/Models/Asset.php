<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'locked_amount' => 'decimal:8',
        ];
    }

    /**
     * Get the user that owns the asset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include assets with available amount.
     */
    public function scopeAvailable($query)
    {
        return $query->whereColumn('amount', '>', 'locked_amount');
    }

    /**
     * Scope a query to only include assets with locked amount.
     */
    public function scopeLocked($query)
    {
        return $query->where('locked_amount', '>', 0);
    }

    /**
     * Get the available (unlocked) amount.
     */
    public function getAvailableAmountAttribute(): float
    {
        return max(0, $this->amount - $this->locked_amount);
    }
}
