<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Filament;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Facades\Filament;
use App\Models\User;

class RoomResourceTest extends TestCase
{
    use RefreshDatabase;
    
    protected $testUser;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-room-resource@example.com',
            'name' => 'Test Room Resource User',
        ]);
    }

    /** @test */
    public function it_can_render_index_page()
    {
        $this->actingAs($this->testUser);

        $rooms = Room::factory()->count(3)->create();

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\ListRooms::class)
            ->assertCanSeeTableRecords($rooms);
    }

    /** @test */
    public function it_can_render_create_page()
    {
        $this->actingAs($this->testUser);

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\CreateRoom::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_room()
    {
        $this->actingAs($this->testUser);

        $category = RoomCategory::factory()->create();

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\CreateRoom::class)
            ->fillForm([
                'name' => 'Test Room',
                'description' => 'Test Description',
                'capacity' => 10,
                'hourly_rate' => 25.00,
                'is_active' => true,
                'room_category_id' => $category->id,
                'amenities' => ['wifi', 'projector'], // Add amenities as an array
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('practice_space_rooms', [
            'name' => 'Test Room',
            'description' => 'Test Description',
            'capacity' => 10,
            'hourly_rate' => 25.00,
            'is_active' => 1,
            'room_category_id' => $category->id,
        ]);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create([
            'amenities' => json_encode(['wifi', 'projector']),
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(RoomResource\Pages\EditRoom::class, [
            'record' => $room->id,
        ])
            ->assertSuccessful()
            ->assertFormSet([
                'name' => $room->name,
                'description' => $room->description,
                'capacity' => $room->capacity,
                'hourly_rate' => $room->hourly_rate,
                'is_active' => $room->is_active,
            ]);
    }

    /** @test */
    public function it_can_update_room()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create([
            'amenities' => json_encode(['wifi']),
        ]);
        $category = RoomCategory::factory()->create();

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(RoomResource\Pages\EditRoom::class, [
            'record' => $room->id,
        ])
            ->fillForm([
                'name' => 'Updated Room',
                'description' => 'Updated Description',
                'capacity' => 15,
                'hourly_rate' => 30.00,
                'is_active' => false,
                'room_category_id' => $category->id,
                'amenities' => ['wifi', 'projector', 'sound_system'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('practice_space_rooms', [
            'id' => $room->id,
            'name' => 'Updated Room',
            'description' => 'Updated Description',
            'capacity' => 15,
            'hourly_rate' => 30.00,
            'is_active' => 0,
            'room_category_id' => $category->id,
        ]);
    }

    /** @test */
    public function it_can_render_view_page()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create([
            'amenities' => json_encode(['wifi', 'projector']),
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(RoomResource\Pages\ViewRoom::class, [
            'record' => $room->id,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_can_delete_room()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create([
            'amenities' => json_encode(['wifi', 'projector']),
        ]);

        // Use the Livewire test directly with the component class and record ID
        Livewire::test(RoomResource\Pages\EditRoom::class, [
            'record' => $room->id,
        ])
            ->callAction('delete');

        // Since the Room model doesn't use soft deletes, check that it's actually deleted
        $this->assertDatabaseMissing('practice_space_rooms', [
            'id' => $room->id,
        ]);
    }
} 