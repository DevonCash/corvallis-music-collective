<?php

namespace CorvMC\PracticeSpace\Calendar;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Contracts\CalendarEvent;
use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingCalendarEvent implements CalendarEvent
{
    public function __construct(
        private readonly Booking $booking
    ) {}
    
    public function getEventId(): string|int
    {
        return $this->booking->id;
    }
    
    public function getStartTime(): Carbon
    {
        return $this->booking->start_time;
    }
    
    public function getEndTime(): Carbon
    {
        return $this->booking->end_time;
    }
    
    public function getEventTitle(): string
    {
        return $this->booking->user->name;
    }
    
    public function belongsToCurrentUser(): bool
    {
        return Auth::id() === $this->booking->user_id;
    }
    
    public function getEventMetadata(): array
    {
        return [
            'room_name' => $this->booking->room->name,
            'state' => $this->booking->state,
        ];
    }
    
    /**
     * Create a collection of BookingCalendarEvents from a collection of Bookings
     * 
     * @param \Illuminate\Database\Eloquent\Collection<Booking> $bookings
     * @return \Illuminate\Support\Collection<BookingCalendarEvent>
     */
    public static function fromBookings($bookings)
    {
        return $bookings->map(fn (Booking $booking) => new self($booking));
    }
} 