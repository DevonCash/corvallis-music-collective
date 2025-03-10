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

class CreateBookingActionTest extends TestCase
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