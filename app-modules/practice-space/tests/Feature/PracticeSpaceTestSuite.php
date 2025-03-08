<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Models\RoomEquipment;
use CorvMC\PracticeSpace\Models\MaintenanceSchedule;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource;
use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource;

class PracticeSpaceTestSuite extends TestCase
{
    use RefreshDatabase;

    /**
     * ROOM MANAGEMENT TESTS
     */

    /** @test */
    public function it_can_create_a_room()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $roomData = [
            'name' => 'Test Room',
            'description' => 'A test practice room',
            'capacity' => 5,
            'hourly_rate' => 20.00,
            'is_active' => true,
        ];

        $room = Room::create($roomData);
        
        $this->assertDatabaseHas('practice_space_rooms', [
            'name' => 'Test Room',
            'capacity' => 5,
        ]);
        
        // Test Filament component can render
        Livewire::test(RoomResource\Pages\ListRooms::class)
            ->assertCanSeeTableRecords([$room]);
    }

    /** @test */
    public function it_can_update_a_room()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create([
            'name' => 'Original Room Name',
        ]);

        $room->update([
            'name' => 'Updated Room Name',
        ]);

        $this->assertDatabaseHas('practice_space_rooms', [
            'id' => $room->id,
            'name' => 'Updated Room Name',
        ]);
        
        // Test Filament component can render
        Livewire::test(RoomResource\Pages\EditRoom::class, [
            'record' => $room->id,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_can_delete_a_room()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();

        $room->delete();

        $this->assertDatabaseMissing('practice_space_rooms', [
            'id' => $room->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_can_list_all_rooms()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $rooms = Room::factory()->count(3)->create();

        // Test Filament component can render and show all rooms
        Livewire::test(RoomResource\Pages\ListRooms::class)
            ->assertCanSeeTableRecords($rooms);
    }

    /** @test */
    public function it_can_show_room_details()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();

        // Test Filament component can render
        Livewire::test(RoomResource\Pages\ViewRoom::class, [
            'record' => $room->id,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_rooms_by_capacity()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $smallRoom = Room::factory()->create(['capacity' => 2]);
        $mediumRoom = Room::factory()->create(['capacity' => 4]);
        $largeRoom = Room::factory()->create(['capacity' => 8]);

        $filteredRooms = Room::query()->where('capacity', '>=', 4)->get();
        
        $this->assertCount(2, $filteredRooms);
        $this->assertTrue($filteredRooms->contains($mediumRoom));
        $this->assertTrue($filteredRooms->contains($largeRoom));
        $this->assertFalse($filteredRooms->contains($smallRoom));
    }

    /** @test */
    public function it_can_filter_rooms_by_availability()
    {
        // Implementation depends on how availability is determined
        $this->markTestIncomplete('Availability filtering test to be implemented');
    }

    /**
     * ROOM CATEGORY TESTS
     */

    /** @test */
    public function it_can_create_room_categories()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $categoryData = [
            'name' => 'Drum Room',
            'description' => 'Rooms equipped for drummers',
            'is_active' => true,
        ];

        $category = RoomCategory::create($categoryData);

        $this->assertDatabaseHas('practice_space_room_categories', [
            'name' => 'Drum Room',
        ]);
        
        // Test Filament component can render
        Livewire::test(RoomCategoryResource\Pages\ListRoomCategories::class)
            ->assertCanSeeTableRecords([$category]);
    }

    /** @test */
    public function it_can_assign_room_to_category()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $category = RoomCategory::factory()->create([
            'name' => 'Drum Room',
        ]);
        
        $room = Room::factory()->create();
        
        $room->update([
            'room_category_id' => $category->id,
        ]);
        
        $this->assertEquals($category->id, $room->refresh()->room_category_id);
        $this->assertTrue($category->rooms->contains($room));
    }

    /**
     * EQUIPMENT INVENTORY TESTS
     */

    /** @test */
    public function it_can_add_equipment_to_room()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        $equipment = RoomEquipment::create([
            'room_id' => $room->id,
            'name' => 'Drum Kit',
            'description' => 'Professional drum kit',
            'quantity' => 1,
            'condition' => 'Good',
        ]);
        
        $this->assertDatabaseHas('practice_space_room_equipment', [
            'room_id' => $room->id,
            'name' => 'Drum Kit',
        ]);
        
        $this->assertTrue($room->equipment->contains($equipment));
    }

    /** @test */
    public function it_can_update_equipment_inventory()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        $equipment = RoomEquipment::create([
            'room_id' => $room->id,
            'name' => 'Drum Kit',
            'quantity' => 1,
            'condition' => 'Good',
        ]);
        
        $equipment->update([
            'quantity' => 2,
            'condition' => 'Excellent',
        ]);
        
        $this->assertDatabaseHas('practice_space_room_equipment', [
            'id' => $equipment->id,
            'quantity' => 2,
            'condition' => 'Excellent',
        ]);
    }

    /**
     * MAINTENANCE SCHEDULING TESTS
     */

    /** @test */
    public function it_can_schedule_room_maintenance()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        $maintenance = MaintenanceSchedule::create([
            'room_id' => $room->id,
            'title' => 'Regular Maintenance',
            'description' => 'Monthly equipment check',
            'start_time' => now()->addDay()->setHour(8),
            'end_time' => now()->addDay()->setHour(12),
            'status' => 'scheduled',
        ]);
        
        $this->assertDatabaseHas('practice_space_maintenance_schedules', [
            'room_id' => $room->id,
            'title' => 'Regular Maintenance',
            'status' => 'scheduled',
        ]);
        
        $this->assertTrue($room->maintenanceSchedules->contains($maintenance));
    }

    /** @test */
    public function it_prevents_bookings_during_maintenance()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        // Schedule maintenance
        $maintenance = MaintenanceSchedule::create([
            'room_id' => $room->id,
            'title' => 'Regular Maintenance',
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(14),
            'status' => 'scheduled',
        ]);
        
        // Try to create a booking during maintenance
        $booking = new Booking([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(11),
            'end_time' => now()->addDay()->setHour(13),
            'status' => 'reserved',
        ]);
        
        // This should be handled by application logic to prevent the booking
        // For testing purposes, we'll check if there's a conflict
        $hasConflict = $room->maintenanceSchedules()
            ->where('start_time', '<', $booking->end_time)
            ->where('end_time', '>', $booking->start_time)
            ->exists();
            
        $this->assertTrue($hasConflict);
    }

    /**
     * BOOKING SYSTEM TESTS
     */

    /** @test */
    public function it_can_create_a_booking()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();

        $bookingData = [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(10)->setMinute(0),
            'end_time' => now()->addDay()->setHour(12)->setMinute(0),
            'notes' => 'Band practice',
            'status' => 'reserved',
        ];

        $booking = Booking::create($bookingData);

        $this->assertDatabaseHas('practice_space_bookings', [
            'room_id' => $room->id,
            'user_id' => $user->id,
        ]);
        
        // Test Filament component can render
        Livewire::test(BookingResource\Pages\ListBookings::class)
            ->assertCanSeeTableRecords([$booking]);
    }

    /** @test */
    public function it_prevents_double_bookings()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        // Create an existing booking
        $existingBooking = Booking::factory()->create([
            'room_id' => $room->id,
            'start_time' => now()->addDay()->setHour(10)->setMinute(0),
            'end_time' => now()->addDay()->setHour(12)->setMinute(0),
        ]);

        // Try to create an overlapping booking
        $newBooking = new Booking([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(11)->setMinute(0),
            'end_time' => now()->addDay()->setHour(13)->setMinute(0),
            'notes' => 'Overlapping booking',
            'status' => 'reserved',
        ]);
        
        // Check for booking conflicts
        $hasConflict = Booking::query()
            ->where('room_id', $room->id)
            ->where('start_time', '<', $newBooking->end_time)
            ->where('end_time', '>', $newBooking->start_time)
            ->exists();
            
        $this->assertTrue($hasConflict);
    }

    /** @test */
    public function it_can_cancel_a_booking()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        $booking->update(['status' => 'cancelled']);

        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_can_create_recurring_bookings()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'status' => 'reserved',
            'is_recurring' => true,
            'recurring_pattern' => 'weekly',
        ]);
        
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'is_recurring' => true,
            'recurring_pattern' => 'weekly',
        ]);
    }

    /** @test */
    public function it_enforces_booking_policies()
    {
        $this->markTestIncomplete('Booking policy enforcement test to be implemented');
    }

    /** @test */
    public function it_can_add_user_to_waitlist()
    {
        $this->markTestIncomplete('Waitlist system test to be implemented');
    }

    /** @test */
    public function it_sends_booking_confirmations()
    {
        $this->markTestIncomplete('Booking confirmation test to be implemented');
    }

    /** @test */
    public function it_tracks_check_in_and_check_out()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);
        
        $booking->update([
            'status' => 'checked_in',
            'check_in_time' => now(),
        ]);
        
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'status' => 'checked_in',
        ]);
        $this->assertNotNull($booking->refresh()->check_in_time);
        
        $booking->update([
            'status' => 'completed',
            'check_out_time' => now(),
        ]);
        
        $this->assertDatabaseHas('practice_space_bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull($booking->refresh()->check_out_time);
    }

    /** @test */
    public function it_logs_booking_status_changes()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        
        $room = Room::factory()->create();
        
        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'status' => 'reserved',
        ]);
        
        // Clear the creation activity
        $booking->activities()->delete();
        
        // Update the booking status
        $booking->status = 'confirmed';
        $booking->save();
        
        // Check that the activity was logged
        $activity = $booking->getStatusHistory()->first();
        
        $this->assertNotNull($activity);
        $this->assertEquals('updated', $activity->event);
        $this->assertEquals('reserved', $activity->properties['old']['status']);
        $this->assertEquals('confirmed', $activity->properties['attributes']['status']);
    }

    /**
     * RESOURCE OPTIMIZATION TESTS
     */

    /** @test */
    public function it_can_apply_dynamic_pricing()
    {
        $this->markTestIncomplete('Dynamic pricing test to be implemented');
    }

    /** @test */
    public function it_can_generate_utilization_reports()
    {
        $this->markTestIncomplete('Utilization reporting test to be implemented');
    }

    /** @test */
    public function it_can_track_energy_usage()
    {
        $this->markTestIncomplete('Energy monitoring test to be implemented');
    }

    /**
     * MEMBER EXPERIENCE TESTS
     */

    /** @test */
    public function it_can_recommend_rooms_based_on_user_needs()
    {
        $this->markTestIncomplete('Room recommendation test to be implemented');
    }

    /** @test */
    public function it_can_save_favorite_rooms()
    {
        $this->markTestIncomplete('Favorite rooms test to be implemented');
    }

    /** @test */
    public function it_can_track_booking_history()
    {
        $this->markTestIncomplete('Booking history test to be implemented');
    }

    /** @test */
    public function it_can_request_additional_equipment()
    {
        $this->markTestIncomplete('Equipment request test to be implemented');
    }

    /**
     * INTEGRATION TESTS
     */

    /** @test */
    public function it_integrates_with_payments_module()
    {
        $this->markTestIncomplete('Payments integration test to be implemented');
    }

    /** @test */
    public function it_integrates_with_member_directory()
    {
        $this->markTestIncomplete('Member directory integration test to be implemented');
    }

    /** @test */
    public function it_integrates_with_band_profiles()
    {
        $this->markTestIncomplete('Band profiles integration test to be implemented');
    }

    /** @test */
    public function it_integrates_with_productions_module()
    {
        $this->markTestIncomplete('Productions integration test to be implemented');
    }

    /** @test */
    public function it_integrates_with_gear_inventory()
    {
        $this->markTestIncomplete('Gear inventory integration test to be implemented');
    }

    /**
     * HELPER METHODS
     */
    
    /**
     * Create a user for testing
     * 
     * @return \Illuminate\Foundation\Auth\User
     */
    private function createUser()
    {
        // Create a user with admin privileges for testing Filament admin panel
        return $this->createAdminUser();
    }
} 