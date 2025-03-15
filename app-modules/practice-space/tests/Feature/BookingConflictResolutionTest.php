<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;

class BookingConflictResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $anotherUser;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create();
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
            'capacity' => 5,
            'hourly_rate' => 25.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_prevents_double_bookings_for_the_same_time_slot()
    {
        // Create an initial booking
        $startTime = Carbon::tomorrow()->setHour(10)->setMinute(0);
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
        ]);
        
        // Attempt to create a conflicting booking
        $conflictingBooking = Booking::factory()->make([
            'room_id' => $this->room->id,
            'user_id' => $this->anotherUser->id,
            'start_time' => $startTime->copy()->addHour(),
            'end_time' => $endTime->copy()->addHour(),
        ]);
        
        // Check if the room is available for the conflicting booking
        $isAvailable = $this->room->isAvailable(
            $conflictingBooking->start_time,
            $conflictingBooking->end_time
        );
        
        // Assert that the room is not available for the conflicting booking
        $this->assertFalse($isAvailable);
    }

    /** @test */
    public function it_identifies_exact_booking_conflicts()
    {
        // Create an initial booking
        $startTime = Carbon::tomorrow()->setHour(14)->setMinute(0);
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
        ]);
        
        // Get bookings that would conflict with the exact same time slot
        $conflictingBookings = $this->room->bookingsIntersecting($startTime, $endTime);
        
        // Assert that we found the existing booking as a conflict
        $this->assertCount(1, $conflictingBookings);
        $this->assertEquals($booking->id, $conflictingBookings->first()->id);
    }

    /** @test */
    public function it_identifies_partial_booking_conflicts()
    {
        // Create an initial booking
        $startTime = Carbon::tomorrow()->setHour(16)->setMinute(0);
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
        ]);
        
        // Check for conflicts with a time slot that partially overlaps
        // (starts before the existing booking ends)
        $partialConflictStart = $endTime->copy()->subHour();
        $partialConflictEnd = $endTime->copy()->addHour();
        
        $conflictingBookings = $this->room->bookingsIntersecting(
            $partialConflictStart,
            $partialConflictEnd
        );
        
        // Assert that we found the existing booking as a conflict
        $this->assertCount(1, $conflictingBookings);
        $this->assertEquals($booking->id, $conflictingBookings->first()->id);
    }

    /** @test */
    public function it_allows_booking_after_a_cancelled_booking()
    {
        // Create a booking that will be cancelled
        $startTime = Carbon::tomorrow()->setHour(12)->setMinute(0);
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
        ]);
        
        // Cancel the booking
        $booking->update([
            'state' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => 'Testing cancellation',
        ]);
        
        // Check if the room is now available for the same time slot
        $isAvailable = $this->room->isAvailable($startTime, $endTime);
        
        // Assert that the room is available after cancellation
        $this->assertTrue($isAvailable);
        
        // Create a new booking for the same time slot
        $newBooking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->anotherUser->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
        ]);
        
        // Assert that the new booking was created successfully
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $newBooking->id,
            'room_id' => $this->room->id,
            'user_id' => $this->anotherUser->id,
            'state' => 'confirmed',
        ]);
    }
} 