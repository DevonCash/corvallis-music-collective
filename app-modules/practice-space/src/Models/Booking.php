<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use CorvMC\PracticeSpace\Database\Factories\BookingFactory;
// Temporarily commented out for testing
// Import from Finance module instead of Payments module
use CorvMC\PracticeSpace\Models\States\BookingState;
use CorvMC\StateManagement\Casts\State;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use CorvMC\Finance\Concerns\HasPayments;
use CorvMC\PracticeSpace\Traits\LogsNotifications;
use CorvMC\PracticeSpace\Traits\HasRecurringBookings;
use Illuminate\Support\Facades\Log;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use CorvMC\PracticeSpace\Contracts\CalendarEvent;
use DateTime;
use DateTimeImmutable;
use Illuminate\Support\Facades\Auth;

class Booking extends Model implements CalendarEvent
{
    // Temporarily commented out HasPayments for testing
    use LogsActivity,
        HasFactory,
        SoftDeletes,
        HasPayments,
        LogsNotifications,
        HasRecurringBookings;

    protected $table = "practice_space_bookings";

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        "start_time" => "datetime",
        "end_time" => "datetime",
        "is_recurring" => "boolean",
        "is_recurring_parent" => "boolean",
        "check_in_time" => "datetime",
        "check_out_time" => "datetime",
        "total_price" => "decimal:2",
        "state" => State::class . ":" . BookingState::class,
        "confirmation_requested_at" => "datetime",
        "confirmation_deadline" => "datetime",
        "confirmed_at" => "datetime",
        "cancelled_at" => "datetime",
        "payment_completed" => "boolean",
        "recurrence_end_date" => "datetime",
    ];

    /**
     * The attributes that should be treated as dates.
     *
     * @var array
     */
    protected $dates = [
        "start_time",
        "end_time",
        "check_in_time",
        "check_out_time",
        "confirmation_requested_at",
        "confirmation_deadline",
        "confirmed_at",
        "cancelled_at",
        "recurrence_end_date",
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    protected $fillable = [
        "room_id",
        "user_id",
        "start_time",
        "end_time",
        "status",
        "notes",
        "is_recurring",
        "is_recurring_parent",
        "recurring_pattern",
        "rrule_string",
        "recurrence_end_date",
        "recurring_booking_id",
        "check_in_time",
        "check_out_time",
        "total_price",
        "payment_status",
        "state",
        "confirmation_requested_at",
        "confirmation_deadline",
        "confirmed_at",
        "cancelled_at",
        "cancellation_reason",
        "no_show_notes",
        "payment_completed",
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            // Calculate total price if not set and room exists
            if (empty($booking->total_price) && $booking->room_id) {
                $room = Room::find($booking->room_id);
                if ($room && $booking->start_time && $booking->end_time) {
                    $hours = $booking->start_time->diffInHours(
                        $booking->end_time
                    );
                    $booking->total_price = $room->hourly_rate * $hours;
                }
            }

            // Apply membership discount if applicable
            if ($booking->user_id) {
                $booking->applyMembershipDiscount();
            }

            // Set confirmation window based on booking policy
            $booking->setConfirmationWindow();
        });
    }

    /**
     * Get the room that was booked.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user that made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the equipment requests for this booking.
     */
    public function equipmentRequests(): HasMany
    {
        return $this->hasMany(EquipmentRequest::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return BookingFactory::new();
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["status", "start_time", "end_time", "notes", "state"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName("booking")
            ->setDescriptionForEvent(function (string $eventName) {
                return "Booking was {$eventName}";
            });
    }

    /**
     * Get the status history for this booking.
     */
    public function getStatusHistory()
    {
        return $this->activities()
            ->where("log_name", "booking")
            ->where(function ($query) {
                $query->where("event", "created")->orWhere(function ($q) {
                    $q->where("event", "updated")->where(function ($q2) {
                        $q2->whereJsonContains("properties->attributes", [
                            "status",
                        ])->orWhereJsonContains("properties->attributes", [
                            "state",
                        ]);
                    });
                });
            })
            ->orderBy("created_at");
    }

    /**
     * Get the booking policy for this booking.
     */
    public function getBookingPolicy()
    {
        return $this->room->booking_policy ??
            new \CorvMC\PracticeSpace\ValueObjects\BookingPolicy();
    }

    /**
     * Get the duration of the booking in hours.
     */
    public function getDurationInHours(): float
    {
        return $this->start_time->diffInMinutes($this->end_time) / 60;
    }

    /**
     * Validate the booking against the applicable booking policy.
     */
    public function validateAgainstPolicy(): bool
    {
        $policy = $this->getBookingPolicy();

        if (!$policy) {
            return true; // No policy to validate against
        }

        // Check for user-specific policy override
        $override = $policy->getOverrideForUser($this->user_id);

        // Validate duration
        if (!$this->validateDuration($policy, $override)) {
            return false;
        }

        // Validate advance notice
        if (!$this->validateAdvanceNotice($policy, $override)) {
            return false;
        }

        // Validate weekly booking limit
        if (!$this->validateWeeklyLimit($policy, $override)) {
            return false;
        }

        return true;
    }

    /**
     * Validate the booking duration against the policy.
     */
    protected function validateDuration($policy, $override = null): bool
    {
        $duration = $this->getDurationInHours();

        $maxDuration =
            $override && isset($override["max_booking_duration_hours"])
                ? $override["max_booking_duration_hours"]
                : $policy->maxBookingDurationHours;

        $minDuration =
            $override && isset($override["min_booking_duration_hours"])
                ? $override["min_booking_duration_hours"]
                : $policy->minBookingDurationHours;

        return $duration <= $maxDuration && $duration >= $minDuration;
    }

    /**
     * Validate the booking advance notice against the policy.
     */
    protected function validateAdvanceNotice($policy, $override = null): bool
    {
        // Get current time in UTC
        $now = Carbon::now()->setTimezone("UTC");

        // Convert booking start time to UTC for comparison
        $startTimeUtc = $this->start_time->copy()->setTimezone("UTC");

        $hoursUntilBooking = $now->diffInMinutes($startTimeUtc, false) / 60;
        $daysUntilBooking = $now->diffInDays($startTimeUtc, false);

        $minHours =
            $override && isset($override["min_advance_booking_hours"])
                ? $override["min_advance_booking_hours"]
                : $policy->minAdvanceBookingHours;

        $maxDays =
            $override && isset($override["max_advance_booking_days"])
                ? $override["max_advance_booking_days"]
                : $policy->maxAdvanceBookingDays;

        return $hoursUntilBooking >= $minHours && $daysUntilBooking <= $maxDays;
    }

    /**
     * Validate the booking against the weekly limit in the policy.
     */
    protected function validateWeeklyLimit($policy, $override = null): bool
    {
        $maxBookingsPerWeek =
            $override && isset($override["max_bookings_per_week"])
                ? $override["max_bookings_per_week"]
                : $policy->maxBookingsPerWeek;

        // Get the start and end of the current week
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        // Count bookings by this user in the current week
        $bookingsThisWeek = self::where("user_id", $this->user_id)
            ->where("id", "!=", $this->id) // Exclude this booking if it's already saved
            ->whereBetween("start_time", [$weekStart, $weekEnd])
            ->count();

        // Check if adding this booking would exceed the weekly limit
        return $bookingsThisWeek + 1 <= $maxBookingsPerWeek;
    }

    /**
     * Check if the booking can be cancelled with a refund.
     */
    public function canCancelWithRefund(): bool
    {
        $policy = $this->getBookingPolicy();

        if (!$policy) {
            return true; // No policy, so allow cancellation with refund
        }

        $now = Carbon::now();
        $hoursUntilBooking = $now->diffInMinutes($this->start_time, false) / 60;

        return $hoursUntilBooking >= $policy->cancellationHours;
    }

    /**
     * Calculate the total price for this booking in cents
     *
     * @return int
     */
    public function calculateTotalPriceInCents(): int
    {
        if ($this->total_price) {
            return (int) round($this->total_price * 100);
        }

        if (!$this->room) {
            return 0;
        }

        $hours = $this->getDurationInHours();
        $hourlyRateInCents = (int) round($this->room->hourly_rate * 100);

        // Calculate total in cents to avoid floating point issues
        return (int) round($hours * $hourlyRateInCents);
    }

    /**
     * Calculate the total price for this booking
     *
     * @return float
     */
    public function calculateTotalPrice(): float
    {
        return $this->calculateTotalPriceInCents() / 100;
    }

    /**
     * Apply a discount to the booking's total price
     *
     * @param float $discountPercent Percentage discount (0-100)
     * @param string|null $reason Reason for the discount
     * @return self
     */
    public function applyDiscount(
        float $discountPercent,
        string $reason = null
    ): self {
        if ($discountPercent <= 0 || $discountPercent > 100) {
            return $this;
        }

        // Calculate in cents to avoid floating point issues
        $originalPriceInCents = $this->calculateTotalPriceInCents();
        $discountAmountInCents = (int) round(
            $originalPriceInCents * ($discountPercent / 100)
        );
        $discountedPriceInCents =
            $originalPriceInCents - $discountAmountInCents;

        // Convert back to dollars for storage
        $discountedPrice = $discountedPriceInCents / 100;

        $this->update([
            "total_price" => $discountedPrice,
            "notes" =>
                $this->notes .
                "\nDiscount applied: {$discountPercent}% " .
                ($reason ? "($reason)" : ""),
        ]);

        return $this;
    }

    public function getDurationAttribute(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    /**
     * Apply discount based on user's membership tier
     *
     * @param \App\Models\User|null $userOverride Optional user object to use instead of looking up by ID
     * @return self
     */
    public function applyMembershipDiscount(?User $userOverride = null): self
    {
        // Skip if no user or no total price
        if (!$this->user_id || empty($this->total_price)) {
            return $this;
        }

        try {
            $user = $userOverride ?? User::find($this->user_id);

            if (!$user) {
                return $this;
            }

            // Apply discount based on membership tier
            switch ($user->membership_tier) {
                case "CD":
                    $this->applyDiscount(25, "CD Tier Membership");
                    break;
                case "Vinyl":
                    $this->applyDiscount(50, "Vinyl Tier Membership");
                    break;
                default:
                    // No discount for Radio tier
                    break;
            }
        } catch (\Exception $e) {
            // Log the error but don't break the booking process
            \Illuminate\Support\Facades\Log::error(
                "Error applying membership discount: " . $e->getMessage()
            );
        }

        return $this;
    }

    /**
     * Recalculate the price with membership discount for an existing booking
     * This is useful when a user upgrades their membership after making a booking
     *
     * @param \App\Models\User|null $userOverride Optional user object to use instead of looking up by ID
     * @return self
     */
    public function recalculatePrice(?User $userOverride = null): self
    {
        // Reset to original price based on room rate and duration
        if ($this->room_id) {
            $hours = $this->getDurationAttribute();
            $this->total_price = $this->room->hourly_rate * $hours;

            // Apply membership discount
            $this->applyMembershipDiscount($userOverride);

            // Save the changes
            $this->save();
        }

        return $this;
    }

    /**
     * Set the confirmation window based on the booking policy.
     */
    public function setConfirmationWindow(): self
    {
        $policy = $this->getBookingPolicy();

        if (!$policy || !$this->start_time) {
            return $this;
        }

        // Work with the start_time in UTC for consistency
        $startTimeUtc = $this->start_time_utc;

        // Calculate when confirmation should be requested
        $confirmationWindowStart = $startTimeUtc
            ->copy()
            ->subDays($policy->confirmationWindowDays);

        // Only set if it's in the future
        if ($confirmationWindowStart->isFuture()) {
            $this->confirmation_requested_at = $confirmationWindowStart;
        } else {
            // If the window has already started, set it to now
            $this->confirmation_requested_at = now()->setTimezone("UTC");
        }

        // Calculate the confirmation deadline
        $this->confirmation_deadline = $startTimeUtc
            ->copy()
            ->subDays($policy->autoConfirmationDeadlineDays);

        return $this;
    }

    /**
     * Check if the booking is within the confirmation window.
     */
    public function isInConfirmationWindow(): bool
    {
        if (
            !$this->confirmation_requested_at ||
            !$this->confirmation_deadline
        ) {
            return false;
        }

        // Use UTC for consistent datetime comparisons
        $now = now()->setTimezone("UTC");

        return $now->gte($this->confirmation_requested_at) &&
            $now->lte($this->confirmation_deadline);
    }

    /**
     * Check if the booking confirmation deadline has passed.
     */
    public function isConfirmationDeadlinePassed(): bool
    {
        if (!$this->confirmation_deadline) {
            return false;
        }

        // Use UTC for consistent datetime comparisons
        $now = now()->setTimezone("UTC");
        return $now->gt($this->confirmation_deadline);
    }

    /**
     * Check if the booking can be marked as a no-show.
     */
    public function canBeMarkedAsNoShow(): bool
    {
        if (!$this->start_time) {
            return false;
        }

        // Can be marked as no-show 15 minutes after the booking starts
        // We work with UTC values for consistent datetime comparisons
        $noShowTime = $this->start_time_utc->copy()->addMinutes(15);
        $now = now()->setTimezone("UTC");

        return $now->gt($noShowTime) &&
            $this->state instanceof BookingState\ConfirmedState;
    }

    /**
     * Confirm the booking.
     */
    public function confirm(?string $notes = null): self
    {
        if (!$this->state instanceof BookingState\ScheduledState) {
            throw new \InvalidArgumentException(
                "Only scheduled bookings can be confirmed."
            );
        }

        // Check if the booking can be confirmed
        $this->state->canBeConfirmed();

        $this->confirmed_at = now();
        if ($notes) {
            $this->notes = $notes;
        }

        $this->state = new BookingState\ConfirmedState($this);
        $this->save();

        return $this;
    }

    /**
     * Check in the booking.
     */
    public function checkIn(
        ?string $notes = null,
        bool $paymentCompleted = false
    ): self {
        if (!$this->state instanceof BookingState\ConfirmedState) {
            throw new \InvalidArgumentException(
                "Only confirmed bookings can be checked in."
            );
        }

        $this->check_in_time = now();
        if ($notes) {
            $this->notes = $notes;
        }
        $this->payment_completed = $paymentCompleted;

        $this->state = new BookingState\CheckedInState($this);
        $this->save();

        return $this;
    }

    /**
     * Complete the booking.
     */
    public function complete(?string $notes = null): self
    {
        if (!$this->state instanceof BookingState\CheckedInState) {
            throw new \InvalidArgumentException(
                "Only checked-in bookings can be completed."
            );
        }

        $this->check_out_time = now();
        if ($notes) {
            $this->notes = $notes;
        }

        $this->state = new BookingState\CompletedState($this);
        $this->save();

        return $this;
    }

    /**
     * Mark the booking as a no-show.
     */
    public function markAsNoShow(string $notes): self
    {
        if (!$this->state instanceof BookingState\ConfirmedState) {
            throw new \InvalidArgumentException(
                "Only confirmed bookings can be marked as no-show."
            );
        }

        if (!$this->canBeMarkedAsNoShow()) {
            throw new \InvalidArgumentException(
                "Booking cannot be marked as no-show yet."
            );
        }

        $this->no_show_notes = $notes;

        $this->state = new BookingState\NoShowState($this);
        $this->save();

        return $this;
    }

    /**
     * Cancel the booking.
     */
    public function cancel(string $reason): self
    {
        if (
            !(
                $this->state instanceof BookingState\ScheduledState ||
                $this->state instanceof BookingState\ConfirmedState
            )
        ) {
            throw new \InvalidArgumentException(
                "Only scheduled or confirmed bookings can be cancelled."
            );
        }

        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;

        $this->state = new BookingState\CancelledState($this);
        $this->save();

        return $this;
    }

    /**
     * Get the start time with the room's timezone applied.
     *
     * @param string $value
     * @return \Carbon\CarbonImmutable
     */
    public function getStartTimeAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Get the base Carbon instance from UTC storage
        $date = CarbonImmutable::parse($value);

        // Always use app timezone
        return $date->setTimezone(config("app.timezone"));
    }

    /**
     * Get the end time with the room's timezone applied.
     *
     * @param string $value
     * @return \Carbon\CarbonImmutable
     */
    public function getEndTimeAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Get the base Carbon instance from UTC storage
        $date = CarbonImmutable::parse($value);

        // Always use app timezone
        return $date->setTimezone(config("app.timezone"));
    }

    /**
     * Get the start time in UTC.
     *
     * @return \Carbon\CarbonImmutable|null
     */
    public function getStartTimeUtcAttribute()
    {
        if (!$this->attributes["start_time"]) {
            return null;
        }
        return CarbonImmutable::parse(
            $this->attributes["start_time"]
        )->setTimezone("UTC");
    }

    /**
     * Get the end time in UTC.
     *
     * @return \Carbon\CarbonImmutable|null
     */
    public function getEndTimeUtcAttribute()
    {
        if (!$this->attributes["end_time"]) {
            return null;
        }
        return CarbonImmutable::parse(
            $this->attributes["end_time"]
        )->setTimezone("UTC");
    }

    /**
     * Set the start_time attribute, converting to UTC for storage.
     *
     * @param mixed $value
     * @return void
     */
    public function setStartTimeAttribute($value)
    {
        if (!$value) {
            $this->attributes["start_time"] = null;
            return;
        }

        // If value is already a Carbon instance
        if ($value instanceof Carbon) {
            // Store in UTC
            $this->attributes["start_time"] = $value
                ->copy()
                ->setTimezone("UTC");
            return;
        }

        // Parse it in the app timezone then convert to UTC
        $date = Carbon::parse($value, config("app.timezone"));
        $this->attributes["start_time"] = $date->setTimezone("UTC");
    }

    /**
     * Set the end_time attribute, converting to UTC for storage.
     *
     * @param mixed $value
     * @return void
     */
    public function setEndTimeAttribute($value)
    {
        if (!$value) {
            $this->attributes["end_time"] = null;
            return;
        }

        // If value is already a Carbon instance
        if ($value instanceof Carbon) {
            // Store in UTC
            $this->attributes["end_time"] = $value->copy()->setTimezone("UTC");
            return;
        }

        // Parse it in the app timezone then convert to UTC
        $date = Carbon::parse($value, config("app.timezone"));
        $this->attributes["end_time"] = $date->setTimezone("UTC");
    }

    /**
     * Get the timezone to use for this booking
     *
     * @return string
     */
    public function getRoomTimezone(): string
    {
        return config("app.timezone");
    }

    /**
     * Check if this booking overlaps with a given time range.
     * All times are assumed to be in the room's timezone.
     *
     * @param \Carbon\Carbon $start Start time in room's timezone
     * @param \Carbon\Carbon $end End time in room's timezone
     * @return bool
     */
    public function overlapsWithTimeRange(Carbon $start, Carbon $end): bool
    {
        // Convert to UTC for comparison
        $startUtc = $start->copy()->setTimezone("UTC");
        $endUtc = $end->copy()->setTimezone("UTC");

        // Compare with booking times in UTC
        return $this->start_time_utc->lte($endUtc) &&
            $this->end_time_utc->gte($startUtc);
    }

    /**
     * Check if this booking falls on a specific date.
     * The date is assumed to be in the room's timezone.
     *
     * @param \Carbon\Carbon|string $date Date in room's timezone (can be string like '2023-04-15')
     * @return bool
     */
    public function isOnDate($date): bool
    {
        // Parse the date in room's timezone
        $timezone = $this->getRoomTimezone();
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date, $timezone);
        }

        // Create start and end of day in room's timezone
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Check if booking overlaps with this day
        return $this->overlapsWithTimeRange($startOfDay, $endOfDay);
    }

    /**
     * Format the booking times in the room's timezone with a given format.
     *
     * @param string $format The date format
     * @return array
     */
    public function formatTimesInRoomTimezone(
        string $format = "Y-m-d H:i:s"
    ): array {
        return [
            "start" => $this->start_time->format($format),
            "end" => $this->end_time->format($format),
        ];
    }

    /**
     * Format the booking times in UTC with a given format.
     *
     * @param string $format The date format
     * @return array
     */
    public function formatTimesInUtc(string $format = "Y-m-d H:i:s"): array
    {
        return [
            "start" => $this->start_time_utc->format($format),
            "end" => $this->end_time_utc->format($format),
        ];
    }

    /**
     * Scope a query to only include active bookings for a room within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Room $room
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRoomInDateRange(
        $query,
        Room $room,
        Carbon $startDate,
        Carbon $endDate
    ) {
        return $query
            ->where("state", "!=", "cancelled")
            ->where("room_id", $room->id)
            ->whereBetween("start_time", [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->with(["room", "user"]);
    }

    public function getEventId(): string|int
    {
        return "booking:{$this->id}";
    }

    public function getStartTime(): DateTimeImmutable
    {
        return CarbonImmutable::parse($this->start_time);
    }

    public function getEndTime(): DateTimeImmutable
    {
        return CarbonImmutable::parse($this->end_time);
    }

    public function getEventTitle(): string
    {
        return $this->user->name;
    }

    public function belongsToCurrentUser(): bool
    {
        return $this->user->id === Auth::id();
    }

    public function getEventMetadata(): array
    {
        return [];
    }
}
