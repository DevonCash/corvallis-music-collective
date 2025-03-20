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
        $minSlotsNeeded = ceil($minBookingDurationMinutes / 30);
        $timezone = $room->timezone;
        
        // IMPORTANT: Ensure we're working with the current time in the room's timezone
        $now = Carbon::now($timezone);
        $minAdvanceBookingThreshold = null;
        
        // Pre-calculate advance booking threshold if needed
        if ($bookingPolicy->minAdvanceBookingHours > 0) {
            // Create the threshold directly in the room's timezone
            $minAdvanceBookingThreshold = $now->copy()->addHours($bookingPolicy->minAdvanceBookingHours);
        }
        
        // Pre-process bookings by day for faster access
        $bookingsByDay = [];
        foreach ($bookings as $booking) {
            $dayIndex = $booking['date_index'];
            if (!isset($bookingsByDay[$dayIndex])) {
                $bookingsByDay[$dayIndex] = [];
            }
            $bookingsByDay[$dayIndex][] = $booking;
        }
        
        // Generate cell data for each day and time slot
        $daysInRange = $startDate->diffInDays($endDate) + 1;
        
        // Ensure normalized date ranges in the room's timezone
        $startDateInRoomTz = $startDate->copy()->setTimezone($timezone)->startOfDay();
        
        for ($dayIndex = 0; $dayIndex < $daysInRange; $dayIndex++) {
            $date = $startDateInRoomTz->copy()->addDays($dayIndex);
            $dateString = $date->format('Y-m-d');
            
            // Check if this date is in the past
            $isPastDate = $date->lt(Carbon::now($timezone)->startOfDay());
            
            // Check if this date is today
            $isToday = $date->isSameDay(Carbon::now($timezone));
            
            // Get booking policy for this day
            $openingTime = $bookingPolicy->getOpeningTime($dateString, $timezone);
            $closingTime = $bookingPolicy->getClosingTime($dateString, $timezone);
            
            // Calculate how many slots we need (from opening to closing time)
            $minutesInDay = $openingTime->diffInMinutes($closingTime);
            $slotsInDay = ceil($minutesInDay / 30);
            
            // Calculate index of slot that is too close to closing for minimum booking duration
            $closeToClosingTime = $slotsInDay - $minSlotsNeeded;
            
            // Fill in data for each time slot
            for ($slotIndex = 0; $slotIndex < $slotsInDay; $slotIndex++) {
                $slotTime = $openingTime->copy()->addMinutes($slotIndex * 30);
                
                $cell = [
                    'date' => $dateString,
                    'time' => $slotTime->format('H:i'),
                    'slot_index' => $slotIndex,
                    'booking_id' => null,
                    'is_current_user_booking' => false,
                    'invalid_duration' => false,
                    'room_id' => $room->id,
                ];
                
                // Create a full DateTime for this slot
                $slotDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s', 
                    $dateString . ' ' . $slotTime->format('H:i:s'), 
                    $timezone
                );
                
                // Mark cells in the past
                if ($isPastDate || ($isToday && $slotDateTime->lt($now))) {
                    $cell['invalid_duration'] = true;
                }
                
                // Mark cells too close to current time per booking policy
                if ($isToday && $minAdvanceBookingThreshold && $slotDateTime->lt($minAdvanceBookingThreshold)) {
                    $cell['invalid_duration'] = true;
                }
                
                // Mark cells too close to closing time
                if ($slotIndex >= $closeToClosingTime) {
                    $cell['invalid_duration'] = true;
                }
                
                $cellData[$dayIndex][$slotIndex] = $cell;
            }
        }
        
        // Apply bookings and minimum duration restrictions
        foreach ($bookingsByDay as $dayIndex => $dayBookings) {
            foreach ($dayBookings as $booking) {
                $bookingStartSlot = $booking['time_index'];
                $bookingEndSlot = $booking['time_index'] + $booking['slots'] - 1;
                
                // Mark booked slots
                for ($i = 0; $i < $booking['slots']; $i++) {
                    $slotIndex = $bookingStartSlot + $i;
                    if (isset($cellData[$dayIndex][$slotIndex])) {
                        $cellData[$dayIndex][$slotIndex]['booking_id'] = $booking['id'];
                        $cellData[$dayIndex][$slotIndex]['is_current_user_booking'] = $booking['is_current_user'];
                    }
                }
                
                // Mark slots with insufficient time before this booking
                for ($i = max(0, $bookingStartSlot - $minSlotsNeeded + 1); $i < $bookingStartSlot; $i++) {
                    if (isset($cellData[$dayIndex][$i]) && !$cellData[$dayIndex][$i]['booking_id']) {
                        $cellData[$dayIndex][$i]['invalid_duration'] = true;
                    }
                }
                
                // Mark slots with insufficient time after this booking
                for ($i = $bookingEndSlot + 1; $i < $bookingEndSlot + $minSlotsNeeded; $i++) {
                    if (isset($cellData[$dayIndex][$i]) && !$cellData[$dayIndex][$i]['booking_id']) {
                        $cellData[$dayIndex][$i]['invalid_duration'] = true;
                    }
                }
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