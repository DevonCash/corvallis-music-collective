<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;

class RoomAvailabilityCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
            'capacity' => 5,
            'hourly_rate' => 25.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_displays_room_availability_with_hourly_resolution()
    {
        // Create some bookings for the room
        $today = Carbon::today();
        
        // Create a booking from 10 AM to 12 PM
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $today->copy()->setHour(10)->setMinute(0),
            'end_time' => $today->copy()->setHour(12)->setMinute(0),
            'state' => 'confirmed',
        ]);
        
        // Get available time slots for today
        $availableSlots = $this->room->getAvailableTimeSlots($today);
        
        // Assert that the time slots from 10 AM to 12 PM are not available
        $this->assertArrayNotHasKey('10:00', $availableSlots);
        $this->assertArrayNotHasKey('11:00', $availableSlots);
        
        // Assert that other time slots are available (assuming operating hours are 8 AM to 8 PM)
        $this->assertArrayHasKey('08:00', $availableSlots);
        $this->assertArrayHasKey('09:00', $availableSlots);
        $this->assertArrayHasKey('12:00', $availableSlots);
        $this->assertArrayHasKey('13:00', $availableSlots);
    }

    /** @test */
    public function it_shows_fully_booked_dates_in_calendar()
    {
        // Create bookings that cover the entire day
        $today = Carbon::today();
        $operatingHours = $this->room->getOperatingHours($today->format('Y-m-d'));
        
        // Create a booking that spans the entire operating hours
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => Carbon::parse($operatingHours['opening']),
            'end_time' => Carbon::parse($operatingHours['closing']),
            'state' => 'confirmed',
        ]);
        
        // Get fully booked dates
        $fullyBookedDates = $this->room->getFullyBookedDates(
            $today->copy()->startOfMonth(),
            $today->copy()->endOfMonth()
        );
        
        // Assert that today is in the fully booked dates
        $this->assertContains($today->format('Y-m-d'), $fullyBookedDates);
    }

    /** @test */
    public function it_provides_hourly_resolution_for_room_availability()
    {
        $today = Carbon::today();
        
        // Create a booking from 2 PM to 4 PM
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $today->copy()->setHour(14)->setMinute(0),
            'end_time' => $today->copy()->setHour(16)->setMinute(0),
            'state' => 'confirmed',
        ]);
        
        // Get available time slots
        $availableSlots = $this->room->getAvailableTimeSlots($today);
        
        // Check that the resolution is hourly (each key should be in HH:MM format)
        foreach (array_keys($availableSlots) as $timeSlot) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $timeSlot);
        }
        
        // Verify specific hours are not available
        $this->assertArrayNotHasKey('14:00', $availableSlots);
        $this->assertArrayNotHasKey('15:00', $availableSlots);
        
        // Verify hours before and after booking are available
        $this->assertArrayHasKey('13:00', $availableSlots);
        $this->assertArrayHasKey('16:00', $availableSlots);
    }
} 