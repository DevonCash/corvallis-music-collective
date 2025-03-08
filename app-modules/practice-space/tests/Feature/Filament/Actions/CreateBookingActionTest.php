<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Filament\Actions;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Services\BookingService;
use CorvMC\PracticeSpace\Tests\TestCase;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;

class CreateBookingActionTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;
    protected $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-booking-action@example.com',
            'name' => 'Test Booking Action User',
        ]);
        
        // Create a room category
        $category = RoomCategory::factory()->create([
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ]);
        
        // Create a room
        $this->room = Room::factory()->create([
            'room_category_id' => $category->id,
            'name' => 'Test Room',
            'description' => 'Test Room Description',
            'capacity' => 10,
            'hourly_rate' => 25.00,
            'is_active' => true,
        ]);
        
        // Mock the BookingService to avoid external dependencies
        $this->bookingService = $this->mockBookingService();
        
        // Set the authenticated user
        $this->actingAs($this->testUser);
    }
    
    /**
     * Mock the BookingService class
     */
    protected function mockBookingService(): MockInterface
    {
        $mock = Mockery::mock(BookingService::class);
        
        // Mock the methods that are called in the CreateBookingAction
        $mock->shouldReceive('getRoomOptions')
            ->andReturn([$this->room->id => $this->room->name]);
            
        $mock->shouldReceive('getRoomById')
            ->with($this->room->id)
            ->andReturn($this->room);
            
        $mock->shouldReceive('getFullyBookedDates')
            ->andReturn([]);
            
        $mock->shouldReceive('getAvailableTimeSlots')
            ->andReturn([
                '09:00' => '9:00 AM',
                '09:30' => '9:30 AM',
                '10:00' => '10:00 AM',
                '10:30' => '10:30 AM',
            ]);
            
        $mock->shouldReceive('getAvailableDurations')
            ->andReturn([
                0.5 => '30 minutes',
                1 => '1 hour',
                1.5 => '1.5 hours',
                2 => '2 hours',
            ]);
            
        $mock->shouldReceive('isRoomAvailable')
            ->andReturn(true);
            
        $mock->shouldReceive('calculateBookingTimes')
            ->andReturn([
                'start_datetime' => Carbon::parse('2023-01-01 10:00:00'),
                'end_datetime' => Carbon::parse('2023-01-01 12:00:00'),
            ]);
            
        $mock->shouldReceive('createBookingInstance')
            ->andReturn(new Booking([
                'room_id' => $this->room->id,
                'user_id' => $this->testUser->id,
                'start_time' => Carbon::parse('2023-01-01 10:00:00'),
                'end_time' => Carbon::parse('2023-01-01 12:00:00'),
                'state' => 'reserved',
                'total_price' => 50.00,
            ]));
            
        $mock->shouldReceive('createBooking')
            ->andReturn(new Booking([
                'id' => 1,
                'room_id' => $this->room->id,
                'user_id' => $this->testUser->id,
                'start_time' => Carbon::parse('2023-01-01 10:00:00'),
                'end_time' => Carbon::parse('2023-01-01 12:00:00'),
                'state' => 'reserved',
                'total_price' => 50.00,
            ]));
            
        // Bind the mock to the container
        $this->app->instance(BookingService::class, $mock);
        
        return $mock;
    }

    /** @test */
    public function it_can_create_action()
    {
        // Test that the action can be created
        $action = CreateBookingAction::make();
        
        $this->assertInstanceOf(Action::class, $action);
        $this->assertEquals('create_booking', $action->getName());
        $this->assertEquals('Book a Room', $action->getLabel());
    }
    
    /** @test */
    public function it_has_correct_action_configuration()
    {
        // Test that the action has the correct configuration
        $action = CreateBookingAction::make();
        
        // Check the action configuration
        $this->assertEquals('primary', $action->getColor());
        $this->assertEquals('Schedule a Practice Room', $action->getModalHeading());
        $this->assertEquals('Book a practice room for your rehearsal or practice session', $action->getModalDescription());
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 