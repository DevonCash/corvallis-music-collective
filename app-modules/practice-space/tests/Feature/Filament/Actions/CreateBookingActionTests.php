<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Filament\Actions;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Tests\TestCase;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\HtmlString;

class CreateBookingActionTests extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;

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
        
        // Set the authenticated user
        $this->actingAs($this->testUser);
    }
    
    /**
     * Test that the action can be created
     */
    public function it_can_create_action()
    {
        $action = CreateBookingAction::make();
        $this->assertInstanceOf(Action::class, $action);
    }

    /**
     * Test that the generateTimeOptions method works correctly
     */
    public function it_generates_time_options()
    {
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass(CreateBookingAction::class);
        $method = $reflection->getMethod('generateTimeOptions');
        $method->setAccessible(true);
        
        // Call the method
        $timeOptions = $method->invoke(null);
        
        // Assert that the time options are generated correctly
        $this->assertIsArray($timeOptions);
        $this->assertNotEmpty($timeOptions);
        $this->assertArrayHasKey('09:00', $timeOptions);
        $this->assertEquals('9:00 AM', $timeOptions['09:00']);
    }

    /**
     * Test that the renderBookingSummary method works correctly
     */
    public function it_renders_booking_summary()
    {
        // Create a booking
        $booking = new Booking([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => Carbon::parse('2023-01-01 10:00:00'),
            'end_time' => Carbon::parse('2023-01-01 12:00:00'),
            'state' => 'reserved',
            'total_price' => 50.00,
        ]);
        
        // Set the room relationship
        $booking->setRelation('room', $this->room);
        
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass(CreateBookingAction::class);
        $method = $reflection->getMethod('renderBookingSummary');
        $method->setAccessible(true);
        
        // Call the method
        $summary = $method->invoke(null, $booking);
        
        // Assert that the summary is generated correctly
        $this->assertInstanceOf(HtmlString::class, $summary);
    }

    /**
     * Test that the action has the correct configuration
     */
    public function it_has_correct_action_configuration()
    {
        $action = CreateBookingAction::make();
        $this->assertEquals('create_booking', $action->getName());
        $this->assertEquals('Book a Room', $action->getLabel());
        $this->assertEquals('primary', $action->getColor());
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
} 