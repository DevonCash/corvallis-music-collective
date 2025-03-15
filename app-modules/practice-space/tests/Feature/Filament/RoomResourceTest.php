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

    /**
     * @test
     * @covers UI-001
     */
    public function it_can_render_index_page()
    {
        $this->actingAs($this->testUser);

        $rooms = Room::factory()->count(3)->create();

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\ListRooms::class)
            ->assertCanSeeTableRecords($rooms);
    }

    /**
     * @test
     * @covers UI-002
     */
    public function it_can_render_create_page()
    {
        $this->actingAs($this->testUser);

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\CreateRoom::class)
            ->assertSuccessful();
    }

    /**
     * @test
     * @covers UI-002
     * @covers REQ-001
     */
    public function it_can_create_room()
    {
        $this->actingAs($this->testUser);

        $category = RoomCategory::factory()->create();

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\CreateRoom::class)
            ->fillForm([
                'name' => 'Test Room',
                'description' => 'A test practice room',
                'room_category_id' => $category->id,
                'capacity' => 5,
                'hourly_rate' => 20.00,
                'is_active' => true,
                'photos' => [],
                'specifications' => [
                    'size_sqft' => 200,
                    'has_windows' => true,
                    'floor_type' => 'carpet',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Check that the room was created in the database
        $this->assertDatabaseHas('practice_space_rooms', [
            'name' => 'Test Room',
            'description' => 'A test practice room',
            'room_category_id' => $category->id,
            'capacity' => 5,
            'hourly_rate' => 20.00,
            'is_active' => 1,
        ]);
    }

    /**
     * @test
     * @covers UI-002
     */
    public function it_can_render_edit_page()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create([
            'name' => 'Existing Room',
            'description' => 'An existing practice room',
            'capacity' => 4,
            'hourly_rate' => 15.00,
            'is_active' => true,
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\EditRoom::class, [
            'record' => $room->id,
        ])
            ->assertFormSet([
                'name' => 'Existing Room',
                'description' => 'An existing practice room',
                'capacity' => 4,
                'hourly_rate' => 15.00,
                'is_active' => true,
            ]);
    }

    /**
     * @test
     * @covers UI-002
     * @covers REQ-001
     */
    public function it_can_update_room()
    {
        $this->actingAs($this->testUser);

        $category = RoomCategory::factory()->create();
        $room = Room::factory()->create([
            'name' => 'Original Room',
            'description' => 'Original description',
            'room_category_id' => $category->id,
            'capacity' => 4,
            'hourly_rate' => 15.00,
            'is_active' => true,
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\EditRoom::class, [
            'record' => $room->id,
        ])
            ->fillForm([
                'name' => 'Updated Room',
                'description' => 'Updated description',
                'room_category_id' => $category->id,
                'capacity' => 6,
                'hourly_rate' => 25.00,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Check that the room was updated in the database
        $this->assertDatabaseHas('practice_space_rooms', [
            'id' => $room->id,
            'name' => 'Updated Room',
            'description' => 'Updated description',
            'room_category_id' => $category->id,
            'capacity' => 6,
            'hourly_rate' => 25.00,
            'is_active' => 0,
        ]);
    }

    /**
     * @test
     * @covers UI-003
     */
    public function it_can_render_view_page()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create([
            'name' => 'Viewable Room',
            'description' => 'A viewable practice room',
        ]);

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\ViewRoom::class, [
            'record' => $room->id,
        ])
            ->assertSuccessful()
            ->assertSee('Viewable Room');
    }

    /**
     * @test
     * @covers UI-001
     */
    public function it_can_delete_room()
    {
        $this->actingAs($this->testUser);

        $room = Room::factory()->create();

        // Use the Livewire test directly with the component class
        Livewire::test(RoomResource\Pages\ListRooms::class)
            ->assertCanSeeTableRecords([$room])
            ->callTableAction('delete', $room)
            ->assertCanNotSeeTableRecords([$room]);

        // Check that the room was deleted from the database
        $this->assertDatabaseMissing('practice_space_rooms', [
            'id' => $room->id,
        ]);
    }
} 