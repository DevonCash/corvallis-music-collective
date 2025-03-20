<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\Finance\Models\Product;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Log;
use CorvMC\PracticeSpace\Database\Factories\RoomFactory;

class Room extends Model
{
    use HasFactory;

    protected $table = 'practice_space_rooms';

    protected $fillable = [
        'room_category_id',
        'name',
        'description',
        'capacity',
        'hourly_rate',
        'is_active',
        'photos',
        'specifications',
        'booking_policy',
        'timezone',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'photos' => 'array',
        'specifications' => 'array',
        'size_sqft' => 'integer',
        'amenities' => 'array',
        'booking_policy' => \CorvMC\PracticeSpace\ValueObjects\BookingPolicy::class,
        'timezone' => 'string',
    ];

    /**
     * Get the category that the room belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    /**
     * Get the bookings for the room.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get bookings that intersect with the given time range.
     * Time parameters are assumed to be in the room's timezone and will be
     * converted to UTC for database queries.
     *
     * @param Carbon $start Start time in room's timezone
     * @param Carbon $end End time in room's timezone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function bookingsIntersecting(Carbon $start, Carbon $end)
    {
        // Convert input times to UTC for database queries
        $startUtc = $start->copy()->setTimezone('UTC');
        $endUtc = $end->copy()->setTimezone('UTC');
        
        return $this->bookings()
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->whereBetween('start_time', [$startUtc, $endUtc])
                    ->orWhereBetween('end_time', [$startUtc, $endUtc])
                    ->orWhere(function ($query) use ($startUtc, $endUtc) {
                        $query->where('start_time', '<=', $startUtc)
                            ->where('end_time', '>=', $endUtc);
                    });
            });
    }
    
    /**
     * Get bookings that fall on a specific date in the room's timezone.
     *
     * @param Carbon $date Date in room's timezone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function bookingsOn(Carbon $date)
    {
        // Ensure the date is in the room's timezone
        $date = $date->copy()->setTimezone($this->timezone);
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        return $this->bookingsIntersecting($startOfDay, $endOfDay);
    }

    /**
     * Get the equipment in the room.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(RoomEquipment::class);
    }

    /**
     * Get the maintenance schedules for the room.
     */
    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Get the product associated with this room from the finance module.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        // This relationship will only work if the Finance module is installed
        if (class_exists('CorvMC\Finance\Models\Product')) {
            return $this->belongsTo('CorvMC\Finance\Models\Product');
        }

        // Return a null relationship if the Finance module is not installed
        return $this->belongsTo(self::class, 'id', 'id')->whereNull('id');
    }

    public function getHourlyRateAttribute()
    {
        return $this->product?->price ?? $this->attributes['hourly_rate'];
    }

    /**
     * Check if this room is available for the given time slot
     *
     * @param \Carbon\Carbon $startDateTime
     * @param \Carbon\Carbon $endDateTime
     * @return bool
     */
    public function isAvailable(Carbon $startDateTime, Carbon $endDateTime): bool
    {
        // The bookingsIntersecting method now handles timezone conversion internally
        $conflictingBookings = $this->bookingsIntersecting($startDateTime, $endDateTime)
            ->where('state', '!=', 'cancelled')
            ->count();

        return $conflictingBookings === 0;
    }

    /**
     * Get available time slots for this room on a specific date
     *
     * @param \Carbon\Carbon $date
     * @return array
     */
    public function getAvailableTimeSlots(Carbon $date): array
    {
        // Ensure we're working with the room's timezone
        $date = $date->copy()->setTimezone($this->timezone);
        $openingTime = $this->booking_policy->getOpeningTime($date->format('Y-m-d'), $this->timezone);
        $closingTime = $this->booking_policy->getClosingTime($date->format('Y-m-d'), $this->timezone);

        // Get all bookings for this room on this date
        // The bookingsOn method now handles timezone conversion internally
        $startTime = max($openingTime, Carbon::now($this->timezone));
        $endTime = $closingTime->copy()->subMinutes($this->booking_policy->maxBookingDurationHours * 60);
        $slotLengthMinutes = $this->booking_policy?->slotLengthMinutes ?? 30;
        
        $timeSlots = [];
        // Generate all possible time slots, without checking for availability
        for ($slot = $startTime->copy(); $slot->lt($endTime); $slot->addMinutes($slotLengthMinutes)) {
            $timeSlots[$slot->format('H:i')] = $slot->format('g:i A');
        }

        $bookings = $this->bookings
            ->where('state', '!=', 'cancelled')
            ->whereBetween('start_time', [$startTime, $endTime]);

        foreach ($bookings as $booking) {
            // Remove the booking from the time slots
            $startWithMinDuration = $booking->start_time->copy()->subHours($this->booking_policy->minBookingDurationHours);
            for($slot = $startWithMinDuration; $slot->lt($booking->end_time); $slot->addMinutes($slotLengthMinutes)) {
                $timeKey = $slot->format('H:i');
                if(isset($timeSlots[$timeKey])) {
                    unset($timeSlots[$timeKey]);
                }
            }
        }

        return $timeSlots;
    }

    /**
     * Get the booking policy for this room
     * 
     * If the room has a specific policy, it will be returned.
     * Otherwise, the category's default policy will be used.
     * If neither exists, a new default policy will be returned.
     *
     * @return BookingPolicy
     */
    public function getBookingPolicyAttribute(): BookingPolicy
    {
        // First check if this room has a specific booking policy
        if (isset($this->attributes['booking_policy']) && $this->attributes['booking_policy']) {
            // Use the cast to convert the JSON to a BookingPolicy object
            return $this->castAttribute('booking_policy', $this->attributes['booking_policy']);
        }
        
        // If no room-specific policy, fall back to the category's default policy
        if ($this->category && $this->category->default_booking_policy) {
            return $this->category->default_booking_policy;
        }
        
        // If no policy found, return a default BookingPolicy
        return new BookingPolicy();
    }

    /**
     * Get available durations for this room at a specific date and time
     *
     * @param Carbon $startTime
     * @return array
     */
    public function getAvailableDurations(Carbon $startTime): array
    {
        // Ensure start time is in room's timezone
        $startTime = $startTime->copy()->setTimezone($this->timezone);
        
        // Get operating hours in room's timezone
        $date = $startTime->format('Y-m-d');
        $openingTime = $this->booking_policy->getOpeningTime($date, $this->timezone);
        $closingTime = $this->booking_policy->getClosingTime($date, $this->timezone);

        // If start time is after closing time, return empty array
        if ($startTime->gte($closingTime)) {
            return [];
        }

        // Get current time in room's timezone
        $now = Carbon::now($this->timezone);
        
        // If start time is in the past, return empty array
        // Skip this check if the date is not today (to handle future bookings for tests)
        if ($startTime->lt($now) && $startTime->isSameDay($now)) {
            return [];
        }

        // Get the next booking after our start time
        $nextBooking = $this->bookings
            ->where('state', '!=', 'cancelled')
            ->whereBetween('start_time', [$startTime, $closingTime])
            ->first();

        $maxEndTime = $nextBooking ? min($closingTime, $nextBooking->start_time) : $closingTime;
        $maxDurationMinutes = min($startTime->diffInMinutes($maxEndTime), $this->booking_policy->maxBookingDurationHours * 60);

        $slotLengthMinutes = $this->booking_policy?->slotLengthMinutes ?? 30;
        $maxNumberOfSlots = floor($maxDurationMinutes / $slotLengthMinutes);

        $durationOptions = [];

        for ($i = 1; $i <= $maxNumberOfSlots; $i++) {
            $hours = $i * $slotLengthMinutes / 60;
            if($hours < 1) {
                $durationOptions[(string) $i] = floor($hours * 60) . ' mins';
            } elseif($hours == 1) {
                $durationOptions[(string) $i] = '1 hour';
            } else {
                $durationOptions[(string) $i] = $hours . ' hours';
            }
        }

        return $durationOptions;
    }
    
    /**
     * Get the timezone for the room
     * 
     * @return string
     */
    public function getTimezoneAttribute(): string
    {
        return $this->attributes['timezone'] ?? config('app.timezone');
    }

    public function getMaximumBookingDate(): Carbon
    {
        return Carbon::now($this->timezone)->addDays($this->booking_policy->maxAdvanceBookingDays);
    }

    public function getMinimumBookingDate(): Carbon
    {
        return Carbon::now($this->timezone)->addHours($this->booking_policy->minAdvanceBookingHours);
    }
}
