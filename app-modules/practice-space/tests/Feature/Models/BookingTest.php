<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Models;

use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\States\BookingState;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Models\States\BookingState\ConfirmedState;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Carbon\Carbon;

class BookingTest extends TestCase
{
    use RefreshDatabase;
    
    protected $testUser;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-booking-model@example.com',
            'name' => 'Test Booking Model User',
        ]);
    }

    /**
     * @test
     * @covers REQ-007
     */
    public function it_can_create_a_booking()
    {
        $room = Room::factory()->create([
            'timezone' => 'UTC', // Explicitly set timezone to UTC
        ]);
        
        $startTime = Carbon::now()->addDay()->setTimezone('UTC'); // Explicitly use UTC
        $endTime = $startTime->copy()->addHours(2);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
        ]);

        $this->assertDatabaseHas('practice_space_bookings', [
            'user_id' => $this->testUser->id,
            'room_id' => $room->id,
        ]);
        
        // Use start_time_utc to compare with UTC time
        $this->assertEquals(
            $startTime->format('Y-m-d H:i'), 
            $booking->start_time_utc->format('Y-m-d H:i'),
            'Start time in UTC does not match the expected value'
        );
        
        // Use end_time_utc to compare with UTC time
        $this->assertEquals(
            $endTime->format('Y-m-d H:i'), 
            $booking->end_time_utc->format('Y-m-d H:i'),
            'End time in UTC does not match the expected value'
        );
        
        $this->assertEquals('scheduled', $booking->getRawOriginal('state'));
    }

    /**
     * @test
     * @covers REQ-007
     * @covers REQ-020
     */
    public function it_has_user_relationship()
    {
        $room = Room::factory()->create();
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $room->id,
        ]);
        
        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertEquals($this->testUser->id, $booking->user->id);
    }

    /**
     * @test
     * @covers REQ-007
     */
    public function it_has_room_relationship()
    {
        $room = Room::factory()->create([
            'name' => 'Test Room',
            'hourly_rate' => 25.00,
        ]);
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $room->id,
        ]);
        
        $this->assertInstanceOf(Room::class, $booking->room);
        $this->assertEquals($room->id, $booking->room->id);
        $this->assertEquals('Test Room', $booking->room->name);
    }

    /**
     * @test
     * @covers REQ-007
     */
    public function it_has_default_state_of_scheduled()
    {
        $room = Room::factory()->create();
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $room->id,
            // Note: not specifying state, should default to scheduled
        ]);
        
        $this->assertInstanceOf(ScheduledState::class, $booking->state);
        $this->assertEquals('scheduled', $booking->getRawOriginal('state'));
    }

    /**
     * @test
     * @covers REQ-007
     * @covers REQ-011
     */
    public function it_can_transition_state()
    {
        $room = Room::factory()->create();
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $room->id,
            'state' => 'scheduled',
        ]);
        
        // Ensure we're working with the model instance, not a string
        $this->assertInstanceOf(ScheduledState::class, $booking->state);
        
        // Transition from scheduled to confirmed
        $booking->state->transitionTo($booking, ConfirmedState::class);
        
        // Refresh the model from the database
        $booking->refresh();
        
        // Check that the state was updated
        $this->assertInstanceOf(ConfirmedState::class, $booking->state);
        $this->assertEquals('confirmed', $booking->getRawOriginal('state'));
    }
} 