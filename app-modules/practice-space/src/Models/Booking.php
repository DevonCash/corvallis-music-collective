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
use Illuminate\Support\Facades\DB;
use CorvMC\Finance\Concerns\HasPayments;

class Booking extends Model
{
    // Temporarily commented out HasPayments for testing
    use LogsActivity, HasFactory, SoftDeletes, HasPayments;

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
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_price' => 'decimal:2',
        'state' => State::class.':'.BookingState::class,
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
        return $this->room->category->bookingPolicy;
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
            : $policy->max_booking_duration_hours;
            
        $minDuration = $override && isset($override['min_booking_duration_hours']) 
            ? $override['min_booking_duration_hours'] 
            : $policy->min_booking_duration_hours;
        
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
            : $policy->min_advance_booking_hours;
            
        $maxDays = $override && isset($override['max_advance_booking_days']) 
            ? $override['max_advance_booking_days'] 
            : $policy->max_advance_booking_days;
        
        return $hoursUntilBooking >= $minHours && $daysUntilBooking <= $maxDays;
    }

    /**
     * Validate the booking against the weekly limit in the policy.
     */
    protected function validateWeeklyLimit($policy, $override = null): bool
    {
        $maxBookingsPerWeek = $override && isset($override['max_bookings_per_week']) 
            ? $override['max_bookings_per_week'] 
            : $policy->max_bookings_per_week;
        
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
        
        return $hoursUntilBooking >= $policy->cancellation_hours;
    }

    /**
     * Calculate the total price for this booking
     * 
     * @return float
     */
    public function calculateTotalPrice(): float
    {
        if ($this->total_price) {
            return (float) $this->total_price;
        }
        
        if (!$this->room) {
            return 0;
        }
        
        $hours = $this->getDurationInHours();
        $hourlyRate = $this->room->hourly_rate ?? 0;
        
        return $hours * $hourlyRate;
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
        
        $originalPrice = $this->calculateTotalPrice();
        $discountAmount = $originalPrice * ($discountPercent / 100);
        $discountedPrice = $originalPrice - $discountAmount;
        
        $this->update([
            'total_price' => $discountedPrice,
            'notes' => $this->notes . "\nDiscount applied: {$discountPercent}% " . ($reason ? "($reason)" : ""),
        ]);
        
        return $this;
    }
    
    /**
     * Initialize traits for the model.
     */
    protected function initializeTraits()
    {
        // Check if the HasPayments trait exists in the Finance module
        if (trait_exists('CorvMC\Finance\Concerns\HasPayments')) {
            // The trait exists, so we can use it
            // No need to do anything special here
        }
    }
} 