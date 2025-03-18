<?php

namespace CorvMC\PracticeSpace\Livewire;

use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Filament\Forms\Components\SelectRoom;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\On;

class RoomAvailabilityCalendar extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    
    public ?Room $selectedRoom = null;
    
    public Carbon $startDate;
    
    public Carbon $endDate;
    
    public $data = [];

    #[Computed]
    public function timezone()
    {
        return $this->selectedRoom->timezone ?? config('app.timezone');
    }
    
    #[Computed]
    public function cellData()
    {
        return $this->generateCellData();
    }
    
    #[Computed]
    public function currentRoomDetails()
    {
        return $this->selectedRoom;
    }
    
    #[Computed]
    public function bookingPolicy()
    {
        return $this->selectedRoom?->booking_policy;
    }

    #[Computed]
    public function bookings()
    {
        if (!$this->selectedRoom) {
            return [];
        }

        // Get bookings from the repository
        $bookings = $this->getBookingsForDateRange();
        
        // Transform bookings for display
        return $this->transformBookingsForDisplay($bookings);
    }

    public function mount(): void
    {
        // Set default room if none selected
        if (!$this->selectedRoom) {
            $this->selectedRoom = Room::where('is_active', true)->first();
            $this->data['roomId'] = $this->selectedRoom?->id;
        }
        
        // Set initial date range to current week, always starting on Monday
        // Explicitly use the room's timezone for the start date
        $this->startDate = Carbon::now($this->timezone)->startOfWeek(Carbon::MONDAY);
        $this->endDate = $this->startDate->copy()->addDays(6); // Sunday

        $this->form->fill();
    }
    
    /**
     * Adjust the current date range to fit within the allowable booking window
     */
    private function adjustDateRangeToAllowableWindow(): void
    {
        if (!$this->selectedRoom) {
            return;
        }
        
        $bookingPolicy = $this->selectedRoom->booking_policy;
        
        // Get the latest allowed booking date in the room's timezone
        $now = Carbon::now($this->timezone)->startOfDay();
        $latestAllowedDate = $now->copy()->addDays($bookingPolicy->maxAdvanceBookingDays);
        
        // If the current start date is beyond the latest allowed date, adjust the date range
        if ($this->startDate->greaterThan($latestAllowedDate)) {
            // Find the Monday of the week containing the latest allowed date
            $this->startDate = $latestAllowedDate->copy()->startOfWeek(Carbon::MONDAY);
            $this->endDate = $this->startDate->copy()->addDays(6);
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                SelectRoom::make('roomId')
                    ->hiddenLabel()
                    ->preload()
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            $this->updateSelectedRoom($state);
                        }
                    })
            ])
            ->statePath('data');
    }
    
    public function previousPeriod(): void
    {
        // Always use week view
        $newStartDate = $this->startDate->copy()->subWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        
        // Get current time in room's timezone
        $now = Carbon::now($this->timezone)->startOfDay();
        
        // Allow navigation to previous week only if it's not entirely in the past
        if ($newEndDate->startOfDay()->greaterThanOrEqualTo($now)) {
            $this->dispatch('dateRangeUpdated', [
                'startDate' => $newStartDate->toDateString(),
                'endDate' => $newEndDate->toDateString()
            ]);
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
        }
    }
    
    public function nextPeriod(): void
    {
        // Always use week view
        $newStartDate = $this->startDate->copy()->addWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        
        // Check if the new date range is within the allowable booking window
        if ($this->isDateRangeAllowed($newStartDate, $newEndDate)) {
            $this->dispatch('dateRangeUpdated', [
                'startDate' => $newStartDate->toDateString(),
                'endDate' => $newEndDate->toDateString()
            ]);
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
        }
    }
    
    /**
     * Check if the given date range is within the allowable booking window
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return bool
     */
    private function isDateRangeAllowed(Carbon $startDate, Carbon $endDate): bool
    {
        if (!$this->selectedRoom) {
            return true;
        }
        
        $bookingPolicy = $this->selectedRoom->booking_policy;
        
        // Get the latest allowed booking date in the room's timezone
        $now = Carbon::now($this->timezone);
        $latestAllowedDate = $now->copy()->addDays($bookingPolicy->maxAdvanceBookingDays);
        
        // Ensure startDate is in the same timezone for proper comparison
        $startDateInRoomTz = $startDate->copy()->setTimezone($this->timezone);
        
        // We only need to check if the start date is before the latest allowed date
        // We'll show all days in the week but mark past days as unavailable
        return $startDateInRoomTz->lessThanOrEqualTo($latestAllowedDate);
    }
    
    public function today(): void
    {
        // Get the current time in the room's timezone
        $now = Carbon::now($this->timezone);
        
        // Always use week view starting on Monday
        $newStartDate = $now->copy()->startOfWeek(Carbon::MONDAY);
        $newEndDate = $newStartDate->copy()->addDays(6); // Sunday
        
        $this->dispatch('dateRangeUpdated', [
            'startDate' => $newStartDate->toDateString(),
            'endDate' => $newEndDate->toDateString()
        ]);
        $this->startDate = $newStartDate;
        $this->endDate = $newEndDate;
    }
    
    /**
     * Get bookings for the current date range from the database
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getBookingsForDateRange()
    {
        return Booking::query()
            ->where('state', '!=', 'cancelled')
            ->where('room_id', $this->selectedRoom->id)
            ->whereBetween('start_time', [
                $this->startDate->copy()->startOfDay(),
                $this->endDate->copy()->endOfDay()
            ])
            ->with(['room', 'user']) // Eager load relationships
            ->get();
    }

    /**
     * Transform bookings into the format needed for display
     * 
     * @param \Illuminate\Database\Eloquent\Collection $bookings
     * @return array
     */
    private function transformBookingsForDisplay($bookings)
    {
        $timezone = $this->selectedRoom->timezone;
        $bookingPolicy = $this->selectedRoom->booking_policy;
        $calendarStartDate = $this->startDate->copy()->setTimezone($timezone)->startOfDay();

        return $bookings->map(function ($booking) use ($bookingPolicy, $timezone, $calendarStartDate) {
            // Convert booking times to room's timezone
            $startTime = $booking->start_time->copy()->setTimezone($timezone);
            $endTime = $booking->end_time->copy()->setTimezone($timezone);
            
            // Get opening time for the booking date
            $bookingDate = $startTime->format('Y-m-d');
            $openingTime = $bookingPolicy->getOpeningTime($bookingDate, $timezone);
            
            // Calculate grid position
            $position = $this->calculateBookingPosition($startTime, $endTime, $calendarStartDate, $openingTime);
            
            return [
                'id' => $booking->id,
                'title' => $booking->user->name,
                'time_range' => $this->formatTimeRange($startTime, $endTime),
                'room_name' => $booking->room->name,
                'is_current_user' => Auth::id() === $booking->user_id,
                'date_index' => $position['date_index'],
                'time_index' => $position['time_index'],
                'slots' => $position['slots'],
            ];
        })->values()->toArray();
    }

    /**
     * Calculate the booking's position in the calendar grid
     * 
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param Carbon $calendarStartDate
     * @param Carbon $openingTime
     * @return array
     */
    private function calculateBookingPosition($startTime, $endTime, $calendarStartDate, $openingTime)
    {
        return [
            'date_index' => $calendarStartDate->diffInDays($startTime->copy()->startOfDay()),
            'time_index' => (int) ceil($openingTime->diffInMinutes($startTime) / 30),
            'slots' => (int) ceil($startTime->diffInMinutes($endTime) / 30),
        ];
    }

    /**
     * Format the time range for display
     * 
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return string
     */
    private function formatTimeRange($startTime, $endTime)
    {
        return $startTime->format('g:ia') . ' - ' . $endTime->format('g:ia');
    }
    
    /**
     * Update the selected room and reload bookings
     * This method is primarily exposed for testability
     * 
     * @param Room|int $room Room instance or room ID
     * @return void
     */
    public function updateSelectedRoom($room)
    {
        if (is_numeric($room)) {
            $this->selectedRoom = Room::find($room);
        } else {
            $this->selectedRoom = $room;
        }
        
        $this->adjustDateRangeToAllowableWindow();
        $this->dispatch('room-selected', room: $this->selectedRoom?->id);
    }

    /**
     * Check if a time slot is in the past
     * This method is exposed for testability
     * 
     * @param string $date Date in Y-m-d format
     * @param string $time Time in H:i format
     * @return bool
     */
    public function isTimeSlotInPast(string $date, string $time): bool
    {
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', "$date $time", $this->timezone);
        $now = Carbon::now($this->timezone);
        
        return $dateTime->lt($now);
    }
    
    /**
     * Generate cell data for the calendar grid
     * 
     * @return array
     */
    public function generateCellData(): array
    {
        // Early returns for invalid states
        if (!$this->selectedRoom) {
            return [];
        }
        
        $cellData = [];
        $bookingPolicy = $this->selectedRoom->booking_policy;
        $minBookingDurationMinutes = $bookingPolicy->minBookingDurationHours * 60;
        $minSlotsNeeded = ceil($minBookingDurationMinutes / 30);
        
        // IMPORTANT: Ensure we're working with the current time in the room's timezone
        $now = Carbon::now($this->timezone);
        $minAdvanceBookingThreshold = null;
        
        // Pre-calculate advance booking threshold if needed
        if ($bookingPolicy->minAdvanceBookingHours > 0) {
            // Create the threshold directly in the room's timezone
            $minAdvanceBookingThreshold = $now->copy()->addHours($bookingPolicy->minAdvanceBookingHours);
        }
        
        // Pre-process bookings by day for faster access
        $bookingsByDay = [];
        foreach ($this->bookings as $booking) {
            $dayIndex = $booking['date_index'];
            if (!isset($bookingsByDay[$dayIndex])) {
                $bookingsByDay[$dayIndex] = [];
            }
            $bookingsByDay[$dayIndex][] = $booking;
        }
        
        // Generate cell data for each day and time slot
        for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
            $date = $this->startDate->copy()->addDays($dayIndex);
            $dateString = $date->format('Y-m-d');
            
            // Convert the date to the room's timezone for accurate comparisons
            $dateTz = $date->copy()->setTimezone($this->timezone);
            
            // Check if this date is in the past
            $isPastDate = $dateTz->lt(Carbon::now($this->timezone)->startOfDay());
            
            // Check if this date is today
            $isToday = $dateTz->isSameDay(Carbon::now($this->timezone));
            
            // Get booking policy for this day
            $openingTime = $bookingPolicy->getOpeningTime($dateString, $this->timezone);
            $closingTime = $bookingPolicy->getClosingTime($dateString, $this->timezone);
            
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
                    'room_id' => $this->selectedRoom->id,
                ];
                
                // Create a full DateTime for this slot
                $slotDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s', 
                    $dateString . ' ' . $slotTime->format('H:i:s'), 
                    $this->timezone
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
    
    public function render()
    {
        return view('practice-space::livewire.room-availability-calendar', [
            'cellData' => $this->cellData(),
            'currentRoomDetails' => $this->currentRoomDetails(),
            'bookings' => $this->bookings(),
        ]);
    }
    
    /**
     * Check if navigation to the previous period is allowed
     * 
     * @return bool
     */
    public function canNavigateToPreviousPeriod(): bool
    {
        // Get the start date of the previous week
        $newStartDate = $this->startDate->copy()->subWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        
        // Get current time in room's timezone
        $now = Carbon::now($this->timezone)->startOfDay();
        
        // Ensure comparison is in the same timezone
        return $newEndDate->startOfDay()->greaterThanOrEqualTo($now);
    }
    
    /**
     * Check if navigation to the next period is allowed
     * 
     * @return bool
     */
    public function canNavigateToNextPeriod(): bool
    {
        $newStartDate = $this->startDate->copy()->addWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        return $this->isDateRangeAllowed($newStartDate, $newEndDate);
    }
    
  
    // Add the booking action
    public function createBookingAction()
    {
        return CreateBookingAction::make();
    }
}