<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Services\RecurringBookingService;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Recurr\Frequency;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;
    protected $roomCategory;
    protected $bookingPolicy;
    protected $recurringBookingService;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-recurring-booking@example.com',
            'name' => 'Test Recurring Booking User',
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
            'booking_policy' => $this->bookingPolicy,
        ]);
        
        // Create the recurring booking service
        $this->recurringBookingService = new RecurringBookingService();
    }

    /**
     * @test
     * @covers REQ-007, REQ-008, REQ-013
     */
    public function it_can_create_weekly_recurring_bookings()
    {
        // Use Carbon directly to avoid timezone issues
        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);
        $endTime = $startTime->copy()->addHours(2);
        
        // Create base booking data
        $bookingData = [
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => 'Weekly band practice',
        ];
        
        // Create a recurring booking with weekly frequency
        $booking = $this->recurringBookingService->createRecurringBooking(
            $bookingData,
            'weekly',
            [
                'count' => 5 // Create 5 instances
            ]
        );
        
        // Verify the parent booking is correct
        $this->assertNotNull($booking);
        $this->assertTrue($booking->is_recurring);
        $this->assertTrue($booking->is_recurring_parent);
        $this->assertNotNull($booking->rrule_string);
        $this->assertStringContainsString('FREQ=WEEKLY', $booking->rrule_string);
        
        // Verify that recurring instances were created
        $recurringBookings = $booking->recurringBookings;
        $this->assertCount(4, $recurringBookings); // 4 instances + 1 parent = 5 total
        
        // Verify all recurring instances have the correct properties
        foreach ($recurringBookings as $instance) {
            $this->assertEquals($booking->id, $instance->recurring_booking_id);
            $this->assertTrue($instance->is_recurring);
            $this->assertFalse($instance->is_recurring_parent);
            $this->assertEquals($booking->room_id, $instance->room_id);
            $this->assertEquals($booking->user_id, $instance->user_id);
            $this->assertEquals($booking->notes, $instance->notes);
            
            // Verify each instance is 7 days after the previous one
            $daysDiff = $instance->start_time->diffInDays($booking->start_time) % 7;
            $this->assertEquals(0, $daysDiff, 'Recurring instance should be on the same day of week');
        }
    }

    /**
     * @test
     * @covers REQ-007, REQ-008, REQ-013
     */
    public function it_can_create_monthly_recurring_bookings()
    {
        // Use Carbon directly to avoid timezone issues
        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);
        $endTime = $startTime->copy()->addHours(2);
        
        // Create base booking data
        $bookingData = [
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => 'Monthly band practice',
        ];
        
        // Create a recurring booking with monthly frequency
        $booking = $this->recurringBookingService->createRecurringBooking(
            $bookingData,
            'monthly',
            [
                'count' => 4 // Create 4 instances
            ]
        );
        
        // Verify the parent booking is correct
        $this->assertNotNull($booking);
        $this->assertTrue($booking->is_recurring);
        $this->assertTrue($booking->is_recurring_parent);
        $this->assertNotNull($booking->rrule_string);
        $this->assertStringContainsString('FREQ=MONTHLY', $booking->rrule_string);
        
        // Verify that recurring instances were created
        $recurringBookings = $booking->recurringBookings;
        $this->assertCount(3, $recurringBookings); // 3 instances + 1 parent = 4 total
        
        // Verify all recurring instances have the correct properties
        $previousDate = $booking->start_time;
        foreach ($recurringBookings as $instance) {
            $this->assertEquals($booking->id, $instance->recurring_booking_id);
            $this->assertTrue($instance->is_recurring);
            $this->assertFalse($instance->is_recurring_parent);
            
            // Verify each instance has the same day of month
            $this->assertEquals(
                $booking->start_time->day, 
                $instance->start_time->day, 
                'Recurring instance should be on the same day of month'
            );
            
            // Verify each instance is 1 month after the previous one
            $this->assertTrue(
                $instance->start_time->gt($previousDate),
                'Each instance should be after the previous one'
            );
            
            $previousDate = $instance->start_time;
        }
    }

    /**
     * @test
     * @covers REQ-007, REQ-008, REQ-013
     */
    public function it_can_update_recurring_bookings()
    {
        // Create a weekly recurring booking with 4 instances
        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);
        $endTime = $startTime->copy()->addHours(2);
        
        $bookingData = [
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => 'Original booking notes',
        ];
        
        $booking = $this->recurringBookingService->createRecurringBooking(
            $bookingData,
            'weekly',
            ['count' => 4]
        );
        
        // Update the booking attributes
        $updatedAttributes = [
            'notes' => 'Updated booking notes',
            'total_price' => 50.00, // Change price
        ];
        
        // Update all recurrences
        $updatedBookings = $this->recurringBookingService->updateRecurringBooking(
            $booking,
            $updatedAttributes
        );
        
        // Verify updates were applied
        $this->assertNotNull($updatedBookings);
        
        // Reload the parent booking
        $booking->refresh();
        
        // Verify parent booking was updated
        $this->assertEquals('Updated booking notes', $booking->notes);
        $this->assertEquals(50.00, $booking->total_price);
        
        // Verify all instances were updated
        foreach ($booking->recurringBookings as $instance) {
            $this->assertEquals('Updated booking notes', $instance->notes);
            $this->assertEquals(50.00, $instance->total_price);
        }
    }

    /**
     * @test
     * @covers REQ-007, REQ-008, REQ-013
     */
    public function it_can_cancel_all_recurring_bookings()
    {
        // Create a weekly recurring booking with 4 instances
        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);
        $endTime = $startTime->copy()->addHours(2);
        
        $bookingData = [
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        
        $booking = $this->recurringBookingService->createRecurringBooking(
            $bookingData,
            'weekly',
            ['count' => 4]
        );
        
        // Count initial instances
        $initialCount = $booking->recurringBookings->count() + 1; // +1 for parent
        
        // Cancel all instances
        $cancelledCount = $this->recurringBookingService->cancelRecurringBooking(
            $booking,
            'Cancelled by user',
            false // Cancel all instances
        );
        
        // Verify all bookings were cancelled
        $this->assertEquals($initialCount, $cancelledCount);
        
        // Reload the parent booking
        $booking->refresh();
        
        // Verify parent booking was cancelled
        $this->assertInstanceOf(\CorvMC\PracticeSpace\Models\States\BookingState\CancelledState::class, $booking->state);
        $this->assertEquals('Cancelled by user', $booking->cancellation_reason);
        
        // Verify all instances were cancelled
        foreach ($booking->recurringBookings as $instance) {
            $this->assertInstanceOf(\CorvMC\PracticeSpace\Models\States\BookingState\CancelledState::class, $instance->state);
            $this->assertEquals('Cancelled by user', $instance->cancellation_reason);
        }
    }

    /**
     * @test
     * @covers REQ-007, REQ-008, REQ-013
     */
    public function it_can_cancel_future_recurring_bookings()
    {
        // Create a weekly recurring booking with 6 instances
        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);
        $endTime = $startTime->copy()->addHours(2);
        
        $bookingData = [
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        
        $booking = $this->recurringBookingService->createRecurringBooking(
            $bookingData,
            'weekly',
            ['count' => 6]
        );
        
        // Get the third instance (2nd index in zero-based array)
        $instances = $booking->recurringBookings->sortBy('start_time')->values();
        $thirdInstance = $instances[1]; // 0-indexed, so 1 is the second instance
        
        // Cancel third instance and all future instances
        $cancelledCount = $this->recurringBookingService->cancelRecurringBooking(
            $thirdInstance,
            'Cancelled future instances',
            true // Cancel only this and future instances
        );
        
        // Reload the bookings
        $booking->refresh();
        $firstInstance = $instances[0];
        $firstInstance->refresh();
        $thirdInstance->refresh();
        
        // Verify parent booking was NOT cancelled
        $this->assertNotInstanceOf(\CorvMC\PracticeSpace\Models\States\BookingState\CancelledState::class, $booking->state);
        
        // Verify first instance was NOT cancelled
        $this->assertNotInstanceOf(\CorvMC\PracticeSpace\Models\States\BookingState\CancelledState::class, $firstInstance->state);
        
        // Verify third instance and all subsequent ones were cancelled
        $this->assertInstanceOf(\CorvMC\PracticeSpace\Models\States\BookingState\CancelledState::class, $thirdInstance->state);
        $this->assertEquals('Cancelled future instances', $thirdInstance->cancellation_reason);
        
        // Verify future instances are also cancelled
        $futureInstances = Booking::where('recurring_booking_id', $booking->id)
            ->where('start_time', '>=', $thirdInstance->start_time)
            ->get();
            
        foreach ($futureInstances as $instance) {
            $this->assertInstanceOf(\CorvMC\PracticeSpace\Models\States\BookingState\CancelledState::class, $instance->state);
        }
    }

    /**
     * @test
     * @covers REQ-007, REQ-008, REQ-013
     */
    public function it_can_check_for_conflicts_in_recurring_bookings()
    {
        // Create an existing booking
        $existingStartTime = Carbon::now()->addDays(8)->setTime(14, 0);
        $existingEndTime = $existingStartTime->copy()->addHours(2);
        
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => $existingStartTime,
            'end_time' => $existingEndTime,
        ]);
        
        // Create a base booking that will conflict on the second week
        $startTime = Carbon::now()->addDays(1)->setTime(14, 0);
        $endTime = $startTime->copy()->addHours(2);
        
        // Check for conflicts
        $conflicts = $this->recurringBookingService->checkRecurringDateConflicts(
            $this->room,
            $startTime,
            $endTime,
            'weekly',
            ['count' => 4]
        );
        
        // There should be one conflict on the second occurrence
        $this->assertCount(1, $conflicts);
        
        // The conflicting date should be around 7 days after our start date
        $conflictingDate = $conflicts[0]['date']['start_time'];
        
        // The conflicts should be with the existing booking we created
        $conflictingBookingId = $conflicts[0]['conflicting_bookings'][0]['id'];
        $this->assertNotNull($conflictingBookingId);
        
        // Verify the conflict is on the same day of week as our start date
        $this->assertEquals(
            $startTime->dayOfWeek,
            $conflictingDate->dayOfWeek,
            'Conflict should be on the same day of week'
        );
    }
} 