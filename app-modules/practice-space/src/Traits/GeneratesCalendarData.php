<?php

namespace CorvMC\PracticeSpace\Traits;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Room;

trait GeneratesCalendarData
{
    /**
     * Generate cell data for the calendar grid
     * 
     * @param Room $room The room to generate data for
     * @param Carbon $startDate The start date of the calendar
     * @param Carbon $endDate The end date of the calendar
     * @param array $bookings Optional array of processed bookings to use
     * @return array
     */
    public function generateCalendarCellData(Room $room, Carbon $startDate, Carbon $endDate, array $bookings = []): array
    {
        // Early returns for invalid states
        if (!$room) {
            return [];
        }
        
        $cellData = [];
        $bookingPolicy = $room->booking_policy;
        $minBookingDurationMinutes = $bookingPolicy->minBookingDurationHours * 60;
        $slotLengthMinutes = 30;
        $minSlotsNeeded = ceil($minBookingDurationMinutes / $slotLengthMinutes);
        $timezone = $room->timezone;
        
        // IMPORTANT: Ensure we're working with the current time in the room's timezone
        $now = Carbon::now($timezone);
        $minAdvanceBookingThreshold = $now->copy()->addHours($bookingPolicy?->minAdvanceBookingHours ?? 0);
        
        // Pre-process bookings by day for faster access
        $bookingsByDay = [];
        foreach ($bookings as $booking) {
            $dayIndex = $booking['date_index'];
            if (!isset($bookingsByDay[$dayIndex])) {
                $bookingsByDay[$dayIndex] = [];
            }
            $bookingsByDay[$dayIndex][] = $booking;
        }
        
        // Loop through each day in the date range
        $dayIndex = 0;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            
            // Get the opening and closing time for this date
            $openingTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateString . ' ' . $room->booking_policy->openingTime . ':00', $timezone);
            $closingTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateString . ' ' . $room->booking_policy->closingTime . ':00', $timezone);
            
            // Initialize the cell data for this day
            if (!isset($cellData[$dayIndex])) {
                $cellData[$dayIndex] = [];
            }
            
            // Determine if the date is today or in the past
            $isPastDate = $date->lt($now);
            $isToday = $date->isSameDay($now);
            
            // Calculate how many slots we need (from opening to closing time)
            $minutesInDay = $openingTime->diffInMinutes($closingTime);
            // Calculate number of complete slots, plus one for any partial time remaining
            $slotsInDay = ceil($minutesInDay / $slotLengthMinutes);
            
            // Fill in data for each time slot using standard 30-minute increments
            for ($slotIndex = 0; $slotIndex < $slotsInDay; $slotIndex++) {
                $slotTime = $openingTime->copy()->addMinutes($slotIndex * $slotLengthMinutes);
                
                // Skip slots that are at or past closing time
                if ($slotTime->gte($closingTime)) {
                    continue;
                }
                
                
                $cell = [
                    'date' => $dateString,
                    'time' => $slotTime->format('H:i'),
                    'slot_index' => $slotIndex,
                    'booking_id' => null,
                    'is_current_user_booking' => false,
                    'invalid_duration' => false,
                    'invalid_reason' => null,
                    'room_id' => $room->id,
                ];
                
                // Create a full DateTime for this slot
                $slotDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s', 
                    $dateString . ' ' . $slotTime->format('H:i:s'), 
                    $timezone
                );
                
                // Mark cells in the past
                if ($slotDateTime->lt($now)) {
                    $cell['invalid_duration'] = true;
                    $cell['invalid_reason'] = 'past';
                }
                
                // Mark cells too close to current time per booking policy
                else if ($isToday && $minAdvanceBookingThreshold && $slotDateTime->lt($minAdvanceBookingThreshold)) {
                    $cell['invalid_duration'] = true;
                    $cell['invalid_reason'] = 'advance_notice';
                }
                
                // Mark cells too close to closing time based on remaining time
                else if ($slotDateTime->diffInMinutes($closingTime) < $minBookingDurationMinutes) {
                    $cell['invalid_duration'] = true;
                    $cell['invalid_reason'] = 'closing_time';
                }
                
                $cellData[$dayIndex][$slotIndex] = $cell;
            }
            $dayIndex++;
        }
        
        // Apply bookings and minimum duration restrictions
        foreach ($bookings as $booking) {
            $bookingStartSlot = $booking['time_index'];

            // Mark slots before the booking
            $slotsBeforeBooking = $bookingStartSlot - $minSlotsNeeded;
            for ($i = $slotsBeforeBooking; $i < $bookingStartSlot; $i++) {
                $slotIndex = $bookingStartSlot + $i;
                $cell = $cellData[$booking['date_index']][$slotIndex];
                $cell['invalid_duration'] = true;
                $cell['invalid_reason'] = 'adjacent_booking';
            }

            // Mark booked slots
            for ($i = 0; $i < $booking['slots']; $i++) {
                $slotIndex = $bookingStartSlot + $i;
                $cell = $cellData[$booking['date_index']][$slotIndex];
                $cell['booking_id'] = $booking['id'];
                $cell['is_current_user_booking'] = $booking['is_current_user'];
                $cell['invalid_duration'] = true;
                $cell['invalid_reason'] = 'booking';
            }
            
        }
        
        return $cellData;
    }
    

    /**
     * Check if a time slot is in the past
     * 
     * @param string $date Date in Y-m-d format
     * @param string $time Time in H:i format
     * @param string $timezone Timezone to use for comparison
     * @return bool
     */
    public function isTimeSlotInPastByTimezone(string $date, string $time, string $timezone): bool
    {
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', "$date $time", $timezone);
        $now = Carbon::now($timezone);
        
        return $dateTime->lt($now);
    }
    
    /**
     * Legacy method for backward compatibility
     * 
     * @param string $date Date in Y-m-d format
     * @param string $time Time in H:i format
     * @param string $timezone Timezone to use for comparison
     * @return bool
     */
    public function isTimeSlotInPast(string $date, string $time, string $timezone): bool
    {
        return $this->isTimeSlotInPastByTimezone($date, $time, $timezone);
    }
} 