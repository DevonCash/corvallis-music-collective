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

    /** @test */
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

    /** @test */
    public function it_can_render_create_page()
    {
        $this->actingAs($this->testUser);

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\CreateBooking::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_booking()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create();

        // Use the Livewire test directly with the component class
        Livewire::test(BookingResource\Pages\CreateBooking::class)
            ->fillForm([
                'room_id' => $room->id,
                'user_id' => $this->testUser->id,
                'start_time' => now()->addDay()->setHour(10),
                'end_time' => now()->addDay()->setHour(12),
                'notes' => 'Test booking',
                'status' => 'reserved',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('practice_space_bookings', [
            'room_id' => $room->id,
            'user_id' => $this->testUser->id,
            'notes' => 'Test booking',
            'status' => 'reserved',
        ]);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $this->actingAs($this->testUser);

        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(BookingResource\Pages\EditBooking::class, [
            'record' => $booking->id,
        ])
            ->assertSuccessful()
            ->assertFormSet([
                'room_id' => $booking->room_id,
                'user_id' => $booking->user_id,
                'notes' => $booking->notes,
                'status' => $booking->status,
            ]);
    }

    /** @test */
    public function it_can_update_booking()
    {
        $this->actingAs($this->testUser);

        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => 'reserved',
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(BookingResource\Pages\EditBooking::class, [
            'record' => $booking->id,
        ])
            ->fillForm([
                'status' => 'confirmed',
                'notes' => 'Updated notes',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'notes' => 'Updated notes',
        ]);
    }

    /** @test */
    public function it_can_render_view_page()
    {
        $this->actingAs($this->testUser);

        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(BookingResource\Pages\ViewBooking::class, [
            'record' => $booking->id,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_can_cancel_booking()
    {
        $this->actingAs($this->testUser);

        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => 'confirmed',
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(BookingResource\Pages\EditBooking::class, [
            'record' => $booking->id,
        ])
            ->fillForm([
                'status' => 'cancelled',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }
} 