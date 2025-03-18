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

class RoomAvailabilityCalendar extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    
    public ?Room $selectedRoom = null;
    public Carbon $startDate;
    public Carbon $endDate;
    public array $bookings = [];
    public $data = [];

    #[Computed]
    public function timezone()
    {
        return $this->selectedRoom->timezone ?? config('app.timezone');
    }
    
    public function mount(): void
    {
        // Set default room if none selected
        if (!$this->selectedRoom) {
            $this->selectedRoom = Room::where('is_active', true)->first();
            $this->data['selectedRoom'] = $this->selectedRoom?->id;
        }
        
        // Set initial date range to current week, always starting on Monday
        // Explicitly use the room's timezone for the start date
        $this->startDate = Carbon::now($this->timezone)->startOfWeek(Carbon::MONDAY);
        $this->endDate = $this->startDate->copy()->addDays(6); // Sunday

        $this->form->fill();
        $this->loadBookings();
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
                SelectRoom::make('selectedRoom')
                    ->hiddenLabel()
                    ->preload()
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            // Make sure state is actually an ID
                            $id = is_array($state) || is_object($state) ? ($state['id'] ?? null) : $state;
                            if ($id) {
                                $this->selectedRoom = Room::where('id', $id)->first();
                                $this->loadBookings();
                            }
                        }
                    }),
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
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
            $this->loadBookings();
        }
    }
    
    public function nextPeriod(): void
    {
        // Always use week view
        $newStartDate = $this->startDate->copy()->addWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        
        // Check if the new date range is within the allowable booking window
        if ($this->isDateRangeAllowed($newStartDate, $newEndDate)) {
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
            $this->loadBookings();
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
        $this->updateDateRange();
        $this->loadBookings();
    }
    
    private function updateDateRange(): void
    {
        // Get the current time in the room's timezone
        $now = Carbon::now($this->timezone);
        
        // Always use week view starting on Monday
        $this->startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
        $this->endDate = $this->startDate->copy()->addDays(6); // Sunday
    }
    
    
    private function loadBookings()
    {
        
        $bookingPolicy = $this->selectedRoom->booking_policy;
        
        $query = Booking::query()
            ->where('state', '!=', 'cancelled')
            ->whereBetween('start_time', [$this->startDate, $this->endDate])
            ->where('room_id', $this->selectedRoom->id)
            ->with(['room', 'user']);
        
        $timezone = $this->selectedRoom->timezone;
        $bookings = $query
        ->get()
        ->map(function ($booking) use ($bookingPolicy, $timezone) {
            // Ensure booking times are in the room's timezone
            $startTime = $booking->start_time->copy()->setTimezone($timezone);
            $endTime = $booking->end_time->copy()->setTimezone($timezone);
            
            $bookingDate = $startTime->format('Y-m-d');
            $openingTime = $bookingPolicy->getOpeningTime($bookingDate, $timezone);
            
            // Make sure to compare dates in the same timezone
            // Convert startDate to the room's timezone for proper comparison
            $calendarStartDate = $this->startDate->copy()->setTimezone($timezone)->startOfDay();
            
            // Calculate date index (which day in the week)
            $dateIndex = $calendarStartDate->diffInDays($startTime->copy()->startOfDay());
            
            // Calculate time index (which slot in the day)
            // This is the number of 30-minute slots from opening time
            $timeIndex = (int) ceil($openingTime->diffInMinutes($startTime) / 30);
            
            // Calculate number of slots this booking spans
            $slots = (int) ceil($startTime->diffInMinutes($endTime) / 30);
            
            return [
                'id' => $booking->id,
                'title' => $booking->user->name,
                'time_range' => $startTime->format('g:ia') . ' - ' . $endTime->format('g:ia'),
                'room_name' => $booking->room->name,
                'is_current_user' => Auth::id() === $booking->user_id,
                'date_index' => $dateIndex,
                'time_index' => $timeIndex,
                'slots' => $slots,
            ];
        });

        $this->bookings = $bookings->toArray();
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
        
        // Sort bookings by time index once per day
        foreach ($bookingsByDay as $dayIndex => $dayBookings) {
            usort($bookingsByDay[$dayIndex], function($a, $b) {
                return $a['time_index'] <=> $b['time_index'];
            });
        }
        
        // Generate calendar grid
        for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
            $date = $this->startDate->copy()->addDays($dayIndex);
            $dateKey = $date->format('Y-m-d');
            $isPastDate = $date->startOfDay()->lt($now->startOfDay());
            $isToday = $date->isSameDay($now);
            
            // Get operating hours for this date
            $openingTime = $date->copy()->setTimeFromTimeString($bookingPolicy->openingTime);
            $closingTime = $date->copy()->setTimeFromTimeString($bookingPolicy->closingTime);
            
            // Calculate slots and validate times once per day
            $totalMinutes = $openingTime->diffInMinutes($closingTime);
            $totalSlots = ceil($totalMinutes / 30);
            
            // Identify the slots too close to closing time (do once per day)
            $closeToClosingTime = max(0, $totalSlots - $minSlotsNeeded + 1);
            
            // Generate slots for this day
            for ($slotIndex = 0; $slotIndex < $totalSlots; $slotIndex++) {
                $slotTime = $openingTime->copy()->addMinutes($slotIndex * 30);
                
                // Skip slots beyond closing time
                if ($slotTime >= $closingTime) {
                    continue;
                }
                
                // Default cell data
                $cell = [
                    'date' => $dateKey,
                    'time' => $slotTime->format('H:i'),
                    'slot_index' => $slotIndex,
                    'booking_id' => null,
                    'is_current_user_booking' => false,
                    'invalid_duration' => false,
                ];
                
                // Mark cells in the past or too close to current time for booking
                if ($isPastDate || 
                    ($slotIndex >= $closeToClosingTime) ||
                    ($isToday && $slotTime->lt($now)) || 
                    ($isToday && $minAdvanceBookingThreshold && $slotTime->lt($minAdvanceBookingThreshold))) {
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
            'cellData' => $this->generateCellData(),
            'currentRoomDetails' => $this->selectedRoom,
            'bookingPolicy' => $this->selectedRoom->bookingPolicy,
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