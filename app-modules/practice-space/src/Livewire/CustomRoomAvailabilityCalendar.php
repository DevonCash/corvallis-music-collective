<?php

namespace CorvMC\PracticeSpace\Livewire;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Blade;

class CustomRoomAvailabilityCalendar extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?string $selectedRoom = null;
    public Carbon $startDate;
    public Carbon $endDate;
    public string $view = 'week'; // Default to week view only
    public array $bookings = [];
    public array $rooms = [];
    public array $timeSlots = [];
    public array $bookingMap = [];
    public ?array $currentRoomDetails = null;
    
    public function mount(): void
    {
        // Set default room if none selected
        if (!$this->selectedRoom) {
            $firstRoom = Room::where('is_active', true)->first();
            if ($firstRoom) {
                $this->selectedRoom = $firstRoom->id;
            }
        }
        
        // Set initial date range to current week, always starting on Monday
        $this->startDate = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $this->endDate = $this->startDate->copy()->addDays(6); // Sunday
        
        $this->initializeRooms();
        $this->loadBookings();
        $this->loadCurrentRoomDetails();
    }
    
    /**
     * Adjust the current date range to fit within the allowable booking window
     */
    private function adjustDateRangeToAllowableWindow(): void
    {
        if (!$this->selectedRoom) {
            return;
        }
        
        $room = Room::find($this->selectedRoom);
        if (!$room) {
            return;
        }
        
        $bookingPolicy = $room->booking_policy;
        
        // Get the latest allowed booking date
        $now = Carbon::now();
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
                Select::make('selectedRoom')
                    ->hiddenLabel()                    
                    ->hidden(fn() => Room::count() <= 1)
                    ->options(function () {
                        // Get all active rooms
                        return Room::where('is_active', true)
                            ->get()
                            ->mapWithKeys(function ($room) {
                                // Format price - show without cents if it's a whole dollar amount
                                $hourlyRate = $room->hourly_rate;
                                $formattedPrice = floor($hourlyRate) == $hourlyRate
                                    ? '$' . number_format($hourlyRate, 0)
                                    : '$' . number_format($hourlyRate, 2);
                                
                                // Create a formatted label with HTML
                                $label = Blade::render("
                                    <div class='flex flex-col py-1'>
                                        <div class='text-sm flex items-center gap-2'>
                                            <span class='font-medium text-gray-900'>{$room->name}</span>
                                            <span class='text-gray-500'>{$formattedPrice}/hr</span>
                                            <span class='flex items-center text-gray-500'>
                                                <x-filament::icon icon='heroicon-o-users' class='w-4 h-4 text-gray-400 mr-1' />
                                                {$room->capacity}
                                            </span>
                                        </div>
                                        " . ($room->description ? "<div class='text-xs text-gray-400 mt-1 truncate max-w-md'>{$room->description}</div>" : "") . "
                                    </div>
                                ");
                                
                                return [$room->id => $label];
                            })
                            ->toArray();
                    })
                    ->allowHtml()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->loadCurrentRoomDetails();
                        $this->loadBookings();
                    }),
            ]);
    }
    
    public function previousPeriod(): void
    {
        // Always use week view
        $newStartDate = $this->startDate->copy()->subWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        
        // Allow navigation to previous week only if it's not entirely in the past
        if ($newEndDate->startOfDay()->greaterThanOrEqualTo(Carbon::now()->startOfDay())) {
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
            $this->loadBookings();
        }
    }
    
    public function nextPeriod(): void
    {
        // Always use week view
        $newStartDate = $this->startDate->copy()->addWeek();
        
        // Check if the new date range is within the allowable booking window
        if ($this->isDateRangeAllowed($newStartDate, $newStartDate->copy()->addDays(6))) {
            $this->startDate = $newStartDate;
            $this->endDate = $this->endDate->addWeek();
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
        
        $room = Room::find($this->selectedRoom);
        if (!$room) {
            return true;
        }
        
        $bookingPolicy = $room->booking_policy;
        
        // Get the latest allowed booking date
        $now = Carbon::now();
        $latestAllowedDate = $now->copy()->addDays($bookingPolicy->maxAdvanceBookingDays);
        
        // We only need to check if the start date is before the latest allowed date
        // We'll show all days in the week but mark past days as unavailable
        return $startDate->lessThanOrEqualTo($latestAllowedDate);
    }
    
    public function today(): void
    {
        $this->updateDateRange();
        $this->loadBookings();
    }
    
    private function updateDateRange(): void
    {
        // Always use week view starting on Monday
        $this->startDate = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $this->endDate = $this->startDate->copy()->addDays(6); // Sunday
    }
    
    private function loadCurrentRoomDetails(): void
    {
        if ($this->selectedRoom) {
            $room = Room::find($this->selectedRoom);
            if ($room) {
                // Format price - show without cents if it's a whole dollar amount
                $hourlyRate = $room->hourly_rate;
                $formattedPrice = floor($hourlyRate) == $hourlyRate
                    ? '$' . number_format($hourlyRate, 0)
                    : '$' . number_format($hourlyRate, 2);
                
                $this->currentRoomDetails = [
                    'id' => $room->id,
                    'name' => $room->name,
                    'hourly_rate' => $hourlyRate,
                    'formatted_price' => $formattedPrice,
                    'capacity' => $room->capacity,
                    'description' => $room->description,
                ];
                
                // Ensure the date range is within the allowable booking window for the selected room
                $this->adjustDateRangeToAllowableWindow();
            } else {
                $this->currentRoomDetails = null;
            }
        } else {
            $this->currentRoomDetails = null;
        }
    }
    
    private function initializeRooms(): void
    {
        // Only load the selected room
        if ($this->selectedRoom) {
            $room = Room::find($this->selectedRoom);
            if ($room) {
                $this->rooms = [
                    [
                        'id' => $room->id,
                        'name' => $room->name,
                    ]
                ];
            } else {
                $this->rooms = [];
            }
        } else {
            $this->rooms = [];
        }
    }
    
    private function loadBookings()
    {
        $this->initializeRooms();
        
        // Only load bookings if a room is selected
        if (!$this->selectedRoom) {
            $this->bookings = [];
            return;
        }
        
        $room = Room::find($this->selectedRoom);
        if (!$room) {
            $this->bookings = [];
            return;
        }
        
        $bookingPolicy = $room->booking_policy;
        
        $query = Booking::query()
            ->where('state', '!=', 'cancelled')
            ->whereBetween('start_time', [$this->startDate, $this->endDate])
            ->where('room_id', $this->selectedRoom)
            ->with(['room', 'user']);
        
        $this->bookings = $query
        ->get()
        ->map(function ($booking) use ($bookingPolicy) {
            $bookingDate = $booking->start_time->format('Y-m-d');
            $openingTime = $bookingPolicy->getOpeningTime($bookingDate);
            
            // Calculate date index (which day in the week)
            $dateIndex = (int) floor($this->startDate->diffInDays($booking->start_time));
            
            // Calculate time index (which slot in the day)
            // This is the number of 30-minute slots from opening time
            $timeIndex = (int) ceil($openingTime->diffInMinutes($booking->start_time) / 30);
            
            // Calculate number of slots this booking spans
            $slots = (int) ceil($booking->start_time->diffInMinutes($booking->end_time) / 30);
            
            return [
                'id' => $booking->id,
                'title' => $booking->user->name ,
                'time_range' => $booking->start_time->format('g:ia') . ' - ' . $booking->end_time->format('g:ia'),
                'room_name' => $booking->room->name,
                'is_current_user' => Auth::id() === $booking->user_id,
                'date_index' => $dateIndex,
                'time_index' => $timeIndex,
                'slots' => $slots,
            ];
        })
        ->toArray(); 
    }
    
    /**
     * Generate cell data for the calendar grid
     * 
     * @return array
     */
    public function generateCellData(): array
    {
        $cellData = [];
        
        // If no room is selected, return empty data
        if (!$this->selectedRoom) {
            return $cellData;
        }
        
        // Get the selected room and its booking policy
        $room = Room::find($this->selectedRoom);
        if (!$room) {
            return $cellData;
        }
        
        $bookingPolicy = $room->booking_policy;
        $minBookingDurationMinutes = $bookingPolicy->minBookingDurationHours * 60;
        $now = Carbon::now();
        
        // Always use 7 days (week view)
        $days = 7;
        for ($day = 0; $day < $days; $day++) {
            $date = $this->startDate->copy()->addDays($day);
            $dateKey = $date->format('Y-m-d');
            
            // Get operating hours for this date from the booking policy
            $openingTime = $bookingPolicy->getOpeningTime($dateKey);
            $closingTime = $bookingPolicy->getClosingTime($dateKey);
            
            // Calculate the number of half-hour slots
            $totalMinutes = $openingTime->diffInMinutes($closingTime);
            $slots = ceil($totalMinutes / 30);
            
            // Check if this date is in the past (before today)
            $isPastDate = $date->startOfDay()->lt($now->startOfDay());
            
            // Generate data for each half-hour slot
            for ($slotIndex = 0; $slotIndex < $slots; $slotIndex++) {
                $dateTime = $openingTime->copy()->addMinutes($slotIndex * 30);
                
                // Skip if we've passed the closing time
                if ($dateTime >= $closingTime) {
                    continue;
                }
                
                // Mark slots in the past as invalid
                $isPastTime = $isPastDate || ($date->isSameDay($now) && $dateTime->lt($now));
                
                $cellData[$day][$slotIndex] = [
                    'date' => $dateKey,
                    'time' => $dateTime->format('H:i'),
                    'slot_index' => $slotIndex,
                    'booking_id' => null,
                    'is_current_user_booking' => false,
                    'invalid_duration' => $isPastTime, // Mark past dates/times as invalid
                ];
            }
        }

        // Mark booked cells
        foreach ($this->bookings as $booking) {
            // Mark all slots within the booking's time range as booked
            for ($i = 0; $i < $booking['slots']; $i++) {
                if (isset($cellData[$booking['date_index']][$booking['time_index'] + $i])) {
                    $cellData[$booking['date_index']][$booking['time_index'] + $i]['booking_id'] = $booking['id'];
                    $cellData[$booking['date_index']][$booking['time_index'] + $i]['is_current_user_booking'] = $booking['is_current_user'];
                }
            }
        }
        
        // Mark cells with invalid durations (not enough time before next booking)
        foreach ($cellData as $dateIndex => $dateCells) {
            $lastBookingSlot = -1;
            
            // Find all bookings for this day and sort by time index
            $dayBookings = array_filter($this->bookings, function($booking) use ($dateIndex) {
                return $booking['date_index'] == $dateIndex;
            });
            
            // Sort bookings by time index
            usort($dayBookings, function($a, $b) {
                return $a['time_index'] <=> $b['time_index'];
            });
            
            // Process each booking
            foreach ($dayBookings as $booking) {
                $bookingStartSlot = $booking['time_index'];
                $minSlotsNeeded = ceil($minBookingDurationMinutes / 30);
                
                // Mark cells that don't have enough time before this booking
                for ($i = max(0, $bookingStartSlot - $minSlotsNeeded + 1); $i < $bookingStartSlot; $i++) {
                    if (isset($cellData[$dateIndex][$i]) && !$cellData[$dateIndex][$i]['booking_id']) {
                        $cellData[$dateIndex][$i]['invalid_duration'] = true;
                    }
                }
                
                $lastBookingSlot = $booking['time_index'] + $booking['slots'] - 1;
                
                // Mark cells that don't have enough time after this booking
                // This is needed to ensure minimum booking duration is respected between bookings
                for ($i = $lastBookingSlot + 1; $i < $lastBookingSlot + $minSlotsNeeded; $i++) {
                    if (isset($cellData[$dateIndex][$i]) && !$cellData[$dateIndex][$i]['booking_id']) {
                        $cellData[$dateIndex][$i]['invalid_duration'] = true;
                    }
                }
            }
            
            // Check for slots too close to closing time
            $date = $this->startDate->copy()->addDays($dateIndex);
            $dateKey = $date->format('Y-m-d');
            $closingTime = $bookingPolicy->getClosingTime($dateKey);
            $openingTime = $bookingPolicy->getOpeningTime($dateKey);
            
            // Calculate total slots in the day
            $totalMinutes = $openingTime->diffInMinutes($closingTime);
            $totalSlots = ceil($totalMinutes / 30);
            
            // Calculate minimum slots needed for a booking
            $minSlotsNeeded = ceil($minBookingDurationMinutes / 30);
            
            // Mark slots that are too close to closing time
            for ($slotIndex = 0; $slotIndex < $totalSlots; $slotIndex++) {
                // If this slot plus minimum duration would exceed closing time
                if ($slotIndex + $minSlotsNeeded > $totalSlots) {
                    if (isset($cellData[$dateIndex][$slotIndex]) && !$cellData[$dateIndex][$slotIndex]['booking_id']) {
                        $cellData[$dateIndex][$slotIndex]['invalid_duration'] = true;
                    }
                }
            }
        }
        
        return $cellData;
    }
    
    public function render()
    {
        $bookingPolicy = null;
        if ($this->selectedRoom) {
            $room = Room::find($this->selectedRoom);
            if ($room) {
                $bookingPolicy = $room->booking_policy;
            }
        }
        
        return view('practice-space::livewire.custom-room-availability-calendar', [
            'cellData' => $this->generateCellData(),
            'currentRoomDetails' => $this->currentRoomDetails,
            'bookingPolicy' => $bookingPolicy,
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
        
        // Allow navigation to previous week only if it's not entirely in the past
        // We check if the end of the previous week (Sunday) is today or in the future
        $newEndDate = $newStartDate->copy()->addDays(6);
        return $newEndDate->startOfDay()->greaterThanOrEqualTo(Carbon::now()->startOfDay());
    }
    
    /**
     * Check if navigation to the next period is allowed
     * 
     * @return bool
     */
    public function canNavigateToNextPeriod(): bool
    {
        $newStartDate = $this->startDate->copy()->addWeek();
        return $this->isDateRangeAllowed($newStartDate, $newStartDate->copy()->addDays(6));
    }
} 