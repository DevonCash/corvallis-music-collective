<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Models\States\BookingState\ConfirmedState;
use CorvMC\PracticeSpace\Models\States\BookingState\CheckedInState;
use CorvMC\PracticeSpace\Models\States\BookingState\CompletedState;
use CorvMC\PracticeSpace\Models\States\BookingState\NoShowState;
use CorvMC\PracticeSpace\Models\States\BookingState\CancelledState;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class BookingWorkflowTest extends TestCase
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
            'email' => 'test-booking-workflow@example.com',
            'name' => 'Test Booking Workflow User',
        ]);
        
        // Create a booking policy as a value object with confirmation window settings
        $this->bookingPolicy = new BookingPolicy(
            openingTime: '08:00',
            closingTime: '22:00',
            maxBookingDurationHours: 8.0,
            minBookingDurationHours: 0.5,
            maxAdvanceBookingDays: 90,
            minAdvanceBookingHours: 1.0,
            cancellationHours: 24,
            maxBookingsPerWeek: 5,
            confirmationWindowDays: 3,
            autoConfirmationDeadlineDays: 1
        );
        
        // Create a room category with the default policy
        $this->roomCategory = RoomCategory::factory()->create([
            'name' => 'Standard Practice Room',
            'description' => 'Standard practice room for bands',
            'default_booking_policy' => $this->bookingPolicy
        ]);
        
        // Create a room with the booking policy
        $this->room = Room::factory()->create([
            'room_category_id' => $this->roomCategory->id,
            'hourly_rate' => 25.00,
            'booking_policy' => $this->bookingPolicy
        ]);
    }

    /** @test */
    public function it_sets_confirmation_window_on_creation()
    {
        // Create a booking 10 days in the future
        $startTime = now()->addDays(10)->setHour(10);
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
        ]);
        
        // Confirmation should be requested 3 days before the booking
        $expectedRequestTime = $startTime->copy()->subDays(3);
        $expectedDeadline = $startTime->copy()->subDays(1);
        
        $this->assertNotNull($booking->confirmation_requested_at);
        $this->assertNotNull($booking->confirmation_deadline);
        
        // Check that the dates are set correctly (within a minute to account for test execution time)
        $this->assertTrue(
            $booking->confirmation_requested_at->diffInMinutes($expectedRequestTime) < 1,
            "Confirmation request time should be 3 days before booking"
        );
        
        $this->assertTrue(
            $booking->confirmation_deadline->diffInMinutes($expectedDeadline) < 1,
            "Confirmation deadline should be 1 day before booking"
        );
    }

    /** @test */
    public function it_can_confirm_a_booking_within_confirmation_window()
    {
        // Create a booking with confirmation window active now
        $startTime = now()->addDays(2)->setHour(10); // 2 days in the future
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDay(), // 1 day ago
            'confirmation_deadline' => now()->addDay(), // 1 day in the future
        ]);
        
        // Check that the booking is in the confirmation window
        $this->assertTrue($booking->isInConfirmationWindow());
        
        // Confirm the booking
        $booking->confirm('Confirmed by user');
        
        // Check that the booking is now confirmed
        $this->assertEquals('confirmed', $booking->getRawOriginal('state'));
        $this->assertInstanceOf(ConfirmedState::class, $booking->state);
        $this->assertNotNull($booking->confirmed_at);
        $this->assertEquals('Confirmed by user', $booking->notes);
    }

    /** @test */
    public function it_cannot_confirm_a_booking_outside_confirmation_window()
    {
        // Create a booking with confirmation window not yet active
        $startTime = now()->addDays(5)->setHour(10); // 5 days in the future
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->addDay(), // 1 day in the future
            'confirmation_deadline' => now()->addDays(3), // 3 days in the future
        ]);
        
        // Check that the booking is not in the confirmation window
        $this->assertFalse($booking->isInConfirmationWindow());
        
        // Try to confirm the booking (should throw an exception)
        $this->expectException(\InvalidArgumentException::class);
        $booking->confirm('Attempted confirmation');
    }

    /** @test */
    public function it_can_check_if_confirmation_deadline_has_passed()
    {
        // Create a booking with confirmation deadline in the past
        $startTime = now()->addDay()->setHour(10); // 1 day in the future
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDays(3), // 3 days ago
            'confirmation_deadline' => now()->subDay(), // 1 day ago
        ]);
        
        // Check that the confirmation deadline has passed
        $this->assertTrue($booking->isConfirmationDeadlinePassed());
    }

    /** @test */
    public function it_can_check_in_a_confirmed_booking()
    {
        // Create a confirmed booking for today
        $startTime = now()->setHour(10); // Today at 10 AM
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
            'confirmed_at' => now()->subDay(),
        ]);
        
        // Check in the booking with payment completed
        $booking->checkIn('Checked in by staff', true);
        
        // Check that the booking is now checked in
        $this->assertEquals('checked_in', $booking->getRawOriginal('state'));
        $this->assertInstanceOf(CheckedInState::class, $booking->state);
        $this->assertNotNull($booking->check_in_time);
        $this->assertEquals('Checked in by staff', $booking->notes);
        $this->assertTrue($booking->payment_completed);
    }

    /** @test */
    public function it_cannot_check_in_a_scheduled_booking()
    {
        // Create a scheduled booking
        $startTime = now()->setHour(10); // Today at 10 AM
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
        ]);
        
        // Try to check in the booking (should throw an exception)
        $this->expectException(\InvalidArgumentException::class);
        $booking->checkIn('Attempted check-in');
    }

    /** @test */
    public function it_can_complete_a_checked_in_booking()
    {
        // Create a checked-in booking
        $startTime = now()->subHours(2)->setHour(10); // Started 2 hours ago
        $endTime = now()->setHour(12); // Just ended
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'checked_in',
            'check_in_time' => $startTime,
        ]);
        
        // Complete the booking
        $booking->complete('Completed by staff');
        
        // Check that the booking is now completed
        $this->assertEquals('completed', $booking->getRawOriginal('state'));
        $this->assertInstanceOf(CompletedState::class, $booking->state);
        $this->assertNotNull($booking->check_out_time);
        $this->assertEquals('Completed by staff', $booking->notes);
    }

    /** @test */
    public function it_cannot_complete_a_confirmed_booking()
    {
        // Create a confirmed booking
        $startTime = now()->subHours(2)->setHour(10); // Started 2 hours ago
        $endTime = now()->setHour(12); // Just ended
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
            'confirmed_at' => now()->subDay(),
        ]);
        
        // Try to complete the booking (should throw an exception)
        $this->expectException(\InvalidArgumentException::class);
        $booking->complete('Attempted completion');
    }

    /** @test */
    public function it_can_mark_a_confirmed_booking_as_no_show()
    {
        // Create a confirmed booking that started more than 15 minutes ago
        $startTime = now()->subMinutes(20)->setHour(10); // Started 20 minutes ago
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
            'confirmed_at' => now()->subDay(),
        ]);
        
        // Mock the current time to be after the no-show threshold
        Carbon::setTestNow($startTime->copy()->addMinutes(20));
        
        // Check that the booking can be marked as no-show
        $this->assertTrue($booking->canBeMarkedAsNoShow());
        
        // Mark the booking as no-show
        $booking->markAsNoShow('Called member at 555-1234 but no answer');
        
        // Check that the booking is now marked as no-show
        $this->assertEquals('no_show', $booking->getRawOriginal('state'));
        $this->assertInstanceOf(NoShowState::class, $booking->state);
        $this->assertEquals('Called member at 555-1234 but no answer', $booking->no_show_notes);
        
        // Reset the mock time
        Carbon::setTestNow();
    }

    /** @test */
    public function it_cannot_mark_a_booking_as_no_show_before_threshold()
    {
        // Create a confirmed booking that just started
        $startTime = now()->setHour(10); // Today at 10 AM
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'confirmed',
            'confirmed_at' => now()->subDay(),
        ]);
        
        // Mock the current time to be 5 minutes after the booking starts
        Carbon::setTestNow($startTime->copy()->addMinutes(5));
        
        // Check that the booking cannot be marked as no-show yet
        $this->assertFalse($booking->canBeMarkedAsNoShow());
        
        // Try to mark the booking as no-show (should throw an exception)
        $this->expectException(\InvalidArgumentException::class);
        $booking->markAsNoShow('Attempted no-show marking');
        
        // Reset the mock time
        Carbon::setTestNow();
    }

    /** @test */
    public function it_can_cancel_a_scheduled_or_confirmed_booking()
    {
        // Create a scheduled booking
        $scheduledBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(5)->setHour(10),
            'end_time' => now()->addDays(5)->setHour(12),
            'state' => 'scheduled',
        ]);
        
        // Cancel the scheduled booking
        $scheduledBooking->cancel('Cancelled by member');
        
        // Check that the booking is now cancelled
        $this->assertEquals('cancelled', $scheduledBooking->getRawOriginal('state'));
        $this->assertInstanceOf(CancelledState::class, $scheduledBooking->state);
        $this->assertNotNull($scheduledBooking->cancelled_at);
        $this->assertEquals('Cancelled by member', $scheduledBooking->cancellation_reason);
        
        // Create a confirmed booking
        $confirmedBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(5)->setHour(14),
            'end_time' => now()->addDays(5)->setHour(16),
            'state' => 'confirmed',
            'confirmed_at' => now()->subDay(),
        ]);
        
        // Cancel the confirmed booking
        $confirmedBooking->cancel('Cancelled by staff');
        
        // Check that the booking is now cancelled
        $this->assertEquals('cancelled', $confirmedBooking->getRawOriginal('state'));
        $this->assertInstanceOf(CancelledState::class, $confirmedBooking->state);
        $this->assertNotNull($confirmedBooking->cancelled_at);
        $this->assertEquals('Cancelled by staff', $confirmedBooking->cancellation_reason);
    }

    /** @test */
    public function it_cannot_cancel_a_completed_booking()
    {
        // Create a completed booking
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->subDays(2)->setHour(10),
            'end_time' => now()->subDays(2)->setHour(12),
            'state' => 'completed',
            'check_in_time' => now()->subDays(2)->setHour(10),
            'check_out_time' => now()->subDays(2)->setHour(12),
        ]);
        
        // Try to cancel the completed booking (should throw an exception)
        $this->expectException(\InvalidArgumentException::class);
        $booking->cancel('Attempted cancellation');
    }
} 