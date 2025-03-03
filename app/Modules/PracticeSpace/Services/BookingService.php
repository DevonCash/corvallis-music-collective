<?php

namespace App\Modules\PracticeSpace\Services;

use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class BookingService {
    /**
     * Get available booking durations (in hours) for a specific date and room
     * 
     * @param string|Carbon $date The date to check
     * @param int $roomId The room ID
     * @return array Array of available durations (1 hour, 2 hours, etc.)
     */
    public static function getAvailableDurations($date, $roomId) {
        if (!$date || !$roomId) {
            return [];
        }
        
        $date = Carbon::parse($date);
        $room = Room::find($roomId);
        
        if (!$room) {
            return [];
        }
        
        // Get room hours for the specific day of week
        $dayOfWeek = strtolower($date->format('l'));
        $roomHours = $room->hours[$dayOfWeek] ?? null;
        
        if (!$roomHours || !isset($roomHours['open']) || !isset($roomHours['close'])) {
            return []; // Room not available this day
        }
        
        $openTime = Carbon::parse($roomHours['open'], 'UTC')->setDateFrom($date);
        $closeTime = Carbon::parse($roomHours['close'], 'UTC')->setDateFrom($date);
        
        // If close time is earlier than open time, it means it closes the next day
        if ($closeTime->lt($openTime)) {
            $closeTime->addDay();
        }
        
        $maxHoursAvailable = $closeTime->diffInHours($openTime);
        
        // Get existing bookings for this room and date
        $existingBookings = Booking::where('room_id', $roomId)
            ->whereDate('start_time', $date)
            ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
            ->orderBy('start_time')
            ->get();
            
        // Calculate longest continuous available time slot
        $availableSlots = self::calculateAvailableTimeSlots($openTime, $closeTime, $existingBookings);
        $longestSlot = $availableSlots->max('duration');
        
        // Create a range of available durations from 1 hour to the longest available slot
        $durations = [];
        for ($i = 1; $i <= min($longestSlot, $maxHoursAvailable); $i++) {
            $durations[$i] = $i . ' ' . ($i == 1 ? 'hour' : 'hours');
        }
        
        return $durations;
    }

    /**
     * Get available start times for a booking given room, date, and duration
     * 
     * @param int $roomId The room ID
     * @param string|Carbon $date The booking date
     * @param int $duration The booking duration in hours
     * @return array Array of available start times
     */
    public static function getAvailableTimes($roomId, $date, $duration) {
        if (!$roomId || !$date || !$duration) {
            return [];
        }
        
        $date = Carbon::parse($date);
        $room = Room::find($roomId);
        $duration = (int)$duration;
        
        if (!$room || $duration <= 0) {
            return [];
        }
        
        // Get room hours for this day
        $dayOfWeek = strtolower($date->format('l'));
        $roomHours = $room->hours[$dayOfWeek] ?? null;
        
        if (!$roomHours || !isset($roomHours['open']) || !isset($roomHours['close'])) {
            return []; // Room not available this day
        }
        
        $openTime = Carbon::parse($roomHours['open'], 'UTC')->setDateFrom($date);
        $closeTime = Carbon::parse($roomHours['close'], 'UTC')->setDateFrom($date);
        
        // If close time is earlier than open time, it means it closes the next day
        if ($closeTime->lt($openTime)) {
            $closeTime->addDay();
        }
        
        // Get existing bookings for this room and date
        $existingBookings = Booking::where('room_id', $roomId)
            ->whereDate('start_time', $date)
            ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
            ->orderBy('start_time')
            ->get();
            
        // Get all available time slots
        $availableSlots = self::calculateAvailableTimeSlots($openTime, $closeTime, $existingBookings);
        
        // Filter slots that can accommodate the requested duration
        $availableSlots = $availableSlots->filter(function ($slot) use ($duration) {
            return $slot['duration'] >= $duration;
        });
        
        // Generate possible start times at 30-minute intervals
        $availableTimes = [];
        foreach ($availableSlots as $slot) {
            $current = clone $slot['start'];
            $endTime = $slot['start']->copy()->addHours($duration);
            
            // Make sure the end time doesn't exceed the slot's end time
            while ($endTime->lte($slot['end'])) {
                $timeKey = $current->format('H:i');
                $timeValue = $current->format('g:i A');
                $availableTimes[$timeKey] = $timeValue;
                
                // Move to next 30-minute interval
                $current->addMinutes(30);
                $endTime = $current->copy()->addHours($duration);
            }
        }
        
        return $availableTimes;
    }

    /**
     * Get unavailable dates for a specific room
     * 
     * @param int $roomId The room ID
     * @return array Array of unavailable dates
     */
    public static function getUnavailableDates($roomId) {
        if (!$roomId) {
            return [];
        }
        
        $room = Room::find($roomId);
        
        if (!$room) {
            return [];
        }
        
        $unavailableDates = [];
        
        // Get dates for next 60 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(60);
        
        // Create a period of dates to check
        $datePeriod = CarbonPeriod::create($startDate, $endDate);
        
        // Check each date
        foreach ($datePeriod as $date) {
            $dayOfWeek = strtolower($date->format('l'));
            
            // Check if room is closed on this day of week
            if (!isset($room->hours[$dayOfWeek]) || 
                !isset($room->hours[$dayOfWeek]['open']) || 
                !isset($room->hours[$dayOfWeek]['close'])) {
                $unavailableDates[] = $date->format('Y-m-d');
                continue;
            }
            
            // Check if fully booked
            $openTime = Carbon::parse($room->hours[$dayOfWeek]['open'], 'UTC')->setDateFrom($date);
            $closeTime = Carbon::parse($room->hours[$dayOfWeek]['close'], 'UTC')->setDateFrom($date);
            
            // If close time is earlier than open time, it means it closes the next day
            if ($closeTime->lt($openTime)) {
                $closeTime->addDay();
            }
            
            // Get existing bookings for this date
            $existingBookings = Booking::where('room_id', $roomId)
                ->whereDate('start_time', $date)
                ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
                ->orderBy('start_time')
                ->get();
                
            // Calculate available time slots
            $availableSlots = self::calculateAvailableTimeSlots($openTime, $closeTime, $existingBookings);
            
            // If no slots available with at least 1 hour, date is unavailable
            if ($availableSlots->isEmpty() || $availableSlots->max('duration') < 1) {
                $unavailableDates[] = $date->format('Y-m-d');
            }
        }
        
        return $unavailableDates;
    }
    
    /**
     * Helper method to calculate available time slots between bookings
     * 
     * @param Carbon $openTime Room opening time
     * @param Carbon $closeTime Room closing time
     * @param Collection $bookings Existing bookings
     * @return Collection Collection of available time slots with duration
     */
    private static function calculateAvailableTimeSlots(Carbon $openTime, Carbon $closeTime, Collection $bookings) {
        $availableSlots = collect();
        
        // If no bookings, entire time slot is available
        if ($bookings->isEmpty()) {
            $availableSlots->push([
                'start' => $openTime,
                'end' => $closeTime,
                'duration' => $openTime->diffInHours($closeTime)
            ]);
            
            return $availableSlots;
        }
        
        // Check if there's available time before first booking
        $firstBooking = $bookings->first();
        if ($openTime->lt($firstBooking->start_time)) {
            $availableSlots->push([
                'start' => $openTime,
                'end' => $firstBooking->start_time,
                'duration' => $openTime->diffInHours($firstBooking->start_time)
            ]);
        }
        
        // Check gaps between bookings
        for ($i = 0; $i < $bookings->count() - 1; $i++) {
            $currentBooking = $bookings[$i];
            $nextBooking = $bookings[$i + 1];
            
            if ($currentBooking->end_time->lt($nextBooking->start_time)) {
                $availableSlots->push([
                    'start' => $currentBooking->end_time,
                    'end' => $nextBooking->start_time,
                    'duration' => $currentBooking->end_time->diffInHours($nextBooking->start_time)
                ]);
            }
        }
        
        // Check if there's available time after last booking
        $lastBooking = $bookings->last();
        if ($lastBooking->end_time->lt($closeTime)) {
            $availableSlots->push([
                'start' => $lastBooking->end_time,
                'end' => $closeTime,
                'duration' => $lastBooking->end_time->diffInHours($closeTime)
            ]);
        }
        
        return $availableSlots;
    }

    /**
     * Get all available start times for a specific date, regardless of duration
     * 
     * @param int $roomId The room ID
     * @param string|Carbon $date The date to check
     * @return array Available start times
     */
    public static function getAllAvailableTimesForDate($roomId, $date) {
        if (!$roomId || !$date) {
            return [];
        }
        
        $date = Carbon::parse($date);
        $room = Room::find($roomId);
        
        if (!$room) {
            return [];
        }
        
        // Get room hours for this day
        $dayOfWeek = strtolower($date->format('l'));
        $roomHours = $room->hours[$dayOfWeek] ?? null;
        
        if (!$roomHours || !isset($roomHours['open']) || !isset($roomHours['close'])) {
            return []; // Room not available this day
        }
        
        $openTime = Carbon::parse($roomHours['open'], 'UTC')->setDateFrom($date);
        $closeTime = Carbon::parse($roomHours['close'], 'UTC')->setDateFrom($date);
        
        // If close time is earlier than open time, it means it closes the next day
        if ($closeTime->lt($openTime)) {
            $closeTime->addDay();
        }
        
        // Get existing bookings for this room and date
        $existingBookings = Booking::where('room_id', $roomId)
            ->whereDate('start_time', $date)
            ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
            ->orderBy('start_time')
            ->get();
            
        // Get all available time slots
        $availableSlots = self::calculateAvailableTimeSlots($openTime, $closeTime, $existingBookings);
        
        // Generate all possible start times at 30-minute intervals, even for short slots
        $availableTimes = [];
        foreach ($availableSlots as $slot) {
            $current = clone $slot['start'];
            
            // Add all time slots, even if they can only accommodate short durations
            while ($current->lt($slot['end'])) {
                $timeKey = $current->format('H:i');
                $timeValue = $current->format('g:i A');
                $availableTimes[$timeKey] = $timeValue . ' (' . 
                    number_format($slot['end']->diffInHours($current, true), 1) . ' hrs max)';
                
                // Move to next 30-minute interval
                $current->addMinutes(30);
            }
        }
        
        return $availableTimes;
    }

    /**
     * Get available durations for a specific date and time
     * 
     * @param int $roomId The room ID
     * @param string|Carbon $date The date
     * @param string $startTime The start time in HH:MM format
     * @return array Available durations
     */
    public static function getAvailableDurationsForDateTime($roomId, $date, $startTime) {
        if (!$roomId || !$date || !$startTime) {
            return [];
        }
        
        $date = Carbon::parse($date);
        $room = Room::find($roomId);
        
        if (!$room) {
            return [];
        }
        
        // Parse the start time
        list($hour, $minute) = explode(':', $startTime);
        $startDateTime = $date->copy()->setTime((int)$hour, (int)$minute);
        
        // Get room hours for this day
        $dayOfWeek = strtolower($date->format('l'));
        $roomHours = $room->hours[$dayOfWeek] ?? null;
        
        if (!$roomHours || !isset($roomHours['open']) || !isset($roomHours['close'])) {
            return []; // Room not available this day
        }
        
        $openTime = Carbon::parse($roomHours['open'], 'UTC')->setDateFrom($date);
        $closeTime = Carbon::parse($roomHours['close'], 'UTC')->setDateFrom($date);
        
        // If close time is earlier than open time, it means it closes the next day
        if ($closeTime->lt($openTime)) {
            $closeTime->addDay();
        }
        
        // Check if the start time is within operating hours
        if ($startDateTime->lt($openTime) || $startDateTime->gte($closeTime)) {
            return []; // Start time outside of operating hours
        }
        
        // Get existing bookings for this room and date
        $existingBookings = Booking::where('room_id', $roomId)
            ->whereDate('start_time', $date)
            ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
            ->orderBy('start_time')
            ->get();
        
        // Find the next booking after the requested start time
        $nextBooking = $existingBookings->first(function($booking) use ($startDateTime) {
            return $booking->start_time->gt($startDateTime);
        });
        
        // Calculate max possible duration
        $endLimit = $nextBooking ? $nextBooking->start_time : $closeTime;
        $maxDuration = $startDateTime->diffInHours($endLimit);
        
        // Create duration options
        $durations = [];
        for ($i = 1; $i <= floor($maxDuration); $i++) {
            $durations[$i] = $i . ' ' . ($i == 1 ? 'hour' : 'hours');
        }
        
        return $durations;
    }

    /**
     * Get standard time options for a room (e.g., for when no date is selected)
     * 
     * @param int $roomId The room ID
     * @return array Standard time options
     */
    public static function getStandardTimeOptions($roomId) {
        if (!$roomId) {
            return [];
        }
        
        $room = Room::find($roomId);
        
        if (!$room) {
            return [];
        }
        
        // Get the common operating hours across the week
        $commonHours = [];
        $earliest = '23:59';
        $latest = '00:00';
        
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if (isset($room->hours[$day]) && isset($room->hours[$day]['open']) && isset($room->hours[$day]['close'])) {
                $open = $room->hours[$day]['open'];
                $close = $room->hours[$day]['close'];
                
                // Track earliest opening and latest closing
                if ($open < $earliest) {
                    $earliest = $open;
                }
                
                // For closing time, we need to handle cases that close after midnight
                if ($close < $open) {
                    $close = '23:59'; // We'll just use 11:59 PM as the end of day
                }
                
                if ($close > $latest) {
                    $latest = $close;
                }
            }
        }
        
        // Generate standard time options from earliest to latest at 30-minute intervals
        $timeOptions = [];
        
        if ($earliest != '23:59' && $latest != '00:00') {
            $current = Carbon::parse($earliest);
            $end = Carbon::parse($latest);
            
            while ($current <= $end) {
                $timeKey = $current->format('H:i');
                $timeValue = $current->format('g:i A');
                $timeOptions[$timeKey] = $timeValue;
                $current->addMinutes(30);
            }
        }
        
        return $timeOptions;
    }

    /**
     * Get available times for a specific duration, across all available dates
     * 
     * @param int $roomId The room ID
     * @param int $duration The duration in hours
     * @return array Available times
     */
    public static function getAvailableTimesForDuration($roomId, $duration) {
        if (!$roomId || !$duration) {
            return [];
        }
        
        // Just return standard time options with a note that date selection is required
        $standardTimes = self::getStandardTimeOptions($roomId);
        $result = [];
        
        foreach ($standardTimes as $key => $value) {
            $result[$key] = $value . ' (select date)';
        }
        
        return $result;
    }

    /**
     * Get available durations for a specific time across all dates
     * 
     * @param int $roomId The room ID
     * @param string $startTime The start time in HH:MM format
     * @return array Available durations
     */
    public static function getAvailableDurationsForTime($roomId, $startTime) {
        if (!$roomId || !$startTime) {
            return [];
        }
        
        // Return standard durations with a note that date selection is required
        return [
            1 => '1 hour (select date)',
            2 => '2 hours (select date)',
            3 => '3 hours (select date)',
            4 => '4 hours (select date)',
        ];
    }

    /**
     * Get unavailable dates for a specific duration
     * 
     * @param int $roomId The room ID
     * @param int $duration The duration in hours
     * @return array Unavailable dates
     */
    public static function getUnavailableDatesForDuration($roomId, $duration) {
        if (!$roomId || !$duration) {
            return [];
        }
        
        $room = Room::find($roomId);
        $duration = (int)$duration;
        
        if (!$room || $duration <= 0) {
            return [];
        }
        
        $unavailableDates = [];
        
        // Get dates for next 60 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(60);
        
        // Create a period of dates to check
        $datePeriod = CarbonPeriod::create($startDate, $endDate);
        
        // Check each date
        foreach ($datePeriod as $date) {
            $dayOfWeek = strtolower($date->format('l'));
            
            // Check if room is closed on this day of week
            if (!isset($room->hours[$dayOfWeek]) || 
                !isset($room->hours[$dayOfWeek]['open']) || 
                !isset($room->hours[$dayOfWeek]['close'])) {
                $unavailableDates[] = $date->format('Y-m-d');
                continue;
            }
            
            // Check if there's any slot available for the requested duration
            $openTime = Carbon::parse($room->hours[$dayOfWeek]['open'], 'UTC')->setDateFrom($date);
            $closeTime = Carbon::parse($room->hours[$dayOfWeek]['close'], 'UTC')->setDateFrom($date);
            
            // If close time is earlier than open time, it means it closes the next day
            if ($closeTime->lt($openTime)) {
                $closeTime->addDay();
            }
            
            // Get existing bookings for this date
            $existingBookings = Booking::where('room_id', $roomId)
                ->whereDate('start_time', $date)
                ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
                ->orderBy('start_time')
                ->get();
                
            // Calculate available time slots
            $availableSlots = self::calculateAvailableTimeSlots($openTime, $closeTime, $existingBookings);
            
            // Filter slots that can accommodate the requested duration
            $availableSlots = $availableSlots->filter(function ($slot) use ($duration) {
                return $slot['duration'] >= $duration;
            });
            
            // If no slots available for the requested duration, date is unavailable
            if ($availableSlots->isEmpty()) {
                $unavailableDates[] = $date->format('Y-m-d');
            }
        }
        
        return $unavailableDates;
    }

    /**
     * Get unavailable dates for a specific time and duration
     * 
     * @param int $roomId The room ID
     * @param string $startTime The start time in HH:MM format
     * @param int $duration The duration in hours
     * @return array Unavailable dates
     */
    public static function getUnavailableDatesForTimeAndDuration($roomId, $startTime, $duration) {
        if (!$roomId || !$startTime || !$duration) {
            return [];
        }
        
        $room = Room::find($roomId);
        $duration = (int)$duration;
        
        if (!$room || $duration <= 0) {
            return [];
        }
        
        $unavailableDates = [];
        
        // Parse the start time
        list($hour, $minute) = explode(':', $startTime);
        
        // Get dates for next 60 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(60);
        
        // Create a period of dates to check
        $datePeriod = CarbonPeriod::create($startDate, $endDate);
        
        // Check each date
        foreach ($datePeriod as $date) {
            $dayOfWeek = strtolower($date->format('l'));
            
            // Check if room is closed on this day of week
            if (!isset($room->hours[$dayOfWeek]) || 
                !isset($room->hours[$dayOfWeek]['open']) || 
                !isset($room->hours[$dayOfWeek]['close'])) {
                $unavailableDates[] = $date->format('Y-m-d');
                continue;
            }
            
            // Set up the start and end datetime for the requested booking
            $startDateTime = $date->copy()->setTime((int)$hour, (int)$minute);
            $endDateTime = $startDateTime->copy()->addHours($duration);
            
            // Get room hours for this day
            $openTime = Carbon::parse($room->hours[$dayOfWeek]['open'], 'UTC')->setDateFrom($date);
            $closeTime = Carbon::parse($room->hours[$dayOfWeek]['close'], 'UTC')->setDateFrom($date);
            
            // If close time is earlier than open time, it means it closes the next day
            if ($closeTime->lt($openTime)) {
                $closeTime->addDay();
            }
            
            // Check if booking time is within operating hours
            if ($startDateTime->lt($openTime) || $endDateTime->gt($closeTime)) {
                $unavailableDates[] = $date->format('Y-m-d');
                continue;
            }
            
            // Get existing bookings for this date
            $existingBookings = Booking::where('room_id', $roomId)
                ->whereDate('start_time', $date)
                ->whereNotIn('state', ['App\\Modules\\PracticeSpace\\Models\\States\\BookingState\\Cancelled'])
                ->orderBy('start_time')
                ->get();
            
            // Check for conflicts with existing bookings
            $hasConflict = $existingBookings->contains(function ($booking) use ($startDateTime, $endDateTime) {
                return $booking->end_time->gt($startDateTime) && $booking->start_time->lt($endDateTime);
            });
            
            if ($hasConflict) {
                $unavailableDates[] = $date->format('Y-m-d');
            }
        }
        
        return $unavailableDates;
    }

    /**
     * Check if a specific time is available for any duration on a given date
     * 
     * @param int $roomId The room ID
     * @param string|Carbon $date The date
     * @param string $startTime The start time in HH:MM format
     * @return bool Whether the time is available for any duration
     */
    public static function isTimeAvailableForAnyDuration($roomId, $date, $startTime) {
        $durations = self::getAvailableDurationsForDateTime($roomId, $date, $startTime);
        return !empty($durations);
    }
}
