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
        
        $this->startDate = Carbon::now()->startOfWeek();
        $this->endDate = Carbon::now()->endOfWeek();
        $this->initializeRooms();
        $this->loadBookings();
        $this->loadCurrentRoomDetails();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedRoom')
                    ->label('Select a Practice Room')
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
        $this->startDate = $this->startDate->subWeek();
        $this->endDate = $this->endDate->subWeek();
        
        $this->loadBookings();
    }
    
    public function nextPeriod(): void
    {
        // Always use week view
        $this->startDate = $this->startDate->addWeek();
        $this->endDate = $this->endDate->addWeek();
        
        $this->loadBookings();
    }
    
    public function today(): void
    {
        $this->updateDateRange();
        $this->loadBookings();
    }
    
    private function updateDateRange(): void
    {
        // Always use week view
        $this->startDate = Carbon::now()->startOfWeek();
        $this->endDate = Carbon::now()->endOfWeek();
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
            
            // Generate data for each half-hour slot
            for ($slotIndex = 0; $slotIndex < $slots; $slotIndex++) {
                $dateTime = $openingTime->copy()->addMinutes($slotIndex * 30);
                
                // Skip if we've passed the closing time
                if ($dateTime >= $closingTime) {
                    continue;
                }
                
                $cellData[$day][$slotIndex] = [
                    'date' => $dateKey,
                    'time' => $dateTime->format('H:i'),
                    'slot_index' => $slotIndex,
                    'booking_id' => null,
                    'is_current_user_booking' => false,
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
} 