<?php

namespace App\Http\Livewire;

use App\Models\Room;
use Livewire\Component;

class RoomAvailabilityCalendar extends Component
{
    public $selectedRoom;
    public $currentRoomDetails;

    public function render()
    {
        $bookingPolicy = null;
        if ($this->selectedRoom) {
            $room = Room::find($this->selectedRoom);
            if ($room) {
                $bookingPolicy = $room->booking_policy;
            }
        }
        
        return view('practice-space::livewire.room-availability-calendar', [
            'cellData' => $this->generateCellData(),
            'currentRoomDetails' => $this->currentRoomDetails,
            'bookingPolicy' => $bookingPolicy,
        ]);
    }

    private function generateCellData()
    {
        // Implementation of generateCellData method
    }
} 