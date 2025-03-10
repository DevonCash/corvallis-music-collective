<?php

namespace CorvMC\PracticeSpace\Livewire;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;
use Illuminate\Support\Collection;

class RoomAvailabilityCalendar extends FullCalendarWidget
{
    public ?string $selectedRoom = null;
    
    public function getFormSchema(): array
    {
        return [
            Select::make('selectedRoom')
                ->label('Filter by Room')
                ->options(Room::where('is_active', true)->pluck('name', 'id'))
                ->placeholder('All Rooms')
                ->live()
                ->afterStateUpdated(function () {
                    $this->dispatch('calendar-refresh');
                }),
        ];
    }

    /**
     * FullCalendar will call this function whenever it needs new event data.
     * This is triggered when the user clicks prev/next or switches views.
     * @param array{start: string, end: string, timezone: string} $info
     */
    public function fetchEvents(array $info): array
    {
        $query = Booking::query()
            ->where('state', '!=', 'cancelled')
            ->with(['room', 'user']);
            
        if ($this->selectedRoom) {
            $query->where('room_id', $this->selectedRoom);
        }
        
        $bookings = $query->get();
        $currentUserId = Auth::id();
        
        return $bookings->map(function (Booking $booking) use ($currentUserId) {
            $isCurrentUserBooking = $booking->user_id === $currentUserId;
            
            // Format the time range for display
            $timeRange = $booking->start_time->format('g:ia') . ' - ' . $booking->end_time->format('g:ia');
            
            // Create the title with user name/status and time range
            $title = ($isCurrentUserBooking ? $booking->user->name : 'Booked');
            
            return EventData::make()
                ->id($booking->id)
                ->title($title)
                ->start($booking->start_time)
                ->end($booking->end_time)
                ->resourceId($booking->room_id)
                ->backgroundColor($isCurrentUserBooking ? '#4f46e5' : '#6b7280')
                ->extendedProps([
                    'room' => $booking->room->name,
                    'timeRange' => $timeRange,
                ])
                ->toArray();
        })->toArray();
    }
    
    protected function getViewOptions(): array
    {
        return [
            'initialView' => 'resourceTimeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'resourceTimeGridDay,resourceTimeGridWeek,dayGridMonth',
            ],
            'slotMinTime' => '08:00:00',
            'slotMaxTime' => '22:00:00',
            'allDaySlot' => false,
            'slotDuration' => '00:30:00',
            'slotLabelFormat' => [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'omitZeroMinute' => false,
                'meridiem' => 'short'
            ],
            'resources' => $this->getResources(),
            'eventTimeFormat' => [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'meridiem' => 'short',
            ],
            'displayEventTime' => true,
            'displayEventEnd' => true,
            'height' => 'auto',
            'expandRows' => true,
            'nowIndicator' => true,
            'eventContent' => "function(arg) {
                return {
                    html: '<div class=\"fc-event-title\">' + arg.event.title + '</div>' +
                          '<div class=\"fc-event-time\">' + arg.event.extendedProps.timeRange + '</div>'
                };
            }",
            'views' => [
                'resourceTimeGridWeek' => [
                    'type' => 'resourceTimeGrid',
                    'duration' => [ 'days' => 7 ]
                ],
                'resourceTimeGridDay' => [
                    'type' => 'resourceTimeGrid',
                    'duration' => [ 'days' => 1 ]
                ]
            ]
        ];
    }
    
    protected function getResources(): array
    {
        $query = Room::query()->where('is_active', true);
        
        if ($this->selectedRoom) {
            $query->where('id', $this->selectedRoom);
        }
        
        return $query->get()->map(function (Room $room) {
            return [
                'id' => $room->id,
                'title' => $room->name,
            ];
        })->toArray();
    }
} 