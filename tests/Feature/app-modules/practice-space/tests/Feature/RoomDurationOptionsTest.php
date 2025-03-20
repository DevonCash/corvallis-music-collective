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
        
        // Use a fixed date in the future to avoid time-based test failures
        $testDate = Carbon::parse('2030-01-15')->setTimezone('America/New_York');
        
        // Create a booking from 8 PM to 10 PM on our test date
        $existingBookingStart = $testDate->copy()->startOfDay()->setHour(20)->setMinute(0);
        $existingBookingEnd = $existingBookingStart->copy()->addHours(2);
        
        echo "\nDebug info for test date: " . $testDate->format('Y-m-d H:i:s') . " (New York)\n";
        echo "Booking start: " . $existingBookingStart->format('Y-m-d H:i:s') . " (New York)\n";
        echo "Booking end: " . $existingBookingEnd->format('Y-m-d H:i:s') . " (New York)\n";
        
        Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => $existingBookingStart,
            'end_time' => $existingBookingEnd,
            'state' => 'confirmed',
        ]);

        // Try to get durations for a 6 PM start on the same test date
        $startTime = $testDate->copy()->startOfDay()->setHour(18)->setMinute(0);
        echo "Duration check start time: " . $startTime->format('Y-m-d H:i:s') . " (New York)\n";
        $durations = $room->getAvailableDurations($startTime);
        
        echo "Available durations: " . print_r($durations, true) . "\n";
        
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

    /**
     * @test
     * @covers REQ-005
     */
    public function duration_options_respect_closing_time_with_specific_timezone()
    {
        // Create a room in Pacific time with specific policy and March 19 date
        $room = Room::factory()->create([
            'name' => 'Pacific Test Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => BookingPolicy::fromArray([
                'minBookingDurationHours' => 0.5,
                'maxBookingDurationHours' => 4,
                'openingTime' => '08:00',
                'closingTime' => '22:00',
            ]),
        ]);

        // Use March 19, 2025 as a fixed test date
        $testDate = Carbon::parse('2025-03-19')->setTimezone('America/Los_Angeles');
        
        // Test case 1: Room closes at 10:00 PM, check duration at 8:30 PM
        // Should only allow bookings up to 1.5 hours (ending at 10:00 PM)
        $startTime1 = $testDate->copy()->setTime(20, 30); // 8:30 PM
        echo "\nTest case 1: " . $startTime1->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        $durations1 = $room->getAvailableDurations($startTime1);
        echo "Available durations at 8:30 PM: " . print_r($durations1, true) . "\n";
        
        // Should allow up to 1.5 hours (until 10:00 PM closing)
        $this->assertArrayHasKey('0.5', $durations1);
        $this->assertArrayHasKey('1', $durations1);
        $this->assertArrayHasKey('1.5', $durations1);
        $this->assertArrayNotHasKey('2', $durations1); // Would go past closing time
        $this->assertArrayNotHasKey('3', $durations1); // Would go past closing time
        
        // Test case 2: Room closes at 10:00 PM, check duration at 7:00 PM
        // Should allow bookings up to 3 hours (ending at 10:00 PM)
        $startTime2 = $testDate->copy()->setTime(19, 0); // 7:00 PM
        echo "Test case 2: " . $startTime2->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        $durations2 = $room->getAvailableDurations($startTime2);
        echo "Available durations at 7:00 PM: " . print_r($durations2, true) . "\n";
        
        // Should allow up to 3 hours (until 10:00 PM closing)
        $this->assertArrayHasKey('0.5', $durations2);
        $this->assertArrayHasKey('1', $durations2);
        $this->assertArrayHasKey('2', $durations2);
        $this->assertArrayHasKey('3', $durations2);
        $this->assertArrayNotHasKey('3.5', $durations2); // Would go past closing time
        $this->assertArrayNotHasKey('4', $durations2); // Would go past closing time
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function duration_options_handle_timezone_date_boundary_correctly()
    {
        // Create a room in Pacific time with specific policy
        $bookingPolicy = new BookingPolicy(
            openingTime: '08:00',
            closingTime: '22:00',
            maxBookingDurationHours: 4.0,
            minBookingDurationHours: 0.5
        );
        
        $room = Room::factory()->create([
            'name' => 'Pacific Time Zone Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => $bookingPolicy,
        ]);

        // Use March 19, 2025 at midnight UTC 
        // (This would be March 18, 2025 in Los Angeles due to timezone difference)
        $testDateUtc = Carbon::parse('2025-03-19 00:00:00', 'UTC');
        echo "\nTest date in UTC: " . $testDateUtc->format('Y-m-d H:i:s') . " (UTC)\n";
        
        // Convert to LA timezone for verification
        $testDateLA = $testDateUtc->copy()->setTimezone('America/Los_Angeles');
        echo "Same date in LA: " . $testDateLA->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        
        // Now try with a different approach - specify the date directly in LA timezone
        $directDateLA = Carbon::parse('2025-03-19 00:00:00', 'America/Los_Angeles');
        echo "Direct LA date: " . $directDateLA->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        echo "Direct LA date in UTC: " . $directDateLA->copy()->setTimezone('UTC')->format('Y-m-d H:i:s') . " (UTC)\n";
        
        // Test for the reported issue: Try bookings on March 19th LA time, 8:30 PM
        $bookingTimeLA = Carbon::parse('2025-03-19 20:30:00', 'America/Los_Angeles');
        echo "\nBooking time in LA: " . $bookingTimeLA->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        echo "Booking time in UTC: " . $bookingTimeLA->copy()->setTimezone('UTC')->format('Y-m-d H:i:s') . " (UTC)\n";
        
        $durations = $room->getAvailableDurations($bookingTimeLA);
        echo "Available durations for 8:30 PM on March 19 (LA): " . print_r($durations, true) . "\n";
        
        // Should allow up to 1.5 hours (ending at 10:00 PM LA time)
        $this->assertArrayHasKey('0.5', $durations);
        $this->assertArrayHasKey('1', $durations);
        $this->assertArrayHasKey('1.5', $durations);
        $this->assertArrayNotHasKey('2', $durations); // Would go past closing time (10 PM)
        
        // Test for a morning slot
        $morningTimeLA = Carbon::parse('2025-03-19 08:00:00', 'America/Los_Angeles');
        echo "\nMorning time in LA: " . $morningTimeLA->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        $morningDurations = $room->getAvailableDurations($morningTimeLA);
        echo "Available durations for 8:00 AM on March 19 (LA): " . print_r($morningDurations, true) . "\n";
        
        // Check that we have appropriate durations available
        $this->assertNotEmpty($morningDurations, "Morning durations should not be empty");
        
        // The max duration should be limited by the booking policy (4 hours)
        $maxDuration = max(array_map('floatval', array_keys($morningDurations)));
        echo "Maximum duration offered: " . $maxDuration . " hours\n";
        $this->assertEquals(4.0, $maxDuration, "Maximum duration should be limited by policy max (4 hours)");
    }
    
    /**
     * @test
     * @covers REQ-005
     */
    public function duration_options_respect_max_policy_duration()
    {
        // Create a room with a 2-hour max booking policy
        $bookingPolicy = new BookingPolicy(
            openingTime: '08:00',
            closingTime: '22:00',
            maxBookingDurationHours: 2.0, // Explicitly set as 2.0 hours max
            minBookingDurationHours: 0.5
        );
        
        echo "\nMax policy test - Policy max hours: " . $bookingPolicy->maxBookingDurationHours . "\n";
        
        $room = Room::factory()->create([
            'name' => 'Limited Duration Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => $bookingPolicy,
        ]);

        // Verify the booking policy is set correctly on the room
        echo "Room policy max hours after creation: " . $room->booking_policy->maxBookingDurationHours . "\n";

        // Reload the room from the database to ensure it's persisted correctly
        $roomReloaded = Room::find($room->id);
        echo "Reloaded room policy max hours: " . $roomReloaded->booking_policy->maxBookingDurationHours . "\n";
        
        // Test for a morning slot with plenty of time until closing
        $morningTime = Carbon::parse('2025-03-19 08:00:00', 'America/Los_Angeles');
        echo "\nMorning time for limited duration test: " . $morningTime->format('Y-m-d H:i:s') . " (Los Angeles)\n";
        
        // Make sure we use our newly created room
        $durations = $roomReloaded->getAvailableDurations($morningTime);
        echo "Available durations with 2-hour policy maximum: " . print_r($durations, true) . "\n";
        
        // Durations should be capped at 2 hours per the policy
        $this->assertArrayHasKey('0.5', $durations);
        $this->assertArrayHasKey('1', $durations);
        $this->assertArrayHasKey('1.5', $durations);
        $this->assertArrayHasKey('2', $durations);
        $this->assertArrayNotHasKey('2.5', $durations); // Should be limited by policy
        $this->assertArrayNotHasKey('3', $durations);   // Should be limited by policy
        
        // Check the actual maximum value
        $maxDuration = max(array_map('floatval', array_keys($durations)));
        $this->assertEquals(2.0, $maxDuration, "Maximum duration should be limited by policy max (2 hours)");
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function duration_options_respect_march_19_specific_timezone_issue()
    {
        // Create a room with explicit LA timezone and policy
        $bookingPolicy = new BookingPolicy(
            openingTime: '08:00',
            closingTime: '22:00',
            maxBookingDurationHours: 3.0,
            minBookingDurationHours: 0.5
        );
        
        echo "Test policy max hours: " . $bookingPolicy->maxBookingDurationHours . "\n";
        
        $room = Room::factory()->create([
            'name' => 'March 19 Test Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => $bookingPolicy,
        ]);
        
        // Directly verify the room's policy
        echo "Room policy after creation: " . $room->booking_policy->maxBookingDurationHours . "\n";
        
        // Reload from database to verify storage
        $reloadedRoom = Room::find($room->id);
        echo "Reloaded room policy: " . $reloadedRoom->booking_policy->maxBookingDurationHours . "\n";
        
        // Use March 19, 2025
        $date = Carbon::parse('2025-03-19', 'America/Los_Angeles');
        
        // Test various time slots throughout the day to ensure proper durations
        
        // 8:00 AM (Opening Time) - should allow bookings up to policy max (3 hours)
        $morning = $date->copy()->setTime(8, 0);
        $morningDurations = $room->getAvailableDurations($morning);
        
        echo "Available morning durations: " . print_r($morningDurations, true) . "\n";
        
        // Verify max duration is limited by policy (3 hours)
        $morningMaxDuration = max(array_map('floatval', array_keys($morningDurations)));
        $this->assertEquals(3.0, $morningMaxDuration, "Morning slot should be limited by 3hr max policy");
        
        // 7:00 PM - should allow up to 3 hours (ending at 10 PM closing)
        $evening = $date->copy()->setTime(19, 0);
        $eveningDurations = $room->getAvailableDurations($evening);
        
        echo "Available evening durations: " . print_r($eveningDurations, true) . "\n";
        
        // Should have up to 3 hours available (policy max and before closing time)
        $this->assertArrayHasKey('0.5', $eveningDurations);
        $this->assertArrayHasKey('1', $eveningDurations);
        $this->assertArrayHasKey('2', $eveningDurations);
        $this->assertArrayHasKey('3', $eveningDurations);
        $this->assertArrayNotHasKey('3.5', $eveningDurations); // Would exceed policy max
        
        // 8:30 PM - should only allow up to 1.5 hours (to reach 10 PM closing)
        $lateEvening = $date->copy()->setTime(20, 30);
        $lateEveningDurations = $room->getAvailableDurations($lateEvening);
        
        echo "Available late evening durations: " . print_r($lateEveningDurations, true) . "\n";
        
        // Should only have options up to 1.5 hours (due to 10 PM closing)
        $this->assertArrayHasKey('0.5', $lateEveningDurations);
        $this->assertArrayHasKey('1', $lateEveningDurations);
        $this->assertArrayHasKey('1.5', $lateEveningDurations);
        $this->assertArrayNotHasKey('2', $lateEveningDurations); // Past closing time
    }
} 