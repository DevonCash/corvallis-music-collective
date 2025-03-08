<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\BookingPolicy;
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
        
        // Create a room category
        $this->roomCategory = RoomCategory::factory()->create([
            'name' => 'Standard Practice Room',
            'description' => 'Standard practice room for bands',
        ]);
        
        // Create a room
        $this->room = Room::factory()->create([
            'room_category_id' => $this->roomCategory->id,
            'hourly_rate' => 25.00,
        ]);
        
        // Create a booking policy for the room category
        $this->bookingPolicy = BookingPolicy::factory()->create([
            'room_category_id' => $this->roomCategory->id,
            'max_booking_duration_hours' => 4, // hours
            'min_booking_duration_hours' => 1, // hour
            'max_advance_booking_days' => 30,
            'min_advance_booking_hours' => 2,
            'cancellation_policy' => 'Cancellations must be made at least 24 hours in advance for a full refund.',
            'cancellation_hours' => 24,
            'max_bookings_per_week' => 3,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function booking_policy_is_associated_with_room_category()
    {
        $this->assertEquals($this->roomCategory->id, $this->bookingPolicy->room_category_id);
        $this->assertInstanceOf(BookingPolicy::class, $this->roomCategory->bookingPolicy);
    }

    /** @test */
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
            'end_time' => now()->addDay()->setHour(15), // 5 hours, exceeds max
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooLongBooking->validateAgainstPolicy());
        
        // Test booking with duration too short
        $tooShortBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(10)->addMinutes(30), // 30 minutes, below min
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooShortBooking->validateAgainstPolicy());
    }

    /** @test */
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
            'start_time' => now()->addHour(), // 1 hour in advance, below min
            'end_time' => now()->addHours(3),
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooSoonBooking->validateAgainstPolicy());
        
        // Test booking too far in advance
        $tooFarBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDays(31), // 31 days in advance, exceeds max
            'end_time' => now()->addDays(31)->addHours(2),
            'state' => 'scheduled',
        ]);
        
        $this->assertFalse($tooFarBooking->validateAgainstPolicy());
    }

    /** @test */
    public function booking_weekly_limit_is_enforced()
    {
        // Get the start of the week to ensure all bookings are in the same week
        $weekStart = Carbon::now()->startOfWeek();
        
        // Create 3 bookings for the user this week (at the limit)
        for ($i = 0; $i < 3; $i++) {
            Booking::factory()->create([
                'room_id' => $this->room->id,
                'user_id' => $this->testUser->id,
                'start_time' => $weekStart->copy()->addDays($i)->setHour(10),
                'end_time' => $weekStart->copy()->addDays($i)->setHour(12),
                'state' => 'scheduled',
            ]);
        }
        
        // Try to create a 4th booking in the same week
        $fourthBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $weekStart->copy()->addDays(3)->setHour(14), // Same week, different time
            'end_time' => $weekStart->copy()->addDays(3)->setHour(16),
            'state' => 'scheduled',
        ]);
        
        // Should fail validation due to weekly limit
        $this->assertFalse($fourthBooking->validateAgainstPolicy());
    }

    /** @test */
    public function cancellation_policy_is_enforced()
    {
        // Create a booking for tomorrow
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'state' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        
        // Cancel with sufficient notice (more than 24 hours)
        $this->assertTrue($booking->canCancelWithRefund());
        
        // Move the booking to be in 12 hours
        $booking->update([
            'start_time' => now()->addHours(12),
            'end_time' => now()->addHours(14),
        ]);
        
        // Cancel with insufficient notice (less than 24 hours)
        $this->assertFalse($booking->canCancelWithRefund());
    }

    /** @test */
    public function booking_policy_can_be_overridden_for_specific_user()
    {
        // Create a policy override for the user
        $this->bookingPolicy->createOverrideForUser($this->testUser->id, [
            'max_bookings_per_week' => 5,
            'max_booking_duration_hours' => 6,
        ]);
        
        // Create 4 bookings for the user this week (exceeds normal limit but within override)
        for ($i = 0; $i < 4; $i++) {
            Booking::factory()->create([
                'room_id' => $this->room->id,
                'user_id' => $this->testUser->id,
                'start_time' => now()->addDay($i)->setHour(10),
                'end_time' => now()->addDay($i)->setHour(12),
                'state' => 'scheduled',
            ]);
        }
        
        // Create a 5th booking with longer duration
        $fifthBooking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDays(5)->setHour(10),
            'end_time' => now()->addDays(5)->setHour(15), // 5 hours, exceeds normal max but within override
            'state' => 'scheduled',
        ]);
        
        // Should pass validation due to policy override
        $this->assertTrue($fifthBooking->validateAgainstPolicy());
    }
} 