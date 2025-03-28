<?php

namespace CorvMC\PracticeSpace\Traits;

use CorvMC\PracticeSpace\Models\Booking;
use Carbon\Carbon;

trait HasBookings
{
    /**
     * Get bookings for the current date range from the database
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getBookingsForDateRange()
    {
        return Booking::query()
            ->where('state', '!=', 'cancelled')
            ->where('room_id', $this->room->id)
            ->whereBetween('start_time', [
                $this->startDate->copy()->startOfDay(),
                $this->endDate->copy()->endOfDay()
            ])
            ->with(['room', 'user']) // Eager load relationships
            ->get();
    }
} 