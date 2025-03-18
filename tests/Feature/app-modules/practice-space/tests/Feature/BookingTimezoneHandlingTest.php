<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Livewire\RoomAvailabilityCalendar;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * @test
 * @covers REQ-005
 */
class BookingTimezoneHandlingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function evening_bookings_show_with_pm_times()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Create a room in Pacific time
        $room = Room::factory()->create([
            'name' => 'West Coast Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
        ]);
        
        // Get today in the room's timezone
        $today = Carbon::now()->setTimezone('America/Los_Angeles')->startOfDay();
        
        // Create an evening booking at 7pm Pacific time
        $bookingStartPacific = $today->copy()->setHour(19)->setMinute(0);
        $bookingEndPacific = $bookingStartPacific->copy()->addHours(3);
        
        // Create the booking - the model will handle timezone conversion
        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => $bookingStartPacific,
            'end_time' => $bookingEndPacific,
            'state' => 'confirmed',
        ]);
        
        // Check that the booking was saved properly
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'room_id' => $room->id,
            'user_id' => $user->id,
        ]);
        
        // Refresh the booking to get the values from the database
        $booking->refresh();
        
        // Verify the booking times are in the room's timezone (Pacific)
        $this->assertEquals('America/Los_Angeles', $booking->start_time->tzName);
        $this->assertEquals('America/Los_Angeles', $booking->end_time->tzName);
        
        // Verify the hour is still 19 (7pm) Pacific time
        $this->assertEquals(19, $booking->start_time->hour);
        $this->assertEquals(22, $booking->end_time->hour);
        
        // Test the display in RoomAvailabilityCalendar component
        $component = Livewire::test(RoomAvailabilityCalendar::class);
        $component->call('updateSelectedRoom', $room);
        
        // Get the bookings data
        $bookings = $component->viewData('bookings');
        
        // Verify booking is present
        $this->assertNotEmpty($bookings, "No bookings found in calendar");
        
        // Find our booking
        $foundBooking = collect($bookings)->firstWhere('id', $booking->id);
        $this->assertNotNull($foundBooking, "Booking not found in calendar data");
        
        // Verify time format shows PM for evening booking
        $this->assertStringContainsString('pm', strtolower($foundBooking['time_range']));
        $this->assertStringNotContainsString('am', strtolower($foundBooking['time_range']));
        
        // Try a cross-timezone test
        $roomEast = Room::factory()->create([
            'name' => 'East Coast Room',
            'timezone' => 'America/New_York', 
            'is_active' => true,
        ]);
        
        // Create a booking at 8pm Eastern time
        $bookingStartEastern = Carbon::now()->setTimezone('America/New_York')->startOfDay()->setHour(20)->setMinute(0);
        $bookingEndEastern = $bookingStartEastern->copy()->addHours(2);
        
        $bookingEast = Booking::factory()->create([
            'room_id' => $roomEast->id,
            'user_id' => $user->id,
            'start_time' => $bookingStartEastern,
            'end_time' => $bookingEndEastern,
            'state' => 'confirmed',
        ]);

        // Refresh the booking to get the values from the database
        $bookingEast->refresh();

        // Verify the booking times are in the room's timezone (Eastern)
        $this->assertEquals('America/New_York', $bookingEast->start_time->tzName);
        $this->assertEquals('America/New_York', $bookingEast->end_time->tzName);

        // Verify the hour is still 20 (8pm) Eastern time
        $this->assertEquals(20, $bookingEast->start_time->hour);
        $this->assertEquals(22, $bookingEast->end_time->hour);

        // Test the display in RoomAvailabilityCalendar component
        $component = Livewire::test(RoomAvailabilityCalendar::class);
        $component->call('updateSelectedRoom', $roomEast);

        // Get the bookings data
        $bookings = $component->viewData('bookings');

        // Find our booking
        $foundBooking = collect($bookings)->firstWhere('id', $bookingEast->id);
        $this->assertNotNull($foundBooking, "Booking not found in calendar data");

        // Verify time format shows PM for evening booking
        $this->assertStringContainsString('pm', strtolower($foundBooking['time_range']));
        $this->assertStringNotContainsString('am', strtolower($foundBooking['time_range']));
    }
}