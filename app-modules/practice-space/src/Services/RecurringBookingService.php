<?php

namespace CorvMC\PracticeSpace\Services;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use Illuminate\Support\Collection;
use \Recurr\Rule;
use \Recurr\Frequency;
use \Recurr\Transformer\ArrayTransformer;

class RecurringBookingService
{
    /**
     * Create a recurring booking with a specified repeat pattern
     *
     * @param array $bookingData Base booking data
     * @param string $frequency Frequency constant (daily, weekly, monthly, etc.)
     * @param array $options Additional options for the recurring rule
     * @return \CorvMC\PracticeSpace\Models\Booking
     */
    public function createRecurringBooking(array $bookingData, string $frequency, array $options = []): Booking
    {
        // Create the base parent booking
        $booking = Booking::create($bookingData);
        
        // Generate the RRULE and set it on the booking
        $rule = $this->buildRRule($booking, $frequency, $options);
        $booking->setRecurringRule($rule, $options['until'] ?? null);
        
        // Generate recurring bookings based on the rule
        $limit = $options['limit'] ?? 10;
        $maxDate = $options['max_date'] ?? null;
        $booking->createRecurringBookings($limit, $maxDate);
        
        return $booking;
    }
    
    /**
     * Build an RRULE string based on booking and frequency
     *
     * @param \CorvMC\PracticeSpace\Models\Booking $booking
     * @param string $frequency One of: daily, weekly, monthly, yearly
     * @param array $options Additional options for the rule
     * @return string RFC 5545 compliant RRULE string
     */
    public function buildRRule(Booking $booking, string $frequency, array $options = []): string
    {
        // Map our API-friendly frequency to Recurr constants
        $frequencyMap = [
            'daily' => Frequency::DAILY,
            'weekly' => Frequency::WEEKLY,
            'bi-weekly' => Frequency::WEEKLY,
            'monthly' => Frequency::MONTHLY,
            'yearly' => Frequency::YEARLY,
        ];
        
        $recurFrequency = $frequencyMap[$frequency] ?? Frequency::WEEKLY;
        
        // Start building the rule
        $rule = new Rule();
        $rule->setFreq($recurFrequency);
        
        // Set interval (e.g., every 2 weeks for bi-weekly)
        if ($frequency === 'bi-weekly') {
            $rule->setInterval(2);
        } else {
            $rule->setInterval($options['interval'] ?? 1);
        }
        
        // Set start date from booking
        $rule->setStartDate($booking->start_time_utc->toDateTime());
        
        // Set specific weekdays for weekly recurrence
        if ($recurFrequency === Frequency::WEEKLY && !empty($options['weekdays'])) {
            $rule->setByDay($options['weekdays']);
        }
        
        // Set specific days of month for monthly recurrence
        if ($recurFrequency === Frequency::MONTHLY && !empty($options['days'])) {
            $rule->setByMonthDay($options['days']);
        }
        
        // Set end conditions
        if (!empty($options['count'])) {
            $rule->setCount($options['count']);
        } elseif (!empty($options['until'])) {
            $untilDate = $options['until'] instanceof Carbon 
                ? $options['until']->toDateTime() 
                : Carbon::parse($options['until'])->toDateTime();
            $rule->setUntil($untilDate);
        }
        
        // Generate the RRULE string
        return $rule->getString();
    }
    
    /**
     * Find all upcoming recurring bookings for a given user
     *
     * @param int $userId
     * @param \Carbon\Carbon|null $fromDate Starting from this date (defaults to now)
     * @return \Illuminate\Support\Collection
     */
    public function getUpcomingRecurringBookingsForUser(int $userId, ?Carbon $fromDate = null): Collection
    {
        $fromDate = $fromDate ?? now();
        
        // Get all recurring parent bookings
        $parentBookings = Booking::where('user_id', $userId)
            ->where('is_recurring_parent', true)
            ->where(function($query) use ($fromDate) {
                // Where the parent booking itself is in the future
                $query->where('start_time', '>=', $fromDate)
                    // Or the recurrence end date is in the future (meaning some instances might be in the future)
                    ->orWhere('recurrence_end_date', '>=', $fromDate)
                    // Or there's no end date set yet
                    ->orWhereNull('recurrence_end_date');
            })
            ->get();
            
        // Get all instances of recurring bookings
        $recurringInstances = Booking::where('user_id', $userId)
            ->where('is_recurring', true)
            ->whereNotNull('recurring_booking_id')
            ->where('start_time', '>=', $fromDate)
            ->get();
            
        return $parentBookings->merge($recurringInstances);
    }
    
    /**
     * Generate preview of recurring dates without creating bookings
     *
     * @param \CorvMC\PracticeSpace\Models\Booking $booking
     * @param string $frequency
     * @param array $options
     * @param int $limit
     * @return array Array of start_time and end_time for each occurrence
     */
    public function previewRecurringDates(Booking $booking, string $frequency, array $options = [], int $limit = 10): array
    {
        // Build the RRULE
        $ruleString = $this->buildRRule($booking, $frequency, $options);
        
        // Create a temporary rule object
        $tempRule = new Rule($ruleString, $booking->start_time_utc->toDateTime());
        
        // Generate occurrences with virtual end dates
        $transformer = new \Recurr\Transformer\ArrayTransformer();
        $occurrenceCollection = $transformer->transform($tempRule, null, true);
        
        // Convert to array and limit the number of occurrences
        $occurrences = iterator_to_array($occurrenceCollection);
        $occurrences = array_slice($occurrences, 0, $limit);
        
        // Format the results
        $result = [];
        foreach ($occurrences as $occurrence) {
            $result[] = [
                'start_time' => Carbon::instance($occurrence->getStart())->setTimezone($booking->getRoomTimezone()),
                'end_time' => Carbon::instance($occurrence->getEnd())->setTimezone($booking->getRoomTimezone()),
            ];
        }
        
        return $result;
    }
    
    /**
     * Check if any of the recurring dates would conflict with existing bookings
     *
     * @param \CorvMC\PracticeSpace\Models\Room $room
     * @param \Carbon\Carbon $startTime
     * @param \Carbon\Carbon $endTime
     * @param string $frequency
     * @param array $options
     * @param int $limit
     * @return array Array of conflicts with date and existing booking details
     */
    public function checkRecurringDateConflicts(Room $room, Carbon $startTime, Carbon $endTime, string $frequency, array $options = [], int $limit = 10): array
    {
        // Create a temporary booking object to generate the preview
        $tempBooking = new Booking([
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        
        // Get the preview dates
        $previewDates = $this->previewRecurringDates($tempBooking, $frequency, $options, $limit);
        
        $conflicts = [];
        
        foreach ($previewDates as $date) {
            // Check for conflicts with existing bookings
            $conflictingBookings = $room->bookingsIntersecting(
                $date['start_time'],
                $date['end_time']
            )->get();
            
            if ($conflictingBookings->count() > 0) {
                $conflicts[] = [
                    'date' => $date,
                    'conflicting_bookings' => $conflictingBookings->map(function($booking) {
                        return [
                            'id' => $booking->id,
                            'start_time' => $booking->start_time,
                            'end_time' => $booking->end_time,
                            'user_id' => $booking->user_id,
                            'user_name' => $booking->user->name ?? 'Unknown',
                        ];
                    }),
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Update a recurring booking and all of its future instances
     *
     * @param \CorvMC\PracticeSpace\Models\Booking $booking
     * @param array $attributes
     * @param bool $updateFutureOnly Update only this and future instances
     * @param array $recurringOptions
     * @return \Illuminate\Support\Collection
     */
    public function updateRecurringBooking(Booking $booking, array $attributes, bool $updateFutureOnly = false, array $recurringOptions = []): Collection
    {
        // If this is a single instance or we're updating all instances
        if (!$booking->is_recurring || !$updateFutureOnly) {
            return $booking->updateWithFutureRecurrences($attributes, $recurringOptions);
        }
        
        // If this is a recurring instance and we're only updating future instances
        // Find the parent
        $parentBooking = $booking->recurringParent;
        
        if (!$parentBooking) {
            // Just update this booking if there's no parent
            $booking->update($attributes);
            return collect([$booking]);
        }
        
        // Create a new RRULE that starts from this instance
        $frequency = $recurringOptions['frequency'] ?? 'weekly';
        $options = $recurringOptions;
        
        // Set the start date to this instance's date
        $options['start_date'] = $booking->start_time;
        
        // Create a new parent booking based on this instance
        $newParentData = array_merge($booking->toArray(), $attributes);
        $newParentData['is_recurring_parent'] = true;
        $newParentData['recurring_booking_id'] = null;
        
        // Create the new parent with the new pattern
        return collect($this->createRecurringBooking($newParentData, $frequency, $options));
    }
    
    /**
     * Cancel a recurring booking and all of its future instances
     *
     * @param \CorvMC\PracticeSpace\Models\Booking $booking
     * @param string $reason
     * @param bool $cancelFutureOnly Cancel only this and future instances
     * @return int Number of cancelled bookings
     */
    public function cancelRecurringBooking(Booking $booking, string $reason, bool $cancelFutureOnly = false): int
    {
        // If this isn't a recurring booking or parent, just cancel this booking
        if (!$booking->is_recurring) {
            $booking->cancel($reason);
            return 1;
        }
        
        $count = 0;
        
        // If this is a parent booking or we're cancelling all instances
        if ($booking->is_recurring_parent && !$cancelFutureOnly) {
            // Cancel all instances
            foreach ($booking->recurringBookings as $instance) {
                $instance->cancel($reason);
                $count++;
            }
            
            // Cancel the parent
            $booking->cancel($reason);
            $count++;
            
            return $count;
        }
        
        // If this is an instance and we're only cancelling future instances
        if ($booking->isRecurringInstance() && $cancelFutureOnly) {
            $parentBooking = $booking->recurringParent;
            
            if ($parentBooking) {
                // Find all future instances
                $futureInstances = Booking::where('recurring_booking_id', $parentBooking->id)
                    ->where('start_time', '>=', $booking->start_time)
                    ->get();
                    
                // Cancel future instances
                foreach ($futureInstances as $instance) {
                    $instance->cancel($reason);
                    $count++;
                }
                
                // Update the parent's recurrence end date
                $parentBooking->recurrence_end_date = $booking->start_time;
                $parentBooking->save();
            }
            
            // Cancel this instance
            $booking->cancel($reason);
            $count++;
            
            return $count;
        }
        
        // Default case: just cancel this booking
        $booking->cancel($reason);
        return 1;
    }
} 