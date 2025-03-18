<?php

namespace CorvMC\PracticeSpace\Traits;

use Carbon\Carbon;
use Recurr\Rule;
use Recurr\RecurrenceCollection;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\Constraint\BetweenConstraint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait HasRecurringBookings
{
    /**
     * Get the recurring bookings that belong to the parent booking.
     */
    public function recurringBookings()
    {
        return $this->hasMany(static::class, 'recurring_booking_id');
    }

    /**
     * Get the parent booking for a recurring instance.
     */
    public function recurringParent()
    {
        return $this->belongsTo(static::class, 'recurring_booking_id');
    }

    /**
     * Set up a recurring booking pattern using RRULE
     *
     * @param string $rrule RFC 5545 compliant RRULE string
     * @param \Carbon\Carbon|string|null $until End date for the recurrence
     * @return self
     */
    public function setRecurringRule(string $rrule, $until = null): self
    {
        // Mark this booking as the parent of the recurrence
        $this->is_recurring_parent = true;
        $this->is_recurring = true;
        
        // Store the RRULE directly
        $this->rrule_string = $rrule;
        
        // Set end date if provided
        if ($until) {
            $this->recurrence_end_date = $until instanceof Carbon ? $until : Carbon::parse($until);
        } else if (str_contains($rrule, 'UNTIL=')) {
            // Extract UNTIL date from the RRULE if present
            preg_match('/UNTIL=([0-9T]+)/', $rrule, $matches);
            if (isset($matches[1])) {
                $this->recurrence_end_date = Carbon::createFromFormat('Ymd\THis', $matches[1])->setTimezone('UTC');
            }
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Create a Rule object from the stored RRULE string
     *
     * @return \Recurr\Rule|null
     */
    public function getRecurrenceRule(): ?Rule
    {
        if (!$this->rrule_string) {
            return null;
        }
        
        // Create a start date/time object for the rule
        $startDate = clone $this->start_time_utc;
        
        // Create the rule from our RRULE
        $rule = new Rule($this->rrule_string, $startDate->toDateTime());
        
        // Set end date if available
        if ($this->recurrence_end_date) {
            $rule->setUntil($this->recurrence_end_date->toDateTime());
        }
        
        return $rule;
    }

    /**
     * Generate all occurrences of the recurring booking
     *
     * @param \Carbon\Carbon|null $maxDate Optional max date to limit recurrences
     * @return \Recurr\RecurrenceCollection Collection of Recurr\Recurrence objects
     */
    public function generateRecurrenceOccurrences($maxDate = null): \Recurr\RecurrenceCollection
    {
        if (!$this->is_recurring_parent || !$this->rrule_string) {
            return new \Recurr\RecurrenceCollection();
        }
        
        $rule = $this->getRecurrenceRule();
        if (!$rule) {
            return new \Recurr\RecurrenceCollection();
        }
        
        $transformer = new ArrayTransformer();
        
        // Calculate the event duration for the transformer
        $durationInSeconds = $this->start_time_utc->diffInSeconds($this->end_time_utc);
        
        // Apply date constraints if provided
        if ($maxDate) {
            $constraint = new BetweenConstraint(
                $this->start_time_utc->toDateTime(),
                $maxDate instanceof Carbon ? $maxDate->toDateTime() : Carbon::parse($maxDate)->toDateTime()
            );
            
            // Transform with virtual end dates based on duration
            return $transformer->transform($rule, $constraint, true);
        }
        
        // Transform with virtual end dates based on duration
        return $transformer->transform($rule, null, true);
    }

    /**
     * Create actual booking records for recurrence pattern
     *
     * @param int $limit Max number of bookings to create
     * @param \Carbon\Carbon|null $maxDate Optional max date to limit recurrences
     * @return \Illuminate\Support\Collection Collection of created booking instances
     */
    public function createRecurringBookings(int $limit = 10, $maxDate = null): Collection
    {
        if (!$this->is_recurring_parent) {
            return collect();
        }
        
        // Get recurrence occurrences
        $occurrences = $this->generateRecurrenceOccurrences($maxDate);
        
        // Skip the first occurrence as it's the parent booking itself
        $occurrencesArray = iterator_to_array($occurrences);
        $occurrencesArray = array_slice($occurrencesArray, 1, $limit);
        
        $createdBookings = collect();
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            foreach ($occurrencesArray as $occurrence) {
                // Convert start and end dates to Carbon
                $startTime = Carbon::instance($occurrence->getStart())
                    ->setTimezone($this->getRoomTimezone());
                $endTime = Carbon::instance($occurrence->getEnd())
                    ->setTimezone($this->getRoomTimezone());
                
                // Create the new booking instance
                $newBooking = new static();
                $newBooking->fill($this->getRecurringBookingAttributes());
                $newBooking->start_time = $startTime;
                $newBooking->end_time = $endTime;
                $newBooking->is_recurring = true;
                $newBooking->recurring_booking_id = $this->id;
                $newBooking->save();
                
                // Apply the same state
                $newBooking->state = get_class($this->state);
                $newBooking->save();
                
                $createdBookings->push($newBooking);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $createdBookings;
    }

    /**
     * Get attributes to copy to recurring instances
     *
     * @return array
     */
    protected function getRecurringBookingAttributes(): array
    {
        return [
            'room_id' => $this->room_id,
            'user_id' => $this->user_id,
            'notes' => $this->notes,
            'total_price' => $this->total_price,
            'payment_completed' => $this->payment_completed ?? false,
            'state' => get_class($this->state),
        ];
    }

    /**
     * Delete future recurring bookings
     *
     * @param \Carbon\Carbon|null $fromDate Start date for deletion, defaults to now
     * @return int Number of deleted bookings
     */
    public function deleteFutureRecurringBookings($fromDate = null): int
    {
        if (!$this->is_recurring_parent) {
            return 0;
        }
        
        $fromDate = $fromDate ?? now()->setTimezone('UTC');
        
        return static::where('recurring_booking_id', $this->id)
            ->where('start_time', '>=', $fromDate)
            ->delete();
    }

    /**
     * Check if a booking is a recurring instance (not the parent)
     *
     * @return bool
     */
    public function isRecurringInstance(): bool
    {
        return $this->is_recurring && $this->recurring_booking_id !== null;
    }

    /**
     * Update this booking and future recurring instances
     *
     * @param array $attributes
     * @param array $recurringOptions Optional parameters for regenerating recurrences
     * @return \Illuminate\Database\Eloquent\Collection Collection of updated bookings
     */
    public function updateWithFutureRecurrences(array $attributes, array $recurringOptions = []): EloquentCollection
    {
        // If this is not a parent booking, find the parent
        $parentBooking = $this->is_recurring_parent ? $this : $this->recurringParent;
        
        if (!$parentBooking) {
            // Just update this booking if there's no parent
            $this->update($attributes);
            return new EloquentCollection([$this]);
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Update parent booking
            $parentBooking->update($attributes);
            
            // If RRULE is changing, update it
            if (isset($attributes['rrule_string'])) {
                $until = $attributes['recurrence_end_date'] ?? null;
                $parentBooking->setRecurringRule($attributes['rrule_string'], $until);
            }
            
            // Delete future occurrences (starting from this booking's time if it's an instance)
            $fromDate = $this->isRecurringInstance() ? $this->start_time_utc : now()->setTimezone('UTC');
            $parentBooking->deleteFutureRecurringBookings($fromDate);
            
            // Regenerate future occurrences
            $limit = $recurringOptions['limit'] ?? 10;
            $maxDate = $recurringOptions['max_date'] ?? null;
            $updatedBookings = $parentBooking->createRecurringBookings($limit, $maxDate);
            
            DB::commit();
            
            // Return all updated bookings including the parent
            return new EloquentCollection([$parentBooking, ...$updatedBookings]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 