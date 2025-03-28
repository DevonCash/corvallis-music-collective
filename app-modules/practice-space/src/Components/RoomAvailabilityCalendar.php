<?php

namespace CorvMC\PracticeSpace\Components;

use CorvMC\PracticeSpace\Models\Room;
use Livewire\Component;

class RoomAvailabilityCalendar extends Component
{
    public $selectedRoom;
    public $calendarDates;

    public function mount(?Room $room = null)
    {
        $this->selectedRoom = $room;
        $this->initializeCalendarDates();
    }

    public function updatedSelectedRoom()
    {
        $this->initializeCalendarDates();
    }

    private function initializeCalendarDates()
    {
        // Implementation of initializeCalendarDates method
    }
} 