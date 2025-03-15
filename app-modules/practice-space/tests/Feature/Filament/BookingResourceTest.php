<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Filament;

use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource;
use Filament\Facades\Filament;
use App\Models\User;

class BookingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-booking@example.com',
            'name' => 'Test Booking User',
        ]);
    }

    /**
     * @test
     * @covers UI-004
     */
    public function it_can_render_index_page()
    {
        $this->actingAs($this->testUser);

        $bookings = Booking::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\ListBookings::class)
            ->assertCanSeeTableRecords($bookings);
    }

    /**
     * @test
     * @covers UI-006
     */
    public function it_can_render_create_page()
    {
        $this->actingAs($this->testUser);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\CreateBooking::class)
            ->assertSuccessful();
    }

    /**
     * @test
     * @covers UI-006
     * @covers REQ-007
     */
    public function it_can_create_booking()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create();
        $startTime = now()->addDay()->setHour(10);
        $endTime = now()->addDay()->setHour(12);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\CreateBooking::class)
            ->fillForm([
                'room_id' => $room->id,
                'user_id' => $this->testUser->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => 'Test booking notes',
                'state' => 'scheduled',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Check that the booking was created in the database
        $this->assertDatabaseHas('practice_space_bookings', [
            'room_id' => $room->id,
            'user_id' => $this->testUser->id,
            'notes' => 'Test booking notes',
        ]);
    }

    /**
     * @test
     * @covers UI-006
     */
    public function it_can_render_edit_page()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $this->testUser->id,
            'notes' => 'Original notes',
            'state' => 'scheduled',
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\EditBooking::class, [
            'record' => $booking->id,
        ])
            ->assertFormSet([
                'room_id' => $room->id,
                'user_id' => $this->testUser->id,
                'notes' => 'Original notes',
            ]);
    }

    /**
     * @test
     * @covers UI-006
     * @covers REQ-007
     */
    public function it_can_update_booking()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $this->testUser->id,
            'notes' => 'Original notes',
            'state' => 'scheduled',
        ]);

        $newStartTime = now()->addDays(2)->setHour(14);
        $newEndTime = now()->addDays(2)->setHour(16);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\EditBooking::class, [
            'record' => $booking->id,
        ])
            ->fillForm([
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'notes' => 'Updated notes',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Check that the booking was updated in the database
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'notes' => 'Updated notes',
        ]);
    }

    /**
     * @test
     * @covers UI-005
     */
    public function it_can_render_view_page()
    {
        $this->actingAs($this->testUser);

        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'notes' => 'Viewable booking',
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\ViewBooking::class, [
            'record' => $booking->id,
        ])
            ->assertSuccessful()
            ->assertSee('Viewable booking');
    }

    /**
     * @test
     * @covers UI-007
     * @covers REQ-013
     */
    public function it_can_cancel_booking()
    {
        $this->actingAs($this->testUser);

        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'state' => 'confirmed',
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\EditBooking::class, [
            'record' => $booking->id,
        ])
            ->callAction('cancel');

        // Check that the booking was cancelled in the database
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'state' => 'cancelled',
        ]);
    }
} 