<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Filament;

use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages\ListBookings;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages\CreateBooking;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages\EditBooking;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages\ViewBooking;
use Filament\Facades\Filament;
use App\Models\User;

class BookingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;
    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-booking@example.com',
            'name' => 'Test Booking User',
        ]);

        // Create a room for bookings
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
        ]);
        
        // Create a sample booking
        $this->booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'status' => 'reserved',
        ]);
        
        // Ensure we're logged in for all tests
        $this->actingAs($this->createAdminUser());
        
        // Set the Filament admin panel for resource tests
        if (method_exists(Filament::class, 'setCurrentPanel') && Filament::hasPanel('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }
    }

    /**
     * Set up testing environment for Livewire component tests
     */
    private function setUpLivewireTest()
    {
        // Make sure we're using the admin panel for resource tests
        if (method_exists(Filament::class, 'setCurrentPanel') && Filament::hasPanel('admin')) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }
    }

    /**
     * @test
     * @covers PS-BOOKING-LIST-001
     */
    public function it_can_render_index_page()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        // Create multiple bookings for listing
        Booking::factory()->count(2)->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
        ]);
        
        // Test the Livewire component
        Livewire::test(ListBookings::class)
            ->assertSuccessful();
    }

    /**
     * @test
     * @covers PS-BOOKING-CREATE-001
     */
    public function it_can_render_create_page()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        // Test the Livewire component
        Livewire::test(CreateBooking::class)
            ->assertSuccessful();
    }

    /**
     * @test
     * @covers PS-BOOKING-CREATE-002
     */
    public function it_can_create_booking()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        $newData = [
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(12, 0),
            'status' => 'reserved',
        ];
        
        // Get the count before creating
        $countBefore = Booking::count();
        
        // Test the form submission process
        Livewire::test(CreateBooking::class)
            ->fillForm([
                'room_id' => $newData['room_id'],
                'user_id' => $newData['user_id'],
                'start_time' => $newData['start_time'],
                'end_time' => $newData['end_time'],
                'status' => $newData['status'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        // Verify a new booking was created
        $this->assertEquals($countBefore + 1, Booking::count());
    }

    /**
     * @test
     * @covers PS-BOOKING-EDIT-001
     */
    public function it_can_render_edit_page()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        // Test the Livewire component
        Livewire::test(EditBooking::class, [
            'record' => $this->booking->id,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @covers PS-BOOKING-EDIT-002
     */
    public function it_can_update_booking()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        $newNotes = 'Updated booking notes via Livewire';
        
        // Test updating via the form
        Livewire::test(EditBooking::class, [
            'record' => $this->booking->id,
        ])
            ->fillForm([
                'notes' => $newNotes,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        
        // Verify the booking was updated
        $this->booking->refresh();
        $this->assertEquals($newNotes, $this->booking->notes);
    }

    /**
     * @test
     * @covers PS-BOOKING-VIEW-001
     */
    public function it_can_render_view_page()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        // Test the Livewire component
        Livewire::test(ViewBooking::class, [
            'record' => $this->booking->id,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @covers PS-BOOKING-CANCEL-001
     */
    public function it_can_cancel_booking()
    {
        $this->markTestSkipped('Skipping Filament Livewire test until we resolve Filament test environment issues');
        
        $this->setUpLivewireTest();
        
        // Assuming there's a cancel action in the Filament resource
        Livewire::test(EditBooking::class, [
            'record' => $this->booking->id,
        ])
            ->fillForm([
                'status' => 'cancelled',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        
        // Verify the booking status was updated
        $this->booking->refresh();
        $this->assertEquals('cancelled', $this->booking->status);
    }
} 