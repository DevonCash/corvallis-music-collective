<?php

namespace Tests\Feature;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoomDurationOptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function duration_options_respect_room_policy()
    {
        // Create a room with a specific policy
        $room = Room::factory()->create([
            'name' => 'Test Room',
            'is_active' => true,
            'booking_policy' => BookingPolicy::fromArray([
                'minBookingDurationHours' => 1,
                'maxBookingDurationHours' => 4,
                'openingTime' => '09:00',
                'closingTime' => '22:00',
            ]),
        ]);

        // Get current time in app timezone
        $now = Carbon::now();
        
        // Set start time to 7 PM (19:00) today
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
        // Create a room that closes at 10 PM
        $room = Room::factory()->create([
            'name' => 'Test Room',
            'is_active' => true,
            'booking_policy' => BookingPolicy::fromArray([
                'minBookingDurationHours' => 0.5,
                'maxBookingDurationHours' => 4,
                'openingTime' => '09:00',
                'closingTime' => '22:00',
            ]),
        ]);

        // Get current time
        $now = Carbon::now();
        
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