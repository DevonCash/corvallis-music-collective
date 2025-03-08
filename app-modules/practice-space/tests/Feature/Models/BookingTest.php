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

    /** @test */
    public function it_can_create_a_booking()
    {
        $room = Room::factory()->create();
        
        $startTime = Carbon::now()->addDay();
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
            'state' => 'scheduled',
        ]);
        
        $this->assertEquals($startTime->toDateTimeString(), $booking->start_time->toDateTimeString());
        $this->assertEquals($endTime->toDateTimeString(), $booking->end_time->toDateTimeString());
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'state' => 'scheduled',
        ]);

        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertEquals($this->testUser->id, $booking->user->id);
    }

    /** @test */
    public function it_has_room_relationship()
    {
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id, 
            'room_id' => $room->id,
            'state' => 'scheduled',
        ]);

        $this->assertInstanceOf(Room::class, $booking->room);
        $this->assertEquals($room->id, $booking->room->id);
    }

    /** @test */
    public function it_has_default_state_of_scheduled()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'state' => 'scheduled',
        ]);
        
        $this->assertEquals('scheduled', $booking->getRawOriginal('state'));
        $this->assertEquals('Scheduled', ScheduledState::getLabel());
    }

    /** @test */
    public function it_can_transition_state()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'state' => 'scheduled',
        ]);
        
        $this->assertEquals('scheduled', $booking->getRawOriginal('state'));
        
        // Transition to confirmed state
        $booking->state = 'confirmed';
        $booking->save();
        $booking->refresh();
        
        $this->assertEquals('confirmed', $booking->getRawOriginal('state'));
        $this->assertEquals('Confirmed', ConfirmedState::getLabel());
    }
} 