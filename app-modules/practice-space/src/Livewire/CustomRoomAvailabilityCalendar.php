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

class CustomRoomAvailabilityCalendar extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?string $selectedRoom = null;
    public Carbon $startDate;
    public Carbon $endDate;
    public string $view = 'week';
    public array $bookings = [];
    public array $rooms = [];
    public array $timeSlots = [];
    public array $bookingMap = [];
    
    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfWeek();
        $this->endDate = Carbon::now()->endOfWeek();
        $this->initializeRooms();
        $this->loadBookings();
        $this->generateTimeSlots();
        $this->generateBookingMap();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Select::make('selectedRoom')
                    ->label('Filter by Room')
                    ->options(Room::where('is_active', true)->pluck('name', 'id'))
                    ->placeholder('All Rooms')
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->loadBookings();
                        $this->generateBookingMap();
                    }),
                Select::make('view')
                    ->label('View')
                    ->options([
                        'day' => 'Day',
                        'week' => 'Week',
                    ])
                    ->default('week')
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->updateDateRange();
                        $this->loadBookings();
                        $this->generateBookingMap();
                    }),
            ]);
    }
    
    public function previousPeriod(): void
    {
        if ($this->view === 'day') {
            $this->startDate = $this->startDate->subDay();
            $this->endDate = $this->endDate->subDay();
        } else {
            $this->startDate = $this->startDate->subWeek();
            $this->endDate = $this->endDate->subWeek();
        }
        
        $this->loadBookings();
        $this->generateBookingMap();
    }
    
    public function nextPeriod(): void
    {
        if ($this->view === 'day') {
            $this->startDate = $this->startDate->addDay();
            $this->endDate = $this->endDate->addDay();
        } else {
            $this->startDate = $this->startDate->addWeek();
            $this->endDate = $this->endDate->addWeek();
        }
        
        $this->loadBookings();
        $this->generateBookingMap();
    }
    
    public function today(): void
    {
        $this->updateDateRange();
        $this->loadBookings();
        $this->generateBookingMap();
    }
    
    private function updateDateRange(): void
    {
        if ($this->view === 'day') {
            $this->startDate = Carbon::now()->startOfDay();
            $this->endDate = Carbon::now()->endOfDay();
        } else {
            $this->startDate = Carbon::now()->startOfWeek();
            $this->endDate = Carbon::now()->endOfWeek();
        }
    }
    
    private function initializeRooms(): void
    {
        $query = Room::query()->where('is_active', true);
        
        if ($this->selectedRoom) {
            $query->where('id', $this->selectedRoom);
        }
        
        $this->rooms = $query->get()->map(function ($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
            ];
        })->toArray();
    }
    
    private function loadBookings(): void
    {
        $this->initializeRooms();
        
        $query = Booking::query()
            ->where('state', '!=', 'cancelled')
            ->whereBetween('start_time', [$this->startDate, $this->endDate])
            ->with(['room', 'user']);
            
        if ($this->selectedRoom) {
            $query->where('room_id', $this->selectedRoom);
        }
        
        $bookings = $query->get();
        $currentUserId = Auth::id();
        
        $this->bookings = $bookings->map(function (Booking $booking) use ($currentUserId) {
            $isCurrentUserBooking = $booking->user_id === $currentUserId;
            $startsOnHalfHour = $booking->start_time->minute >= 15 && $booking->start_time->minute < 45;
            
            return [
                'id' => $booking->id,
                'room_id' => $booking->room_id,
                'room_name' => $booking->room->name,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'title' => $isCurrentUserBooking ? $booking->user->name : 'Booked',
                'is_current_user' => $isCurrentUserBooking,
                'time_range' => $booking->start_time->format('g:ia') . ' - ' . $booking->end_time->format('g:ia'),
                'date' => $booking->start_time->format('Y-m-d'),
                'start_hour' => (int) $booking->start_time->format('G'),
                'start_minute' => (int) $booking->start_time->format('i'),
                'end_hour' => (int) $booking->end_time->format('G'),
                'end_minute' => (int) $booking->end_time->format('i'),
                'duration_slots' => $this->calculateDurationSlots($booking->start_time, $booking->end_time),
                'starts_on_half_hour' => $startsOnHalfHour,
            ];
        })->toArray();
        
        $this->generateTimeSlots();
    }
    
    private function calculateDurationSlots(Carbon $startTime, Carbon $endTime): int
    {
        // Calculate how many 1-hour slots this booking spans
        $startHour = $startTime->hour;
        $endHour = $endTime->hour;
        
        // If end time has minutes, add an extra hour
        if ($endTime->minute > 0) {
            $endHour += 1;
        }
        
        return max(1, $endHour - $startHour);
    }
    
    private function generateTimeSlots(): void
    {
        $this->timeSlots = [];
        
        // Generate time slots from 8am to 10pm on the hour
        for ($hour = 8; $hour <= 22; $hour++) {
            $time = sprintf('%02d:00', $hour);
            $displayTime = Carbon::createFromFormat('H:i', $time)->format('g:ia');
            
            $this->timeSlots[] = [
                'time' => $time,
                'display_time' => $displayTime,
                'hour' => $hour,
                'minute' => 0,
                'slot_index' => $hour - 8,
            ];
        }
    }
    
    private function generateBookingMap(): void
    {
        $this->bookingMap = [];
        $dates = $this->getDates();
        
        foreach ($dates as $date) {
            $dateKey = $date['date'];
            $this->bookingMap[$dateKey] = [];
            
            foreach ($this->rooms as $room) {
                $roomId = $room['id'];
                $this->bookingMap[$dateKey][$roomId] = [];
                
                // Initialize all slots to null (no booking)
                foreach ($this->timeSlots as $timeSlot) {
                    $slotIndex = $timeSlot['slot_index'];
                    $this->bookingMap[$dateKey][$roomId][$slotIndex] = null;
                }
                
                // Fill in bookings
                foreach ($this->bookings as $booking) {
                    if ($booking['room_id'] == $roomId && $booking['date'] == $dateKey) {
                        $startSlot = $booking['start_hour'] - 8;
                        $endSlot = $startSlot + $booking['duration_slots'];
                        
                        // Mark all slots covered by this booking
                        for ($slot = $startSlot; $slot < $endSlot && $slot < count($this->timeSlots); $slot++) {
                            $this->bookingMap[$dateKey][$roomId][$slot] = [
                                'booking' => $booking,
                                'is_start' => ($slot == $startSlot),
                                'span' => $booking['duration_slots'],
                            ];
                        }
                    }
                }
            }
        }
    }
    
    public function getDates(): array
    {
        $dates = [];
        $period = CarbonPeriod::create($this->startDate, $this->endDate);
        
        foreach ($period as $date) {
            if ($this->view === 'week' || $date->isSameDay($this->startDate)) {
                $dates[] = [
                    'date' => $date->format('Y-m-d'),
                    'display_date' => $date->format('D n/j'),
                    'is_today' => $date->isToday(),
                ];
            }
        }
        
        return $dates;
    }
    
    public function getBookingForSlot($date, $roomId, $slotIndex): ?array
    {
        return $this->bookingMap[$date][$roomId][$slotIndex] ?? null;
    }
    
    public function render()
    {
        return view('practice-space::livewire.custom-room-availability-calendar', [
            'dates' => $this->getDates(),
        ]);
    }
} 