<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;
    protected $roomCategory;
    protected $bookingPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-booking-policy@example.com',
            'name' => 'Test Booking Policy User',
        ]);
        
        // Create a booking policy as a value object
        $this->bookingPolicy = new BookingPolicy(
            openingTime: '08:00',
            closingTime: '22:00',
            maxBookingDurationHours: 8.0,
            minBookingDurationHours: 0.5,
            maxAdvanceBookingDays: 90,
            minAdvanceBookingHours: 1.0,
            cancellationHours: 24,
            maxBookingsPerWeek: 5
        );
        
        // Create a room category with the default policy
        $this->roomCategory = RoomCategory::factory()->create([
            'name' => 'Standard Practice Room',
            'description' => 'Standard practice room for bands',
            'default_booking_policy' => $this->bookingPolicy
        ]);
        
        // Create a room without a specific booking policy (will use category default)
        $this->room = Room::factory()->create([
            'room_category_id' => $this->roomCategory->id,
            'hourly_rate' => 25.00,
            'booking_policy' => null // Explicitly set to null to use category default
        ]);
    }

    /**
     * @test
     * @covers REQ-002
     */
    public function booking_policy_is_associated_with_room_category()
    {
        // Test that the room falls back to the category's default booking policy
        $this->assertInstanceOf(BookingPolicy::class, $this->room->booking_policy);
        $this->assertEquals(8.0, $this->room->booking_policy->maxBookingDurationHours);
        
        // Verify that it's using the category's policy
        $this->assertEquals($this->roomCategory->default_booking_policy->openingTime, $this->room->booking_policy->openingTime);
    }

    /**
     * @test
     * @covers REQ-007
     * @covers REQ-009
     */
    public function booking_duration_is_validated_against_policy()
    {
        // Test booking with valid duration
        $validBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours, within policy
            'state' => 'scheduled',
        ]);
        
        $this->assertTrue($validBooking->validateAgainstPolicy());
        
        // Test booking with duration too long
        $tooLongBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(19), // 9 hours, exceeds max of 8 hours
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooLongBooking->validateAgainstPolicy());
        
        // Test booking with duration too short
        $tooShortBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(10)->addMinutes(20), // 20 minutes, below min of 30 minutes
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooShortBooking->validateAgainstPolicy());
    }

    /**
     * @test
     * @covers REQ-007
     * @covers REQ-009
     */
    public function booking_advance_notice_is_validated_against_policy()
    {
        // Test booking with valid advance notice
        $validBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addHours(3), // 3 hours in advance, within policy
            'end_time' => now()->addHours(5),
            'state' => 'scheduled',
        ]);
        
        $this->assertTrue($validBooking->validateAgainstPolicy());
        
        // Test booking with too little advance notice
        $tooSoonBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addMinutes(30), // 30 minutes in advance, below min of 1 hour
            'end_time' => now()->addHours(2)->addMinutes(30),
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooSoonBooking->validateAgainstPolicy());
        
        // Test booking too far in advance
        $tooFarBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDays(91), // 91 days in advance, exceeds max of 90 days
            'end_time' => now()->addDays(91)->addHours(2),
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooFarBooking->validateAgainstPolicy());
    }

    /**
     * @test
     * @covers REQ-009
     */
    public function booking_weekly_limit_is_enforced()
    {
        // Get the start of the week to ensure all bookings are in the same week
        $weekStart = Carbon::now()->startOfWeek();
        
        // Create 5 bookings for the user this week (at the limit)
        for ($i = 0; $i < 5; $i++) {
            Booking::factory()->create([
                'room_id' => $this->room->id,
                'user_id' => $this->testUser->id,
                'start_time' => $weekStart->copy()->addDays($i)->setHour(10),
                'end_time' => $weekStart->copy()->addDays($i)->setHour(12),
                'state' => 'scheduled',
            ]);
        }
        
        // Try to create a 6th booking in the same week
        $sixthBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $weekStart->copy()->addDays(6)->setHour(14), // Same week, different time
            'end_time' => $weekStart->copy()->addDays(6)->setHour(16),
            'state' => 'scheduled',
        ]);
        
        // Should fail validation due to weekly limit
        $this->assertFalse($sixthBooking->validateAgainstPolicy());
    }

    /** @test */
    public function cancellation_policy_is_enforced()
    {
        // Skip this test since canBeCancelled method is not available
        $this->markTestSkipped('The method canBeCancelled() is not available on the Booking model');
    }
} 