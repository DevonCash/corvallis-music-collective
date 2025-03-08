<?php

namespace CorvMC\PracticeSpace\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use Illuminate\Support\HtmlString;

class BookingService
{
    /**
     * Check if a time is on the half hour (XX:00 or XX:30)
     *
     * @param Carbon $dateTime
     * @return bool
     */
    public function isTimeOnHalfHour(Carbon $dateTime): bool
    {
        $minutes = (int) $dateTime->format('i');
        return $minutes === 0 || $minutes === 30;
    }

    /**
     * Check if a room is available for the given time slot
     *
     * @param int $roomId
     * @param Carbon $startDateTime
     * @param Carbon $endDateTime
     * @return bool
     */
    public function isRoomAvailable(int $roomId, Carbon $startDateTime, Carbon $endDateTime): bool
    {
        $room = Room::find($roomId);
        if (!$room) {
            return false;
        }
        
        return $room->isAvailable($startDateTime, $endDateTime);
    }
    
    /**
     * Calculate the total price for a booking in cents
     *
     * @param int $roomId
     * @param int $durationHours
     * @return int
     */
    public function calculateTotalPriceInCents(int $roomId, int $durationHours): int
    {
        $room = Room::find($roomId);
        if (!$room) {
            return 0;
        }
        
        $hourlyRateInCents = (int) round($room->hourly_rate * 100);
        return (int) round($hourlyRateInCents * $durationHours);
    }
    
    /**
     * Calculate the total price for a booking
     *
     * @param int $roomId
     * @param int $durationHours
     * @return float
     */
    public function calculateTotalPrice(int $roomId, int $durationHours): float
    {
        return $this->calculateTotalPriceInCents($roomId, $durationHours) / 100;
    }
    
    /**
     * Get room details by ID
     *
     * @param int $roomId
     * @return Room|null
     */
    public function getRoomById(int $roomId): ?Room
    {
        return Room::find($roomId);
    }
    
    /**
     * Get all active rooms
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveRooms()
    {
        return Room::query()->where('is_active', true)->get();
    }
    
    /**
     * Get room options for select field
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoomOptions()
    {
        return Room::query()->where('is_active', true)->pluck('name', 'id');
    }
    
    /**
     * Calculate start and end datetime from form data
     *
     * @param array $data
     * @return array
     */
    public function calculateBookingTimes(array $data): array
    {
        $startDateTime = Carbon::parse($data['booking_date'] . ' ' . $data['booking_time']);
        
        if (isset($data['end_time'])) {
            $endDateTime = Carbon::parse($data['booking_date'] . ' ' . $data['end_time']);
            
            // If end time is earlier than start time, it means it's the next day
            if ($endDateTime <= $startDateTime) {
                $endDateTime->addDay();
            }
        } else {
            $endDateTime = $startDateTime->copy()->addHours((int)$data['duration_hours']);
        }
        
        return [
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
        ];
    }
    
    /**
     * Create a booking instance without saving it to the database
     *
     * @param array $data
     * @return Booking
     * @throws \Exception
     */
    public function createBookingInstance(array $data): Booking
    {
        // Validate booking data
        $validationResult = $this->validateBookingData($data);
        
        if (!$validationResult['is_valid']) {
            throw new \Exception($validationResult['error_message']);
        }
        
        $startDateTime = $validationResult['start_datetime'];
        $endDateTime = $validationResult['end_datetime'];
        $totalPrice = $validationResult['total_price'];
        
        // Create the booking instance without saving
        return new Booking([
            'room_id' => $data['room_id'],
            'user_id' => Auth::id(),
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'state' => 'reserved',
            'notes' => $data['notes'] ?? null,
            'total_price' => $totalPrice,
        ]);
    }
    
    /**
     * Validate booking data and check availability
     *
     * @param array $data
     * @return array
     */
    public function validateBookingData(array $data): array
    {
        $result = [
            'is_valid' => true,
            'error_message' => null,
        ];
        
        // Calculate booking times
        $times = $this->calculateBookingTimes($data);
        $startDateTime = $times['start_datetime'];
        $endDateTime = $times['end_datetime'];
        
        // Get the room and its booking policy
        $room = $this->getRoomById($data['room_id']);
        if (!$room) {
            $result['is_valid'] = false;
            $result['error_message'] = 'Room not found.';
            return $result;
        }
        
        $policy = $room->getBookingPolicy();
        
        // Validate that start time is on the half hour
        if (!$this->isTimeOnHalfHour($startDateTime)) {
            $result['is_valid'] = false;
            $result['error_message'] = 'Booking start time must be on the hour or half hour (e.g., 9:00 or 9:30).';
            return $result;
        }
        
        // Check if room is available
        if (!$this->isRoomAvailable($data['room_id'], $startDateTime, $endDateTime)) {
            $result['is_valid'] = false;
            $result['error_message'] = 'The selected room is not available for the chosen time slot.';
            return $result;
        }
        
        // Check if booking duration is within policy limits
        $durationHours = (float) $data['duration_hours'];
        if ($durationHours < $policy->minBookingDurationHours) {
            $result['is_valid'] = false;
            $result['error_message'] = "Bookings for this room must be at least {$policy->minBookingDurationHours} hours.";
            return $result;
        }
        
        if ($durationHours > $policy->maxBookingDurationHours) {
            $result['is_valid'] = false;
            $result['error_message'] = "Bookings for this room cannot exceed {$policy->maxBookingDurationHours} hours.";
            return $result;
        }
        
        // Check if booking is within operating hours
        $operatingHours = $policy->getOperatingHours($data['booking_date']);
        if ($startDateTime->lt($operatingHours['opening'])) {
            $result['is_valid'] = false;
            $result['error_message'] = "Bookings for this room cannot start before {$policy->openingTime}.";
            return $result;
        }
        
        if ($endDateTime->gt($operatingHours['closing'])) {
            $result['is_valid'] = false;
            $result['error_message'] = "Bookings for this room must end by {$policy->closingTime}.";
            return $result;
        }
        
        // Check if booking is within allowed advance booking window
        $now = Carbon::now();
        $maxAdvanceDate = $now->copy()->addDays($policy->maxAdvanceBookingDays);
        if ($startDateTime->startOfDay()->gt($maxAdvanceDate->startOfDay())) {
            $result['is_valid'] = false;
            $result['error_message'] = "Bookings can only be made up to {$policy->maxAdvanceBookingDays} days in advance.";
            return $result;
        }
        
        // Check if booking meets minimum advance booking time
        $minAdvanceTime = $now->copy()->addHours($policy->minAdvanceBookingHours);
        if ($startDateTime->lt($minAdvanceTime)) {
            $result['is_valid'] = false;
            $result['error_message'] = "Bookings must be made at least {$policy->minAdvanceBookingHours} hours in advance.";
            return $result;
        }
        
        // Check if user has exceeded max bookings per week
        if ($policy->maxBookingsPerWeek > 0) {
            $userId = Auth::id();
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
            
            $userBookingsThisWeek = Booking::where('user_id', $userId)
                ->where('state', '!=', 'cancelled')
                ->where(function ($query) use ($weekStart, $weekEnd) {
                    $query->whereBetween('start_time', [$weekStart, $weekEnd])
                        ->orWhereBetween('end_time', [$weekStart, $weekEnd]);
                })
                ->count();
            
            if ($userBookingsThisWeek >= $policy->maxBookingsPerWeek) {
                $result['is_valid'] = false;
                $result['error_message'] = "You have reached the maximum of {$policy->maxBookingsPerWeek} bookings per week.";
                return $result;
            }
        }
        
        // Add calculated times to result
        $result['start_datetime'] = $startDateTime;
        $result['end_datetime'] = $endDateTime;
        
        // Calculate total price
        $result['total_price'] = $this->calculateTotalPrice($data['room_id'], (int)$data['duration_hours']);
        
        return $result;
    }
    
    /**
     * Prepare booking summary data
     *
     * @param array $data
     * @return array
     */
    public function prepareBookingSummaryData(array $data): array
    {
        $room = $this->getRoomById($data['room_id']);
        $times = $this->calculateBookingTimes($data);
        $totalPrice = $this->calculateTotalPrice($data['room_id'], (int)$data['duration_hours']);
        
        return [
            'room' => $room,
            'booking_date' => $data['booking_date'],
            'booking_time' => $data['booking_time'],
            'end_time' => $times['end_datetime']->format('H:i'),
            'duration_hours' => $data['duration_hours'],
            'hourly_rate' => $room->hourly_rate,
            'total_price' => $totalPrice,
        ];
    }
    
    /**
     * Render booking summary HTML from form data
     *
     * @param array $data
     * @return HtmlString
     */
    public function renderBookingSummary(array $data): HtmlString
    {
        try {
            // Create a booking instance without saving it
            $booking = $this->createBookingInstance($data);
            
            // Prepare the summary data
            $room = $this->getRoomById($booking->room_id);
            
            $html = view('practice-space::filament.forms.booking-summary', [
                'room' => $room,
                'booking_date' => $booking->start_time->format('Y-m-d'),
                'booking_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
                'duration_hours' => $booking->end_time->diffInHours($booking->start_time),
                'hourly_rate' => $room->hourly_rate,
                'total_price' => $booking->total_price,
            ])->render();
            
            return new HtmlString($html);
        } catch (\Exception $e) {
            // If there's an error, return a simple error message
            return new HtmlString('<div class="text-danger-500">' . $e->getMessage() . '</div>');
        }
    }
    
    /**
     * Create a new booking
     *
     * @param array $data
     * @return Booking
     * @throws \Exception
     */
    public function createBooking(array $data): Booking
    {
        // Create a booking instance
        $booking = $this->createBookingInstance($data);
        
        // Save it to the database
        $booking->save();
        
        return $booking;
    }

    /**
     * Get dates when a room is fully booked
     *
     * @param int $roomId
     * @param \Carbon\Carbon|null $startDate Start date for the range (defaults to today)
     * @param \Carbon\Carbon|null $endDate End date for the range (defaults to 3 months from start)
     * @return array
     */
    public function getFullyBookedDates(int $roomId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $room = Room::find($roomId);
        if (!$room) {
            return [];
        }
        
        return $room->getFullyBookedDates($startDate, $endDate);
    }

    /**
     * Get available time slots for a room on a specific date
     *
     * @param int $roomId
     * @param string $date
     * @param float|null $duration
     * @return array
     */
    public function getAvailableTimeSlots(int $roomId, string $date, ?float $duration = null): array
    {
        $room = Room::find($roomId);
        if (!$room) {
            return [];
        }
        
        return $room->getAvailableTimeSlots($date, $duration);
    }

    /**
     * Get available durations for a room at a specific date and time
     *
     * @param int $roomId
     * @param string $date
     * @param string|null $time Optional time parameter
     * @param bool $includeHalfHour
     * @return array
     */
    public function getAvailableDurations(int $roomId, string $date, ?string $time = null, bool $includeHalfHour = false): array
    {
        $room = Room::find($roomId);
        if (!$room) {
            return [];
        }
        
        return $room->getAvailableDurations($date, $time, $includeHalfHour);
    }

    /**
     * Generate duration options
     *
     * @param float $maxDuration
     * @param bool $includeHalfHour
     * @return array
     */
    private function generateDurationOptions(float $maxDuration, bool $includeHalfHour = false): array
    {
        $options = [];
        $step = $includeHalfHour ? 0.5 : 1;
        
        for ($duration = $step; $duration <= $maxDuration; $duration += $step) {
            if ($duration == 0.5) {
                $options[$duration] = '30 minutes';
            } elseif ($duration == 1) {
                $options[$duration] = '1 hour';
            } elseif ($duration - floor($duration) == 0) {
                $options[$duration] = $duration . ' hours';
            } else {
                $options[$duration] = $duration . ' hours';
            }
        }
        
        return $options;
    }
} 