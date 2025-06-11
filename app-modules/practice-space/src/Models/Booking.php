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
use CorvMC\PracticeSpace\Models\States\BookingState;
use CorvMC\StateManagement\Casts\State;
use CorvMC\StateManagement\Traits\HasStates;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use CorvMC\Finance\Concerns\HasPayments;
use CorvMC\PracticeSpace\Traits\LogsNotifications;
use CorvMC\PracticeSpace\Traits\HasRecurringBookings;
use CorvMC\PracticeSpace\Contracts\CalendarEvent;
use DateTimeImmutable;
use Illuminate\Support\Facades\Auth;
use CorvMC\PracticeSpace\Models\States\ScheduledState;
use CorvMC\PracticeSpace\Models\States\ConfirmedState;
use CorvMC\PracticeSpace\Models\States\CheckedInState;
use CorvMC\PracticeSpace\Models\States\CompletedState;
use CorvMC\PracticeSpace\Models\States\CancelledState;
use CorvMC\PracticeSpace\Models\States\NoShowState;
use Filament\Actions\Action;
use CorvMC\PracticeSpace\Models\PracticeSpace;

class Booking extends Model implements CalendarEvent
{
    use LogsActivity,
        HasFactory,
        SoftDeletes,
        HasPayments,
        HasRecurringBookings,
        HasStates;

    protected $table = "practice_space_bookings";

    protected $fillable = [
        'user_id',
        'practice_space_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'cancellation_reason',
        'no_show_reason',
        'check_in_time',
        'check_out_time',
        'completed_at',
        'cancelled_at',
        'no_show_at',
    ];

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
        "status" => State::class . ":" . BookingState::class,
        "completed_at" => "datetime",
        "cancelled_at" => "datetime",
        "no_show_at" => "datetime",
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
        "completed_at",
        "cancelled_at",
        "no_show_at",
        "recurrence_end_date",
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('uncancelled', function ($builder) {
            $builder->whereNot('status', 'cancelled');
        });
    }

    /**
     * Get the state column name.
     */
    public function getStateColumn(): string
    {
        return 'status';
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
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["status", "start_time", "end_time", "notes", "status"])
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
                            "status",
                        ]);
                    });
                });
            })
            ->orderBy("created_at");
    }

    /**
     * Get the duration of the booking in hours.
     */
    public function getDurationInHours(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    // Price including discounts
    public function getFinalPriceAttribute(): float
    {
        return $this->room->hourly_rate;
    }

    // Final Price * duration
    public function getTotalCostAttribute(): float
    {
        return $this->final_price * $this->duration;
    }

    /**
     * Check if the booking can be cancelled with a refund.
     */
    public function canCancelWithRefund(): bool
    {
        $policy = $this->room->booking_policy();

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

    public function getDurationAttribute(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    /**
     * Set the confirmation window based on the booking policy.
     */
    public function setConfirmationWindow(): self
    {
        $policy = $this->room->booking_policy;

        if (!$policy || !$this->start_time) {
            return $this;
        }

        // Calculate when confirmation should be requested
        $confirmationWindowStart = $this->start_time
            ->subDays($policy->confirmationWindowDays);

        // Only set if it's in the future
        if ($confirmationWindowStart->isFuture()) {
            $this->no_show_at = $confirmationWindowStart;
        } else {
            // If the window has already started, set it to now
            $this->no_show_at = now()->setTimezone("UTC");
        }

        // Calculate the confirmation deadline
        $this->recurrence_end_date = $this->start_time
            ->subDays($policy->autoConfirmationDeadlineDays);

        return $this;
    }

    /**
     * Check if the booking is within the confirmation window.
     */
    public function isInConfirmationWindow(): bool
    {
        if (
            !$this->no_show_at ||
            !$this->recurrence_end_date
        ) {
            return false;
        }

        // Use UTC for consistent datetime comparisons
        $now = now()->setTimezone("UTC");

        return $now->gte($this->no_show_at) &&
            $now->lte($this->recurrence_end_date);
    }

    /**
     * Check if the booking confirmation deadline has passed.
     */
    public function isConfirmationDeadlinePassed(): bool
    {
        if (!$this->recurrence_end_date) {
            return false;
        }

        // Use UTC for consistent datetime comparisons
        $now = now()->setTimezone("UTC");
        return $now->gt($this->recurrence_end_date);
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
            $this->status instanceof BookingState\ConfirmedState;
    }

    /**
     * Confirm the booking.
     */
    public function confirm(?string $notes = null): self
    {
        if (!$this->status instanceof BookingState\ScheduledState) {
            throw new \InvalidArgumentException(
                "Only scheduled bookings can be confirmed."
            );
        }

        // Check if the booking can be confirmed
        BookingState\ScheduledState::canBeConfirmed($this);

        $this->completed_at = now();
        if ($notes) {
            $this->notes = $notes;
        }

        $this->status = BookingState\ConfirmedState::class;
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
        if (!$this->status instanceof BookingState\ConfirmedState) {
            throw new \InvalidArgumentException(
                "Only confirmed bookings can be checked in."
            );
        }

        $this->check_in_time = now();
        if ($notes) {
            $this->notes = $notes;
        }
        $this->payment_completed = $paymentCompleted;

        $this->status = BookingState\CheckedInState::class;
        $this->save();

        return $this;
    }

    /**
     * Complete the booking.
     */
    public function complete(?string $notes = null): self
    {
        if (!$this->status instanceof BookingState\CheckedInState) {
            throw new \InvalidArgumentException(
                "Only checked-in bookings can be completed."
            );
        }

        $this->check_out_time = now();
        if ($notes) {
            $this->notes = $notes;
        }

        $this->status = BookingState\CompletedState::class;
        $this->save();

        return $this;
    }

    /**
     * Mark the booking as a no-show.
     */
    public function markAsNoShow(string $notes): self
    {
        if (!$this->status instanceof BookingState\ConfirmedState) {
            throw new \InvalidArgumentException(
                "Only confirmed bookings can be marked as no-show."
            );
        }

        if (!$this->canBeMarkedAsNoShow()) {
            throw new \InvalidArgumentException(
                "Booking cannot be marked as no-show yet."
            );
        }

        $this->no_show_reason = $notes;

        $this->status = BookingState\NoShowState::class;
        $this->save();

        return $this;
    }

    /**
     * Cancel the booking.
     */
    public function cancel(?string $reason = null): self
    {
        if (
            !(
                $this->status instanceof BookingState\ScheduledState ||
                $this->status instanceof BookingState\ConfirmedState
            )
        ) {
            throw new \InvalidArgumentException(
                "Only scheduled or confirmed bookings can be cancelled."
            );
        }

        $this->cancelled_at = now();
        $this->cancellation_reason = $reason ?? 'No reason provided';

        $this->status = BookingState\CancelledState::class;
        $this->save();

        return $this;
    }

    public function getEventId(): string|int
    {
        return "booking:{$this->id}";
    }

    public function getStartTime(): DateTimeImmutable
    {
        return CarbonImmutable::createFromMutable($this->start_time);
    }

    public function getEndTime(): DateTimeImmutable
    {
        return CarbonImmutable::createFromMutable($this->end_time);
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

    public function practiceSpace(): BelongsTo
    {
        return $this->belongsTo(PracticeSpace::class);
    }
}
