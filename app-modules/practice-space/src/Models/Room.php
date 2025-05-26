<?php

namespace CorvMC\PracticeSpace\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $table = "practice_space_rooms";

    protected $fillable = [
        "room_category_id",
        "name",
        "description",
        "capacity",
        "hourly_rate",
        "is_active",
        "photos",
        "specifications",
        "booking_policy",
    ];

    protected $casts = [
        "capacity" => "integer",
        "hourly_rate" => "decimal:2",
        "is_active" => "boolean",
        "photos" => "array",
        "specifications" => "array",
        "size_sqft" => "integer",
        "amenities" => "array",
        "booking_policy" => BookingPolicy::class,
    ];

    /**
     * Get the category that the room belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, "room_category_id");
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
     */
    public function bookingsIntersecting(Carbon $start, Carbon $end)
    {
        return $this->bookings()->where(function ($query) use ($start, $end) {
            $query
                ->whereBetween("start_time", [$start, $end])
                ->orWhereBetween("end_time", [$start, $end])
                ->orWhere(function ($query) use ($start, $end) {
                    $query
                        ->where("start_time", "<=", $start)
                        ->where("end_time", ">=", $end);
                });
        });
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
        if (class_exists("CorvMC\Finance\Models\Product")) {
            return $this->belongsTo("CorvMC\Finance\Models\Product");
        }

        // Return a null relationship if the Finance module is not installed
        return $this->belongsTo(self::class, "id", "id")->whereNull("id");
    }

    /**
     * Check if this room is available for the given time slot
     *
     * @param \Carbon\Carbon $startDateTime
     * @param \Carbon\Carbon $endDateTime
     * @return bool
     */
    public function isAvailable(
        Carbon $startDateTime,
        Carbon $endDateTime
    ): bool {
        // The bookingsIntersecting method now handles timezone conversion internally
        $conflictingBookings = $this->bookingsIntersecting(
            $startDateTime,
            $endDateTime
        )
            ->where("state", "!=", "cancelled")
            ->count();

        return $conflictingBookings === 0;
    }

    /**
     * Get available time slots for this room on a specific date
     *
     * @param \Carbon\CarbonImmutable $date
     * @return array
     */
    public function getAvailableTimeSlots(CarbonImmutable $start): array
    {
        $openingTime = $this->booking_policy->getOpeningTime($start);
        $options = collect($this->getValidSlots($start))
            ->mapWithKeys(function ($item, $key) use ($openingTime, $start) {
                if ($item !== true) return [null => null];
                $time = $openingTime->copy()->addMinutes($key * 30);
                if ($time->isBefore($start)) return [null => null];
                return [
                    $time->format('H:i') => $time->format('g:i a')
                ];
            })->filter(fn($val) => $val !== null)->toArray();
        return $options;
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
        if (
            isset($this->attributes["booking_policy"]) &&
            $this->attributes["booking_policy"]
        ) {
            // Use the cast to convert the JSON to a BookingPolicy object
            return $this->castAttribute(
                "booking_policy",
                $this->attributes["booking_policy"]
            );
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
        $closingTime = $startTime
            ->copy()
            ->startOfDay()
            ->modify($this->booking_policy->closingTime);

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
            ->where("start_time", ">", $startTime)
            ->where("start_time", "<=", $closingTime)
            ->whereNotIn("status", ["cancelled"])
            ->orderBy("start_time")
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
        if (
            $maxDurationHours < $this->booking_policy->minBookingDurationHours
        ) {
            return [];
        }

        $durations = [];
        $currentDuration = $this->booking_policy->minBookingDurationHours;

        while ($currentDuration <= $maxDurationHours) {
            // Format key as string, ensuring whole numbers don't have decimal point
            $key = number_format($currentDuration, 1);

            // Format label with proper pluralization
            $label = $currentDuration == 1 ? "1 hour" : $key . " hours";

            $durations[$key] = $label;
            $currentDuration += 0.5;
        }

        return $durations;
    }

    public function getValidSlots(CarbonImmutable $day): array
    {
        $policy = $this->booking_policy;
        $openingTime = $policy->getOpeningTime($day);
        $closingTime = $policy->getClosingTime($day);
        $bookings = $this->bookings->whereBetween('start_time', [$openingTime, $closingTime]);
        $slots = [];

        for (
            $mins = 0;
            $mins < $openingTime->diffInMinutes($closingTime);
            $mins += 30
        ) {
            $time = $openingTime->addMinutes($mins);
            if ($time->isPast()) {
                $slots[] = "time_in_past";
            } elseif (
                $time->lt(
                    Carbon::now()->addHours($policy->minAdvanceBookingHours)
                )
            ) {
                $slots[] = "advance_notice";
            } elseif (
                $time
                ->addHours($policy->minBookingDurationHours)
                ->gt($closingTime)
            ) {
                $slots[] = "too_close_to_close";
            } elseif (
                $bookings->first(
                    fn($b) => $time->between(
                        $b->start_time
                            ->subHours($policy->minBookingDurationHours)
                            ->addMinutes(1),
                        $b->end_time->subMinutes(1) // Allow next-hour bookings
                    )
                )
            ) {
                $slots[] = "slot_booked";
            } else {
                $slots[] = true;
            }
        }

        return $slots;
    }

    public function validateBooking(Booking $booking): bool
    {
        if (!$this->booking_policy) return true;
        return $this->booking_policy->validateBooking($booking);
    }

    public function getMaximumBookingDate(): CarbonImmutable
    {
        return CarbonImmutable::now()->addDays($this->booking_policy->maxAdvanceBookingDays);
    }

    public function getMinimumBookingDate(): CarbonImmutable
    {
        return CarbonImmutable::now()->addHours($this->booking_policy->minAdvanceBookingHours);
    }
}
