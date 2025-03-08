<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPolicyOverride extends Model
{
    use HasFactory;

    protected $table = 'practice_space_booking_policy_overrides';

    protected $fillable = [
        'booking_policy_id',
        'user_id',
        'overrides',
        'notes',
        'expires_at',
    ];

    protected $casts = [
        'overrides' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the booking policy that this override applies to.
     */
    public function bookingPolicy(): BelongsTo
    {
        return $this->belongsTo(BookingPolicy::class);
    }

    /**
     * Get the user that this override applies to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the override has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get a specific override value.
     */
    public function getOverrideValue(string $key, $default = null)
    {
        return $this->overrides[$key] ?? $default;
    }

    /**
     * Set a specific override value.
     */
    public function setOverrideValue(string $key, $value): self
    {
        $overrides = $this->overrides;
        $overrides[$key] = $value;
        $this->overrides = $overrides;
        
        return $this;
    }

    /**
     * Remove a specific override value.
     */
    public function removeOverrideValue(string $key): self
    {
        $overrides = $this->overrides;
        unset($overrides[$key]);
        $this->overrides = $overrides;
        
        return $this;
    }
} 