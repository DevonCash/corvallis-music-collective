<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\Finance\Models\Product;
use CorvMC\PracticeSpace\Casts\BookingPolicyCast;
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
        'booking_policy' => BookingPolicyCast::class,
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
        $conflictingBookings = $this->bookingsIntersecting($startDateTime, $endDateTime)
            ->where('state', '!=', 'cancelled')
            ->count();

        return $conflictingBookings === 0;
    }

    /**
     * Get available time slots for this room on a specific date
     *
     * @param \Carbon\Carbon $date
     * @param float|null $duration Duration in hours (optional)
     * @return array
     */
    public function getAvailableTimeSlots(Carbon $date): array
    {
        // Get operating hours
        $openingTime = $date->copy()->setTimeFromTimeString($this->booking_policy->openingTime);
        $closingTime = $date->copy()->setTimeFromTimeString($this->booking_policy->closingTime);

        // If no duration is specified, use the minimum booking duration from the policy
        $minDuration = $this->booking_policy->minBookingDurationHours;

        // Check if the date is today and apply minimum advance booking hours
        $now = Carbon::now();
        $isToday = $now->isSameDay($date);

        // If booking is for today, adjust the opening time based on minimum advance booking hours
        if ($isToday && $this->booking_policy->minAdvanceBookingHours > 0) {
            $minAdvanceTime = $now->copy()->addHours($this->booking_policy->minAdvanceBookingHours);

            // If the minimum advance time is after the opening time, use it instead
            if ($minAdvanceTime->gt($openingTime)) {
                $openingTime = $minAdvanceTime;

                // Round up to the next half hour if needed
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
        $bookings = $this->bookingsOn($date)
            ->where('state', '!=', 'cancelled')
            ->get();

        // Generate all possible time slots
        $timeSlots = [];

        for ($t = $openingTime->copy(); $t < $closingTime; $t->addMinutes(30)) {
            $timeKey = $t->format('H:i');
            $displayTime = $t->format('g:i A');

            // Check if this time slot is available (not within any existing booking)
            if ($bookings->contains(
                fn($b) => $t->between(
                    $b->start_time,
                    $b->end_time->subMinute()
                )
            )) {
                continue;
            }

            // Check if there's enough time for at least the minimum booking duration
            $end = $t->copy()->addMinutes($minDuration * 60);

            // Check if the end time exceeds closing time
            if ($end > $closingTime) {
                continue;
            }

            // Find any booking that would conflict with this duration
            $conflictingBooking = $bookings->first(function ($booking) use ($t, $end) {
                // Check if booking starts during our time slot
                $bookingStartsDuringSlot = $booking->start_time->between($t, $end);

                // Check if our time slot starts during booking
                $slotStartsDuringBooking = $t->between(
                    $booking->start_time,
                    $booking->end_time
                );

                return $bookingStartsDuringSlot || $slotStartsDuringBooking;
            });

            if (!$conflictingBooking) {
                $timeSlots[$timeKey] = $displayTime;
            }
        }

        return $timeSlots;
    }

    /**
     * Get the booking policy for this room
     * 
     * If the room has a custom policy, it will be returned.
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
     * Set the booking policy for this room
     * 
     * @param BookingPolicy|array|null $value
     * @return void
     */
    public function setBookingPolicyAttribute($value): void
    {
        // If null is provided, reset to use the category default
        if ($value === null) {
            $this->attributes['booking_policy'] = null;
            return;
        }
        
        // If an array is provided, ensure it uses snake_case keys
        if (is_array($value)) {
            // The BookingPolicy::fromArray method expects snake_case keys
            // If you're using this method, make sure your array keys are in snake_case format
            // e.g., 'opening_time' instead of 'openingTime'
            $value = BookingPolicy::fromArray($value);
        }
        
        // Ensure the value is a BookingPolicy instance
        if (!$value instanceof BookingPolicy) {
            throw new \InvalidArgumentException('The booking policy must be a BookingPolicy instance, an array, or null.');
        }
        
        // Store the policy as JSON
        $this->attributes['booking_policy'] = json_encode($value);
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
     * @param Carbon $date
     * @param string $time Start time parameter
     * @param bool $includeHalfHour
     * @return array
     */
    public function getAvailableDurations(Carbon $startTime): array
    {
        // Get operating hours
        $openingTime = $startTime->copy()->setTimeFromTimeString($this->booking_policy->openingTime);
        $closingTime = $startTime->copy()->setTimeFromTimeString($this->booking_policy->closingTime);

        // Get all bookings for this room on this date
        $bookings = $this->bookingsOn($startTime)
            ->where('state', '!=', 'cancelled')
            ->get();

        // If start time is after closing time, return empty array
        if ($startTime >= $closingTime) {
            return [];
        }

        // Check if the start time is within any existing booking
        $isStartTimeBooked = $bookings->contains(function ($booking) use ($startTime) {
            return $startTime->between(
                $booking->start_time,
                $booking->end_time->subMinute()
            );
        });

        if ($isStartTimeBooked) {
            return []; // Start time is already booked
        }

        // Find the next booking that starts after this time
        $nextBooking = $bookings->first(function ($booking) use ($startTime) {
            return $booking->start_time > $startTime;
        });

        // Calculate maximum possible duration in hours
        if ($nextBooking) {
            $maxPossibleDuration = $startTime->diffInMinutes($nextBooking->start_time) / 60;
        } else {
            $maxPossibleDuration = $startTime->diffInMinutes($closingTime) / 60;
        }

        $maxPossibleDuration = min($maxPossibleDuration, $this->booking_policy->maxBookingDurationHours);


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

        // Start from the minimum duration
        for ($duration = $minDuration; $duration <= $maxDuration; $duration += 0.5) {
            // Format the duration label
            if ($duration < 1) {
                $options[$duration] = (string) floor($duration * 60) . " mins";
            } elseif ($duration == 1) {
                $options[$duration] = '1 hour';
            } else {
                $options[$duration] = $duration . ' hours';
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
        return $leadTimeInDays < 1 ? now() : now()->addDays(ceil($leadTimeInDays));
    }

    public function getMaximumBookingDate()
    {
        // Get the maximum advance booking days from the room's policy
        $policy = $this->booking_policy;
        $leadTimeInDays = $policy->maxAdvanceBookingDays;

        return now()->addDays($leadTimeInDays);
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
        $startDate = $startDate ?? Carbon::today();
        $endDate = $endDate ?? Carbon::today()->addMonths(3);

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
    private function getOperatingHours(string $date): array
    {
        $dateObj = Carbon::parse($date);
        $policy = $this->booking_policy;

        // Set opening and closing times for this date
        $openingTime = $dateObj->copy()->setTimeFromTimeString($policy->openingTime);
        $closingTime = $dateObj->copy()->setTimeFromTimeString($policy->closingTime);

        return [
            'opening' => $openingTime,
            'closing' => $closingTime
        ];
    }
}
