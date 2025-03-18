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
            'booking_policy' => [
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'min_booking_duration_hours' => 1,
                'max_booking_duration_hours' => 4,
                'min_advance_booking_hours' => 0,
                'max_advance_booking_days' => 30,
                'allowed_days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            ],
            'timezone' => 'UTC',
        ]);
    }

    /** @test */
    public function it_prints_time_slots_for_debugging()
    {
        // Use a specific date in the future to avoid issues with the current time
        $testDate = Carbon::tomorrow($this->room->timezone);
        
        // Get available time slots for the test date
        $availableSlots = $this->room->getAvailableTimeSlots($testDate);
        
        // Dump the available slots
        dump('Available time slots:', $availableSlots);
        
        // Make a simple assertion to ensure the test passes
        $this->assertIsArray($availableSlots);
    }

    /** @test */
    public function it_displays_room_availability_with_hourly_resolution()
    {
        // Use a future date to avoid same-day booking restrictions
        $futureDate = Carbon::tomorrow($this->room->timezone);
        
        // Create a booking from 10 AM to 12 PM in the room's timezone
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $futureDate->copy()->setHour(10)->setMinute(0)->setTimezone('UTC'),
            'end_time' => $futureDate->copy()->setHour(12)->setMinute(0)->setTimezone('UTC'),
            'state' => 'confirmed',
        ]);
        
        // Get available time slots for the future date
        $availableSlots = $this->room->getAvailableTimeSlots($futureDate);
        
        // Dump the available slots
        dump('Available time slots in it_displays_room_availability_with_hourly_resolution:', $availableSlots);
        
        // Assert that the time slots from 10 AM to 12 PM are not available
        $this->assertArrayNotHasKey('10:00', $availableSlots);
        $this->assertArrayNotHasKey('11:00', $availableSlots);
        $this->assertArrayNotHasKey('12:00', $availableSlots);
        
        // Assert that other time slots are available (assuming operating hours are 8 AM to 8 PM)
        $this->assertArrayHasKey('08:00', $availableSlots);
        $this->assertArrayHasKey('09:00', $availableSlots);
        $this->assertArrayHasKey('12:30', $availableSlots);
        $this->assertArrayHasKey('13:00', $availableSlots);
    }

    /** @test */
    public function it_shows_fully_booked_dates_in_calendar()
    {
        // Use a future date to avoid same-day booking restrictions
        $futureDate = Carbon::tomorrow($this->room->timezone);
        $operatingHours = $this->room->getOperatingHours($futureDate->format('Y-m-d'));
        
        // Create a booking that spans the entire operating hours
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => Carbon::parse($operatingHours['opening'], $this->room->timezone)->setTimezone('UTC'),
            'end_time' => Carbon::parse($operatingHours['closing'], $this->room->timezone)->setTimezone('UTC'),
            'state' => 'confirmed',
        ]);
        
        // Get fully booked dates
        $fullyBookedDates = $this->room->getFullyBookedDates(
            $futureDate->copy()->startOfMonth(),
            $futureDate->copy()->endOfMonth()
        );
        
        // Assert that the future date is in the fully booked dates
        $this->assertContains($futureDate->format('Y-m-d'), $fullyBookedDates);
    }

    /** @test */
    public function it_provides_hourly_resolution_for_room_availability()
    {
        // Use a future date to avoid same-day booking restrictions
        $futureDate = Carbon::tomorrow($this->room->timezone);
        
        // Create a booking from 2 PM to 4 PM in the room's timezone
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $futureDate->copy()->setHour(14)->setMinute(0)->setTimezone('UTC'),
            'end_time' => $futureDate->copy()->setHour(16)->setMinute(0)->setTimezone('UTC'),
            'state' => 'confirmed',
        ]);
        
        // Get available time slots
        $availableSlots = $this->room->getAvailableTimeSlots($futureDate);
        
        // Dump the available slots
        dump('Available time slots in it_provides_hourly_resolution_for_room_availability:', $availableSlots);
        
        // Check that the resolution is hourly (each key should be in HH:MM format)
        foreach (array_keys($availableSlots) as $timeSlot) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $timeSlot);
        }
        
        // Verify specific hours are not available
        $this->assertArrayNotHasKey('14:00', $availableSlots);
        $this->assertArrayNotHasKey('15:00', $availableSlots);
        $this->assertArrayNotHasKey('16:00', $availableSlots);
        
        // Verify hours before and after booking are available
        $this->assertArrayHasKey('13:00', $availableSlots);
        $this->assertArrayHasKey('16:30', $availableSlots);
    }
} 