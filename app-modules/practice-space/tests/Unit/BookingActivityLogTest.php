<?php

namespace CorvMC\PracticeSpace\Tests\Unit;

use App\Models\User;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_booking_creation()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'status' => 'reserved',
            'state' => 'scheduled',
            'notes' => 'Test booking',
        ]);
        
        $activity = $booking->activities()->first();
        
        $this->assertNotNull($activity);
        $this->assertEquals('created', $activity->event);
        $this->assertEquals('booking', $activity->log_name);
        $this->assertEquals('Booking was created', $activity->description);
    }
    
    /** @test */
    public function it_logs_booking_status_changes()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'status' => 'reserved',
            'state' => 'scheduled',
            'notes' => 'Test booking',
        ]);
        
        // Clear the creation activity
        $booking->activities()->delete();
        
        // Update the booking status
        $booking->status = 'confirmed';
        $booking->state = 'confirmed';
        $booking->save();
        
        $activity = $booking->activities()->first();
        
        $this->assertNotNull($activity);
        $this->assertEquals('updated', $activity->event);
        $this->assertEquals('booking', $activity->log_name);
        $this->assertEquals('Booking was updated', $activity->description);
        $this->assertEquals('reserved', $activity->properties['old']['status']);
        $this->assertEquals('confirmed', $activity->properties['attributes']['status']);
    }
    
    /** @test */
    public function it_can_retrieve_status_history()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        // Create a booking and manually log activities to simulate status changes
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'status' => 'reserved',
            'state' => 'scheduled',
            'notes' => 'Test booking',
        ]);
        
        // Get the creation activity
        $creationActivity = $booking->activities()->first();
        $this->assertNotNull($creationActivity);
        $this->assertEquals('created', $creationActivity->event);
        
        // Verify that we can retrieve the activity log
        $activities = $booking->activities()->get();
        $this->assertGreaterThanOrEqual(1, $activities->count());
        
        // Verify that the getStatusHistory method returns activities
        $statusHistory = $booking->getStatusHistory()->get();
        $this->assertGreaterThanOrEqual(1, $statusHistory->count());
        
        // Verify that the creation activity is included
        $this->assertNotNull($statusHistory->firstWhere('event', 'created'));
    }
} 