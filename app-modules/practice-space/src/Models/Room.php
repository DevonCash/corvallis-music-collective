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
     *
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function bookingsIntersecting(Carbon $start, Carbon $end)
    {
        return $this->bookings()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($query) use ($start, $end) {
                        $query->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            });
    }
    
    /**
     * Get bookings that fall on a specific date.
     *
     * @param Carbon $date Date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function bookingsOn(Carbon $date)
    {
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
        $openingTime = $this->booking_policy->getOpeningTime($date->format('Y-m-d'));
        $closingTime = $this->booking_policy->getClosingTime($date->format('Y-m-d'));

        // Get all bookings for this room on this date
        $startTime = max($openingTime, now());
        $endTime = $closingTime;
        $slotLengthMinutes = 30; // Fixed 30-minute slots
        
        $timeSlots = [];
        // Generate all possible time slots
        for ($slot = $openingTime->copy(); $slot->lt($endTime); $slot->addMinutes($slotLengthMinutes)) {
            // Skip slots that are in the past
            if ($slot->lt(now()) && $slot->isSameDay(now())) {
                continue;
            }
            $timeSlots[$slot->format('H:i')] = $slot->format('g:i A');
        }

        // Get all non-cancelled bookings for this date
        $bookings = $this->bookings()
            ->where('state', '!=', 'cancelled')
            ->where(function ($query) use ($date) {
                $query->whereDate('start_time', $date)
                    ->orWhereDate('end_time', $date);
            })
            ->get();

        // Remove booked slots
        foreach ($bookings as $booking) {
            $bookingStart = $booking->start_time;
            $bookingEnd = $booking->end_time;

            // If booking spans multiple days, adjust start/end times
            if ($bookingStart->format('Y-m-d') < $date->format('Y-m-d')) {
                $bookingStart = $openingTime;
            }
            if ($bookingEnd->format('Y-m-d') > $date->format('Y-m-d')) {
                $bookingEnd = $closingTime;
            }

            // Remove slots that overlap with this booking
            for ($slot = $bookingStart->copy(); 
                 $slot->lte($bookingEnd); 
                 $slot->addMinutes($slotLengthMinutes)) {
                unset($timeSlots[$slot->format('H:i')]);
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
        $closingTime = $startTime->copy()->startOfDay()->modify($this->booking_policy->closingTime);

        // Return empty array if start time is after closing time
        if ($startTime->gt($closingTime)) {
            return [];
        }

        // For past dates, only allow if it's for future bookings
        if ($startTime->lt(now()) && !$startTime->isToday()) {
            return [];
        }

        // Get the next booking after this start time
        $nextBooking = $this->bookings()
            ->where('start_time', '>', $startTime)
            ->where('start_time', '<=', $closingTime)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->first();

        // Calculate maximum end time based on closing time and next booking
        $maxEndTime = $nextBooking 
            ? min($closingTime, $nextBooking->start_time)
            : $closingTime;

        // Calculate maximum duration in hours
        $maxDurationHours = $startTime->copy()->diffInMinutes($maxEndTime) / 60;
        $maxDurationHours = min(
            $maxDurationHours,
            $this->booking_policy->maxBookingDurationHours
        );

        // If max duration is less than minimum booking duration, return empty array
        if ($maxDurationHours < $this->booking_policy->minBookingDurationHours) {
            return [];
        }

        $durations = [];
        $currentDuration = $this->booking_policy->minBookingDurationHours;

        while ($currentDuration <= $maxDurationHours) {
            // Format key as string, ensuring whole numbers don't have decimal point
            $key = (floor($currentDuration) == $currentDuration) 
                ? (string)$currentDuration 
                : number_format($currentDuration, 1);

            // Format label with proper pluralization
            $label = $currentDuration == 1 
                ? '1 hour' 
                : $key . ' hours';

            $durations[$key] = $label;
            $currentDuration += 0.5;
        }

        return $durations;
    }

    public function getMaximumBookingDate(): Carbon
    {
        return now()->addDays($this->booking_policy->maxAdvanceBookingDays);
    }

    public function getMinimumBookingDate(): Carbon
    {
        return now()->addHours($this->booking_policy->minAdvanceBookingHours);
    }

    public function getOperatingHours(string $date): array
    {
        $openingTime = $this->booking_policy->getOpeningTime($date);
        $closingTime = $this->booking_policy->getClosingTime($date);

        return [
            'opening' => $openingTime->format('Y-m-d H:i:s'),
            'closing' => $closingTime->format('Y-m-d H:i:s'),
        ];
    }

    public function getFullyBookedDates(Carbon $startDate, Carbon $endDate): array
    {
        $fullyBookedDates = [];
        $currentDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->endOfDay();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $openingTime = $this->booking_policy->getOpeningTime($dateStr);
            $closingTime = $this->booking_policy->getClosingTime($dateStr);

            // Get all bookings for this date
            $bookings = $this->bookingsOn($currentDate)
                ->where('state', '!=', 'cancelled')
                ->get();

            // Check if there are any available time slots
            $availableSlots = $this->getAvailableTimeSlots($currentDate);
            if (empty($availableSlots)) {
                $fullyBookedDates[] = $dateStr;
            }

            $currentDate->addDay();
        }

        return $fullyBookedDates;
    }
}
