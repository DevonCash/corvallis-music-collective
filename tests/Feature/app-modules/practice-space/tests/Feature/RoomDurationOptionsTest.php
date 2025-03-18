<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @test
 * @covers REQ-005
 */
class RoomDurationOptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function duration_options_respect_room_timezone_and_policy()
    {
        // Create a room in Pacific time with a specific policy
        $room = Room::factory()->create([
            'name' => 'West Coast Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => BookingPolicy::fromArray([
                'minBookingDurationHours' => 1,
                'maxBookingDurationHours' => 4,
                'openingTime' => '09:00',
                'closingTime' => '22:00',
            ]),
        ]);

        // Get current time in room's timezone
        $now = Carbon::now()->setTimezone('America/Los_Angeles');
        
        // Set start time to 7 PM (19:00) today in room's timezone
        $startTime = $now->copy()->startOfDay()->setHour(19)->setMinute(0);
        
        // Get available durations
        $durations = $room->getAvailableDurations($startTime);
        
        // Should have options from 1 hour to 3 hours (since closing is at 22:00)
        $this->assertArrayHasKey('1', $durations);
        $this->assertArrayHasKey('2', $durations);
        $this->assertArrayHasKey('3', $durations);
        $this->assertArrayNotHasKey('4', $durations); // 4 hours would go past closing time
        
        // Check half-hour increments
        $this->assertArrayHasKey('1.5', $durations);
        $this->assertArrayHasKey('2.5', $durations);
        
        // Verify format of duration labels
        $this->assertEquals('1 hour', $durations['1']);
        $this->assertEquals('1.5 hours', $durations['1.5']);
        $this->assertEquals('2 hours', $durations['2']);
    }

    /** @test */
    public function duration_options_respect_existing_bookings()
    {
        // Create a room in Eastern time
        $room = Room::factory()->create([
            'name' => 'East Coast Room',
            'timezone' => 'America/New_York',
            'is_active' => true,
            'booking_policy' => BookingPolicy::fromArray([
                'minBookingDurationHours' => 1,
                'maxBookingDurationHours' => 4,
                'openingTime' => '09:00',
                'closingTime' => '22:00',
            ]),
        ]);

        // Create a user
        $user = User::factory()->create();
        
        // Get current time in room's timezone
        $now = Carbon::now()->setTimezone('America/New_York');
        
        // Create a booking from 8 PM to 10 PM
        $existingBookingStart = $now->copy()->startOfDay()->setHour(20)->setMinute(0);
        $existingBookingEnd = $existingBookingStart->copy()->addHours(2);
        
        Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => $existingBookingStart,
            'end_time' => $existingBookingEnd,
            'state' => 'confirmed',
        ]);

        // Try to get durations for a 6 PM start
        $startTime = $now->copy()->startOfDay()->setHour(18)->setMinute(0);
        $durations = $room->getAvailableDurations($startTime);
        
        // Should only have options up to 2 hours (since there's a booking at 8 PM)
        $this->assertArrayHasKey('1', $durations);
        $this->assertArrayHasKey('1.5', $durations);
        $this->assertArrayHasKey('2', $durations);
        $this->assertArrayNotHasKey('2.5', $durations); // Would overlap with 8 PM booking
        $this->assertArrayNotHasKey('3', $durations);
    }

    /** @test */
    public function duration_options_handle_closing_time_correctly()
    {
        // Create a room in Pacific time that closes at 10 PM
        $room = Room::factory()->create([
            'name' => 'Late Night Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => BookingPolicy::fromArray([
                'minBookingDurationHours' => 0.5,
                'maxBookingDurationHours' => 4,
                'openingTime' => '09:00',
                'closingTime' => '22:00',
            ]),
        ]);

        // Get current time in room's timezone
        $now = Carbon::now()->setTimezone('America/Los_Angeles');
        
        // Test durations for different times near closing
        
        // At 9:00 PM (1 hour before closing)
        $startTime = $now->copy()->startOfDay()->setHour(21)->setMinute(0);
        $durations = $room->getAvailableDurations($startTime);
        
        $this->assertArrayHasKey('0.5', $durations);
        $this->assertArrayHasKey('1', $durations);
        $this->assertArrayNotHasKey('1.5', $durations); // Would go past closing time
        
        // At 9:30 PM (30 minutes before closing)
        $startTime = $now->copy()->startOfDay()->setHour(21)->setMinute(30);
        $durations = $room->getAvailableDurations($startTime);
        
        $this->assertArrayHasKey('0.5', $durations);
        $this->assertArrayNotHasKey('1', $durations); // Would go past closing time
        
        // At 9:45 PM (15 minutes before closing)
        $startTime = $now->copy()->startOfDay()->setHour(21)->setMinute(45);
        $durations = $room->getAvailableDurations($startTime);
        
        $this->assertEmpty($durations); // No valid durations available
    }
} 