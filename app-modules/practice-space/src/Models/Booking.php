<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Carbon\Carbon;
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
use Illuminate\Support\Facades\Log;

class Booking extends Model
{
    // Temporarily commented out HasPayments for testing
    use LogsActivity, HasFactory, SoftDeletes, HasPayments, LogsNotifications;

    protected $table = 'practice_space_bookings';

    protected $fillable = [
        'room_id',
        'user_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'is_recurring',
        'recurring_pattern',
        'check_in_time',
        'check_out_time',
        'total_price',
        'payment_status',
        'state',
        'confirmation_requested_at',
        'confirmation_deadline',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'no_show_notes',
        'payment_completed',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_price' => 'decimal:2',
        'state' => State::class.':'.BookingState::class,
        'confirmation_requested_at' => 'datetime',
        'confirmation_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payment_completed' => 'boolean',
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
                    $hours = $booking->start_time->diffInHours($booking->end_time);
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
            ->logOnly(['status', 'start_time', 'end_time', 'notes', 'state'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('booking')
            ->setDescriptionForEvent(function(string $eventName) {
                return "Booking was {$eventName}";
            });
    }

    /**
     * Get the status history for this booking.
     */
    public function getStatusHistory()
    {
        return $this->activities()
            ->where('log_name', 'booking')
            ->where(function($query) {
                $query->where('event', 'created')
                    ->orWhere(function($q) {
                        $q->where('event', 'updated')
                            ->where(function($q2) {
                                $q2->whereJsonContains('properties->attributes', ['status'])
                                   ->orWhereJsonContains('properties->attributes', ['state']);
                            });
                    });
            })
            ->orderBy('created_at');
    }

    /**
     * Get the booking policy for this booking.
     */
    public function getBookingPolicy()
    {
        return $this->room->booking_policy ?? new \CorvMC\PracticeSpace\ValueObjects\BookingPolicy();
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
        
        $maxDuration = $override && isset($override['max_booking_duration_hours']) 
            ? $override['max_booking_duration_hours'] 
            : $policy->maxBookingDurationHours;
            
        $minDuration = $override && isset($override['min_booking_duration_hours']) 
            ? $override['min_booking_duration_hours'] 
            : $policy->minBookingDurationHours;
        
        return $duration <= $maxDuration && $duration >= $minDuration;
    }

    /**
     * Validate the booking advance notice against the policy.
     */
    protected function validateAdvanceNotice($policy, $override = null): bool
    {
        $now = Carbon::now();
        $hoursUntilBooking = $now->diffInMinutes($this->start_time, false) / 60;
        $daysUntilBooking = $now->diffInDays($this->start_time, false);
        
        $minHours = $override && isset($override['min_advance_booking_hours']) 
            ? $override['min_advance_booking_hours'] 
            : $policy->minAdvanceBookingHours;
            
        $maxDays = $override && isset($override['max_advance_booking_days']) 
            ? $override['max_advance_booking_days'] 
            : $policy->maxAdvanceBookingDays;
        
        return $hoursUntilBooking >= $minHours && $daysUntilBooking <= $maxDays;
    }

    /**
     * Validate the booking against the weekly limit in the policy.
     */
    protected function validateWeeklyLimit($policy, $override = null): bool
    {
        $maxBookingsPerWeek = $override && isset($override['max_bookings_per_week']) 
            ? $override['max_bookings_per_week'] 
            : $policy->maxBookingsPerWeek;
        
        // Get the start and end of the current week
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        
        // Count bookings by this user in the current week
        $bookingsThisWeek = self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id) // Exclude this booking if it's already saved
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->count();
        
        // Check if adding this booking would exceed the weekly limit
        return ($bookingsThisWeek + 1) <= $maxBookingsPerWeek;
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
    public function applyDiscount(float $discountPercent, string $reason = null): self
    {
        if ($discountPercent <= 0 || $discountPercent > 100) {
            return $this;
        }
        
        // Calculate in cents to avoid floating point issues
        $originalPriceInCents = $this->calculateTotalPriceInCents();
        $discountAmountInCents = (int) round($originalPriceInCents * ($discountPercent / 100));
        $discountedPriceInCents = $originalPriceInCents - $discountAmountInCents;
        
        // Convert back to dollars for storage
        $discountedPrice = $discountedPriceInCents / 100;
        
        $this->update([
            'total_price' => $discountedPrice,
            'notes' => $this->notes . "\nDiscount applied: {$discountPercent}% " . ($reason ? "($reason)" : ""),
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
                case 'CD':
                    $this->applyDiscount(25, 'CD Tier Membership');
                    break;
                case 'Vinyl':
                    $this->applyDiscount(50, 'Vinyl Tier Membership');
                    break;
                default:
                    // No discount for Radio tier
                    break;
            }
        } catch (\Exception $e) {
            // Log the error but don't break the booking process
            \Illuminate\Support\Facades\Log::error('Error applying membership discount: ' . $e->getMessage());
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
        
        // Calculate when confirmation should be requested
        $confirmationWindowStart = $this->start_time->copy()->subDays($policy->confirmationWindowDays);
        
        // Only set if it's in the future
        if ($confirmationWindowStart->isFuture()) {
            $this->confirmation_requested_at = $confirmationWindowStart;
        } else {
            // If the window has already started, set it to now
            $this->confirmation_requested_at = now();
        }
        
        // Calculate the confirmation deadline
        $this->confirmation_deadline = $this->start_time->copy()->subDays($policy->autoConfirmationDeadlineDays);
        
        return $this;
    }
    
    /**
     * Check if the booking is within the confirmation window.
     */
    public function isInConfirmationWindow(): bool
    {
        if (!$this->confirmation_requested_at || !$this->confirmation_deadline) {
            return false;
        }
        
        $now = now();
        return $now->isAfter($this->confirmation_requested_at) && $now->isBefore($this->confirmation_deadline);
    }
    
    /**
     * Check if the booking confirmation deadline has passed.
     */
    public function isConfirmationDeadlinePassed(): bool
    {
        if (!$this->confirmation_deadline) {
            return false;
        }
        
        return now()->isAfter($this->confirmation_deadline);
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
        $noShowTime = $this->start_time->copy()->addMinutes(15);
        
        // For testing purposes, use Carbon::now() instead of now() to allow mocking
        return Carbon::now()->isAfter($noShowTime) && $this->state instanceof BookingState\ConfirmedState;
    }
    
    /**
     * Confirm the booking.
     */
    public function confirm(?string $notes = null): self
    {
        if (!$this->state instanceof BookingState\ScheduledState) {
            throw new \InvalidArgumentException('Only scheduled bookings can be confirmed.');
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
    public function checkIn(?string $notes = null, bool $paymentCompleted = false): self
    {
        if (!$this->state instanceof BookingState\ConfirmedState) {
            throw new \InvalidArgumentException('Only confirmed bookings can be checked in.');
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
            throw new \InvalidArgumentException('Only checked-in bookings can be completed.');
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
            throw new \InvalidArgumentException('Only confirmed bookings can be marked as no-show.');
        }
        
        if (!$this->canBeMarkedAsNoShow()) {
            throw new \InvalidArgumentException('Booking cannot be marked as no-show yet.');
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
        if (!($this->state instanceof BookingState\ScheduledState || $this->state instanceof BookingState\ConfirmedState)) {
            throw new \InvalidArgumentException('Only scheduled or confirmed bookings can be cancelled.');
        }
        
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        
        $this->state = new BookingState\CancelledState($this);
        $this->save();
        
        return $this;
    }
} 