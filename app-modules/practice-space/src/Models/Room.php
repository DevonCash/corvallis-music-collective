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

        // If no duration is specified, use the minimum booking duration from the policy
        $minDuration = $this->booking_policy->minBookingDurationHours;

        // Get current time in room's timezone
        $now = Carbon::now($this->timezone);
        
        // Check if the date is today
        $isToday = $now->isSameDay($date);

        // If booking is for today, adjust the opening time based on minimum advance booking hours
        if ($isToday) {
            // For today, use the current time plus minimum advance booking hours
            $minAdvanceTime = $now->copy()->addHours($this->booking_policy->minAdvanceBookingHours);
            
            // If minAdvanceTime is greater than opening time, use it instead
            if ($minAdvanceTime->greaterThan($openingTime)) {
                $openingTime = $minAdvanceTime;

                // Round up to the next half hour
                $minutes = (int) $openingTime->format('i');
                if ($minutes > 0 && $minutes < 30) {
                    $openingTime->setTime($openingTime->hour, 30);
                } else if ($minutes > 30) {
                    $openingTime->setTime($openingTime->hour + 1, 0);
                }
            }

            // If the adjusted opening time is after closing time, return no available slots
            if ($openingTime->gte($closingTime)) {
                return [];
            }
        }

        // Get all bookings for this room on this date
        // The bookingsOn method now handles timezone conversion internally
        $bookings = $this->bookingsOn($date->copy()->startOfDay())
            ->where('state', '!=', 'cancelled')
            ->get();

        // Generate all possible time slots
        $timeSlots = [];
        $currentSlot = $openingTime->copy();

        while ($currentSlot->lt($closingTime)) {
            $slotEnd = $currentSlot->copy()->addMinutes(30);
            $timeKey = $currentSlot->format('H:i');
            $displayTime = $currentSlot->format('g:i A');

            // Check if this slot is available
            $isAvailable = true;

            // Check if this slot would allow for a minimum duration booking
            $minDurationEnd = $currentSlot->copy()->addHours($minDuration);
            if ($minDurationEnd->gt($closingTime)) {
                $isAvailable = false;
            }

            // Check if this slot overlaps with any booking
            foreach ($bookings as $booking) {
                // A slot is unavailable if:
                // 1. The slot start time is within a booking
                // 2. The slot end time is within a booking
                // 3. The booking spans the entire slot
                // 4. The minimum duration would overlap with a booking
                
                // Using the booking times directly since they're already in room's timezone
                $bookingStart = $booking->start_time;
                $bookingEnd = $booking->end_time;
                
                if (
                    // Slot start is within booking
                    ($currentSlot->between($bookingStart, $bookingEnd, true)) ||
                    // Slot end is within booking
                    ($slotEnd->between($bookingStart, $bookingEnd, true)) ||
                    // Booking spans the entire slot
                    ($bookingStart->lte($currentSlot) && $bookingEnd->gte($slotEnd)) ||
                    // Minimum duration would overlap with booking
                    ($minDurationEnd->gt($bookingStart) && $currentSlot->lt($bookingEnd))
                ) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $timeSlots[$timeKey] = $displayTime;
            }

            $currentSlot = $slotEnd;
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
     * Get the maximum booking duration in hours
     *
     * @return float
     */
    public function getMaxBookingDuration(): float
    {
        return $this->booking_policy->maxBookingDurationHours;
    }

    /**
     * Get the minimum booking duration in hours
     *
     * @return float
     */
    public function getMinBookingDuration(): float
    {
        return $this->booking_policy->minBookingDurationHours;
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
        if ($startTime->lt($now)) {
            return [];
        }

        // Get the next booking after our start time
        $nextBooking = $this->bookings()
            ->where('state', '!=', 'cancelled')
            ->where(function ($query) use ($startTime) {
                // Convert start_time to UTC for database comparison
                $startTimeUtc = $startTime->copy()->setTimezone('UTC');
                $query->where('start_time', '>', $startTimeUtc);
            })
            ->orderBy('start_time')
            ->first();
    
        // Calculate maximum possible duration in hours
        if ($nextBooking) {
            // Convert next booking start time to room's timezone for comparison
            $nextBookingStart = $nextBooking->start_time->copy()->setTimezone($this->timezone);
            $maxPossibleDuration = $startTime->diffInMinutes($nextBookingStart) / 60;
        } else {
            $maxPossibleDuration = $startTime->diffInMinutes($closingTime) / 60;
        }

        // Respect the policy's max duration
        $maxPossibleDuration = min(
            $maxPossibleDuration,
            $this->booking_policy->maxBookingDurationHours
        );

        // Round down to nearest half hour to avoid partial slots
        $maxPossibleDuration = floor($maxPossibleDuration * 2) / 2;

        // Generate duration options
        return $this->generateDurationOptions($maxPossibleDuration, true);
    }

    /**
     * Generate duration options
     *
     * @param float $maxDuration Maximum possible duration based on time constraints
     * @param bool $includeHalfHour Whether to include half-hour increments
     * @return array
     */
    private function generateDurationOptions(float $maxDuration, bool $includeHalfHour = false): array
    {
        $options = [];
        $policy = $this->booking_policy;

        // Respect the policy's min and max durations
        $minDuration = $policy->minBookingDurationHours;

        // Determine the increment based on includeHalfHour flag
        $increment = $includeHalfHour ? 0.5 : 1.0;

        // Start from the minimum duration
        for ($duration = $minDuration; $duration <= $maxDuration; $duration += $increment) {
            // Format the duration label
            if ($duration < 1) {
                $options[(string)$duration] = (string) floor($duration * 60) . " mins";
            } elseif ($duration == 1) {
                $options[(string)$duration] = '1 hour';
            } else {
                $options[(string)$duration] = $duration . ' hours';
            }
        }

        return $options;
    }

    public function getMinimumBookingDate()
    {
        // Get the minimum advance booking hours from the room's policy
        $policy = $this->booking_policy;
        $leadTimeInDays = $policy->minAdvanceBookingHours / 24;

        // Allow bookings for today if the lead time is less than 1 day
        return $leadTimeInDays < 1 ? Carbon::now($this->timezone) : Carbon::now($this->timezone)->addDays(ceil($leadTimeInDays));
    }

    public function getMaximumBookingDate()
    {
        // Get the maximum advance booking days from the room's policy
        $policy = $this->booking_policy;
        $leadTimeInDays = $policy->maxAdvanceBookingDays;

        return Carbon::now($this->timezone)->addDays($leadTimeInDays);
    }

    /**
     * Get dates when this room is fully booked
     *
     * @param \Carbon\Carbon|null $startDate Start date for the range (defaults to today)
     * @param \Carbon\Carbon|null $endDate End date for the range (defaults to 3 months from start)
     * @return array Array of dates in Y-m-d format that are fully booked
     */
    public function getFullyBookedDates(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Default date range: today to 3 months from now
        $startDate = $startDate ?? Carbon::today($this->timezone);
        $endDate = $endDate ?? Carbon::today($this->timezone)->addMonths(3);

        // Get all bookings for this room within the date range
        $bookings = $this->bookingsIntersecting($startDate, $endDate)
            ->where('state', '!=', 'cancelled')
            ->get();

        // Group bookings by date
        $bookingsByDate = [];
        foreach ($bookings as $booking) {
            $date = $booking->start_time->format('Y-m-d');
            if (!isset($bookingsByDate[$date])) {
                $bookingsByDate[$date] = [];
            }
            $bookingsByDate[$date][] = $booking;
        }

        // Check which dates are fully booked
        $fullyBookedDates = [];

        // Check each day in the range
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');

            // If we already have bookings for this date, check if it's fully booked
            if (isset($bookingsByDate[$dateString])) {
                $dateBookings = $bookingsByDate[$dateString];

                // Get operating hours for this date
                $operatingHours = $this->getOperatingHours($dateString);
                $openingTime = $operatingHours['opening'];
                $closingTime = $operatingHours['closing'];

                // Check if the date is fully booked
                if ($this->isDateFullyBooked($dateBookings, $openingTime, $closingTime)) {
                    $fullyBookedDates[] = $dateString;
                }
            } else {
                // If there are no bookings, check if there are any available time slots
                $availableTimeSlots = $this->getAvailableTimeSlots(Carbon::parse($dateString));
                if (empty($availableTimeSlots)) {
                    $fullyBookedDates[] = $dateString;
                }
            }

            $currentDate->addDay();
        }

        return $fullyBookedDates;
    }

    /**
     * Check if a date is fully booked
     *
     * @param array $bookings
     * @param \Carbon\Carbon $openingTime
     * @param \Carbon\Carbon $closingTime
     * @return bool
     */
    private function isDateFullyBooked(array $bookings, Carbon $openingTime, Carbon $closingTime): bool
    {
        // Sort bookings by start time
        usort($bookings, function ($a, $b) {
            return $a->start_time <=> $b->start_time;
        });

        // Check if there are any available time slots
        $currentTime = $openingTime->copy();

        foreach ($bookings as $booking) {
            // If there's a gap between current time and booking start time, the date is not fully booked
            if ($currentTime < $booking->start_time) {
                return false;
            }

            // Move current time to the end of this booking
            if ($booking->end_time > $currentTime) {
                $currentTime = $booking->end_time->copy();
            }
        }

        // If current time is before closing time, there's still available time
        return $currentTime >= $closingTime;
    }

    public function getDisabledBookingDates() {}

    /**
     * Get operating hours for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return array Array with 'opening' and 'closing' Carbon instances
     */
    public function getOperatingHours(string $date): array
    {
        return $this->booking_policy->getOperatingHours($date, $this->timezone);
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
}
