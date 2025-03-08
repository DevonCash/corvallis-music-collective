<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingPolicy extends Model
{
    use HasFactory;

    protected $table = 'practice_space_booking_policies';

    protected $fillable = [
        'room_category_id',
        'name',
        'description',
        'max_booking_duration_hours',
        'min_booking_duration_hours',
        'max_advance_booking_days',
        'min_advance_booking_hours',
        'cancellation_policy',
        'cancellation_hours',
        'max_bookings_per_week',
        'is_active',
    ];

    protected $casts = [
        'max_booking_duration_hours' => 'float',
        'min_booking_duration_hours' => 'float',
        'max_advance_booking_days' => 'integer',
        'min_advance_booking_hours' => 'float',
        'cancellation_hours' => 'integer',
        'max_bookings_per_week' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the room category that this policy applies to.
     */
    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class);
    }

    /**
     * Get the policy overrides for specific users.
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(BookingPolicyOverride::class);
    }

    /**
     * Create a policy override for a specific user.
     */
    public function createOverrideForUser(int $userId, array $overrides): BookingPolicyOverride
    {
        return $this->overrides()->create([
            'user_id' => $userId,
            'overrides' => $overrides,
        ]);
    }

    /**
     * Get the policy override for a specific user.
     */
    public function getOverrideForUser(int $userId): ?array
    {
        $override = $this->overrides()->where('user_id', $userId)->first();
        
        return $override ? $override->overrides : null;
    }

    /**
     * Check if a user has a policy override.
     */
    public function hasOverrideForUser(int $userId): bool
    {
        return $this->overrides()->where('user_id', $userId)->exists();
    }

    /**
     * Remove a policy override for a specific user.
     */
    public function removeOverrideForUser(int $userId): bool
    {
        return (bool) $this->overrides()->where('user_id', $userId)->delete();
    }
} 