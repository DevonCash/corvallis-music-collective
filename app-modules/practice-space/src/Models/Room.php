<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use CorvMC\PracticeSpace\Database\Factories\RoomFactory;
use CorvMC\PracticeSpace\Casts\BookingPolicyCast;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;

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

    /**
     * Create or update the associated product in the finance module.
     * 
     * @param array $attributes Additional product attributes
     * @return mixed The product model or null if Finance module is not available
     */
    public function syncProduct(array $attributes = [])
    {
        if (!class_exists('CorvMC\Finance\Models\Product')) {
            return null;
        }

        $productClass = 'CorvMC\Finance\Models\Product';
        
        $productData = array_merge([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->hourly_rate,
            'type' => 'service',
            'is_active' => $this->is_active,
        ], $attributes);

        if ($this->product_id) {
            // Update existing product
            $product = $productClass::find($this->product_id);
            if ($product) {
                $product->update($productData);
                return $product;
            }
        }
        
        // Create new product
        $product = $productClass::create($productData);
        $this->update(['product_id' => $product->id]);
        return $product;
    }

    /**
     * Sync the room's hourly rate with the product price.
     * 
     * @return self
     */
    public function syncWithProduct(): self
    {
        if (!class_exists('CorvMC\Finance\Models\Product') || !$this->product_id) {
            return $this;
        }

        $product = $this->product;
        if ($product && $this->hourly_rate != $product->price) {
            $this->update(['hourly_rate' => $product->price]);
        }

        return $this;
    }

    /**
     * Update the product price based on the room's hourly rate.
     * 
     * @return mixed The product model or null if Finance module is not available
     */
    public function updateProductPrice()
    {
        if (!class_exists('CorvMC\Finance\Models\Product') || !$this->product_id) {
            return null;
        }

        $product = $this->product;
        if ($product && $product->price != $this->hourly_rate) {
            $product->update(['price' => $this->hourly_rate]);
        }

        return $product;
    }

    /**
     * Deactivate the associated product.
     * 
     * @return mixed The product model or null if Finance module is not available
     */
    public function deactivateProduct()
    {
        if (!class_exists('CorvMC\Finance\Models\Product') || !$this->product_id) {
            return null;
        }

        $product = $this->product;
        if ($product && $product->is_active) {
            $product->update(['is_active' => false]);
        }

        return $product;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoomFactory::new();
    }

    /**
     * Check if this room is available for the given time slot
     *
     * @param \Carbon\Carbon $startDateTime
     * @param \Carbon\Carbon $endDateTime
     * @return bool
     */
    public function isAvailable(\Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime): bool
    {
        $conflictingBookings = $this->bookings()
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                    ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                    ->orWhere(function ($query) use ($startDateTime, $endDateTime) {
                        $query->where('start_time', '<=', $startDateTime)
                            ->where('end_time', '>=', $endDateTime);
                    });
            })
            ->where('state', '!=', 'cancelled')
            ->count();
            
        return $conflictingBookings === 0;
    }

    /**
     * Get available time slots for this room on a specific date
     *
     * @param string $date
     * @param float|null $duration Duration in hours (optional)
     * @return array
     */
    public function getAvailableTimeSlots(string $date, ?float $duration = null): array
    {
        // Get operating hours
        $operatingHours = $this->getOperatingHours($date);
        $openingTime = $operatingHours['opening'];
        $closingTime = $operatingHours['closing'];
        
        // Get all bookings for this room on this date
        $bookings = $this->bookings()
            ->where('state', '!=', 'cancelled')
            ->where(function ($query) use ($date) {
                $startOfDay = \Carbon\Carbon::parse($date)->startOfDay();
                $endOfDay = \Carbon\Carbon::parse($date)->endOfDay();
                
                $query->whereBetween('start_time', [$startOfDay, $endOfDay])
                    ->orWhereBetween('end_time', [$startOfDay, $endOfDay])
                    ->orWhere(function ($query) use ($startOfDay, $endOfDay) {
                        $query->where('start_time', '<=', $startOfDay)
                            ->where('end_time', '>=', $endOfDay);
                    });
            })
            ->get();
        
        // Generate all possible time slots
        $timeSlots = [];
        $currentTime = $openingTime->copy();
        
        while ($currentTime < $closingTime) {
            $timeKey = $currentTime->format('H:i');
            $displayTime = $currentTime->format('g:i A');
            
            // Check if this time slot is available (not within any existing booking)
            $isTimeSlotBooked = $bookings->contains(function ($booking) use ($currentTime) {
                // Check if this time falls within a booking
                return $currentTime->between(
                    $booking->start_time, 
                    $booking->end_time->subMinute()
                );
            });
            
            if (!$isTimeSlotBooked) {
                // If duration is specified, check if there's enough time until the next booking
                if ($duration !== null) {
                    $endTimeSlot = $currentTime->copy()->addMinutes($duration * 60);
                    
                    // Check if the end time exceeds closing time
                    if ($endTimeSlot > $closingTime) {
                        $currentTime->addMinutes(30);
                        continue;
                    }
                    
                    // Find any booking that would conflict with this duration
                    $conflictingBooking = $bookings->first(function ($booking) use ($currentTime, $endTimeSlot) {
                        // Check if booking starts during our time slot
                        $bookingStartsDuringSlot = $booking->start_time->between($currentTime, $endTimeSlot);
                        
                        // Check if our time slot starts during booking
                        $slotStartsDuringBooking = $currentTime->between(
                            $booking->start_time, 
                            $booking->end_time
                        );
                        
                        return $bookingStartsDuringSlot || $slotStartsDuringBooking;
                    });
                    
                    if (!$conflictingBooking) {
                        $timeSlots[$timeKey] = $displayTime;
                    }
                } else {
                    // If no duration specified, just add the time slot
                    $timeSlots[$timeKey] = $displayTime;
                }
            }
            
            $currentTime->addMinutes(30); // Move to next half-hour
        }
        
        return $timeSlots;
    }

    /**
     * Get the booking policy for this room
     * 
     * @return BookingPolicy
     */
    public function getBookingPolicy(): BookingPolicy
    {
        // If this room has a specific policy, use it
        if ($this->booking_policy !== null) {
            return $this->booking_policy;
        }
        
        // Otherwise, use the category's default policy if available
        if ($this->category && $this->category->default_booking_policy !== null) {
            return $this->category->default_booking_policy;
        }
        
        // Fall back to default policy
        return new BookingPolicy();
    }
    
    /**
     * Update the booking policy for this room
     * 
     * @param BookingPolicy|array $policy
     * @return self
     */
    public function updateBookingPolicy(BookingPolicy|array $policy): self
    {
        $this->booking_policy = $policy instanceof BookingPolicy 
            ? $policy 
            : BookingPolicy::fromArray($policy);
            
        $this->save();
        
        return $this;
    }
    
    /**
     * Reset the room's booking policy to use the category default
     * 
     * @return self
     */
    public function resetBookingPolicy(): self
    {
        $this->booking_policy = null;
        $this->save();
        
        return $this;
    }
    
    /**
     * Get the operating hours for this room on a specific date
     * 
     * @param string $date
     * @return array Returns ['opening' => Carbon, 'closing' => Carbon]
     */
    public function getOperatingHours(string $date): array
    {
        return $this->getBookingPolicy()->getOperatingHours($date);
    }
    
    /**
     * Get the maximum booking duration in hours
     * 
     * @return float
     */
    public function getMaxBookingDuration(): float
    {
        return $this->getBookingPolicy()->maxBookingDurationHours;
    }
    
    /**
     * Get the minimum booking duration in hours
     * 
     * @return float
     */
    public function getMinBookingDuration(): float
    {
        return $this->getBookingPolicy()->minBookingDurationHours;
    }

    /**
     * Get available durations for this room at a specific date and time
     *
     * @param string $date
     * @param string|null $time Optional time parameter
     * @param bool $includeHalfHour
     * @return array
     */
    public function getAvailableDurations(string $date, ?string $time = null, bool $includeHalfHour = false): array
    {
        // Get operating hours
        $operatingHours = $this->getOperatingHours($date);
        $openingTime = $operatingHours['opening'];
        $closingTime = $operatingHours['closing'];
        
        // Get all bookings for this room on this date
        $bookings = $this->bookings()
            ->where('state', '!=', 'cancelled')
            ->where(function ($query) use ($date) {
                $startOfDay = \Carbon\Carbon::parse($date)->startOfDay();
                $endOfDay = \Carbon\Carbon::parse($date)->endOfDay();
                
                $query->whereBetween('start_time', [$startOfDay, $endOfDay])
                    ->orWhereBetween('end_time', [$startOfDay, $endOfDay])
                    ->orWhere(function ($query) use ($startOfDay, $endOfDay) {
                        $query->where('start_time', '<=', $startOfDay)
                            ->where('end_time', '>=', $endOfDay);
                    });
            })
            ->orderBy('start_time')
            ->get();
        
        // If no specific time is provided, return half-hour blocks between opening and closing times
        if ($time === null) {
            $result = [];
            $currentTime = $openingTime->copy();
            
            // Generate half-hour blocks
            while ($currentTime < $closingTime) {
                $timeKey = $currentTime->format('H:i');
                
                // Check if this time slot is available (not within any existing booking)
                $isTimeSlotBooked = $bookings->contains(function ($booking) use ($currentTime) {
                    // Check if this time falls within a booking
                    return $currentTime->between(
                        $booking->start_time, 
                        $booking->end_time->subMinute()
                    );
                });
                
                if (!$isTimeSlotBooked) {
                    // For each available time slot, get available durations
                    
                    // Find the next booking that starts after this time
                    $nextBooking = $bookings->first(function ($booking) use ($currentTime) {
                        return $booking->start_time > $currentTime;
                    });
                    
                    // Calculate maximum possible duration in hours
                    if ($nextBooking) {
                        $maxPossibleDuration = $currentTime->diffInMinutes($nextBooking->start_time) / 60;
                    } else {
                        $maxPossibleDuration = $currentTime->diffInMinutes($closingTime) / 60;
                    }
                    
                    // Generate duration options
                    $durations = $this->generateDurationOptions($maxPossibleDuration, $includeHalfHour);
                    
                    if (!empty($durations)) {
                        $result[$timeKey] = $durations;
                    }
                }
                
                $currentTime->addMinutes(30);
            }
            
            return $result;
        }
        
        // Parse the start time
        $startTime = \Carbon\Carbon::parse($date . ' ' . $time);
        
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
        
        // Generate duration options
        return $this->generateDurationOptions($maxPossibleDuration, $includeHalfHour);
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
        $policy = $this->getBookingPolicy();
        
        // Respect the policy's min and max durations
        $minDuration = $policy->minBookingDurationHours;
        $maxPolicyDuration = $policy->maxBookingDurationHours;
        
        // The actual max duration is the minimum of the policy max and the available time
        $effectiveMaxDuration = min($maxDuration, $maxPolicyDuration);
        
        // Determine the step size based on includeHalfHour and policy min duration
        $step = $includeHalfHour ? min(0.5, $minDuration) : max(1.0, $minDuration);
        
        // Start from the minimum duration
        for ($duration = $minDuration; $duration <= $effectiveMaxDuration; $duration += $step) {
            // Format the duration label
            if ($duration == 0.5) {
                $options[$duration] = '30 minutes';
            } elseif ($duration == 1) {
                $options[$duration] = '1 hour';
            } elseif ($duration - floor($duration) == 0) {
                // Whole hours
                $options[$duration] = $duration . ' hours';
            } else {
                // Fractional hours (e.g., 1.5 hours)
                $options[$duration] = $duration . ' hours';
            }
        }
        
        return $options;
    }

    /**
     * Get dates when this room is fully booked
     *
     * @param \Carbon\Carbon|null $startDate Start date for the range (defaults to today)
     * @param \Carbon\Carbon|null $endDate End date for the range (defaults to 3 months from start)
     * @return array Array of dates in Y-m-d format that are fully booked
     */
    public function getFullyBookedDates(?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        // Default date range: today to 3 months from now
        $startDate = $startDate ?? \Carbon\Carbon::today();
        $endDate = $endDate ?? \Carbon\Carbon::today()->addMonths(3);
        
        // Get all bookings for this room within the date range
        $bookings = $this->bookings()
            ->where('state', '!=', 'cancelled')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->orWhereBetween('end_time', [$startDate->startOfDay(), $endDate->endOfDay()]);
            })
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
                $availableTimeSlots = $this->getAvailableTimeSlots($dateString);
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
    private function isDateFullyBooked(array $bookings, \Carbon\Carbon $openingTime, \Carbon\Carbon $closingTime): bool
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
} 