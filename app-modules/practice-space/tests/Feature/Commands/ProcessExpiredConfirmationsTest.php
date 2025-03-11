<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Models\States\BookingState\CancelledState;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class ProcessExpiredConfirmationsTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-expired-confirmations@example.com',
            'name' => 'Test Expired Confirmations User',
        ]);
        
        // Create a room
        $this->room = Room::factory()->create([
            'hourly_rate' => 25.00,
        ]);
    }

    /** @test */
    public function it_cancels_bookings_with_expired_confirmation_deadlines()
    {
        // Create a booking with an expired confirmation deadline
        $expiredBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDays(3),
            'confirmation_deadline' => now()->subDay(),
        ]);
        
        // Create a booking with a future confirmation deadline
        $validBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(3)->setHour(10),
            'end_time' => now()->addDays(3)->setHour(12),
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDay(),
            'confirmation_deadline' => now()->addDay(),
        ]);
        
        // Run the command
        Artisan::call('practice-space:process-expired-confirmations');
        
        // Refresh the bookings from the database
        $expiredBooking->refresh();
        $validBooking->refresh();
        
        // Check that the expired booking was cancelled
        $this->assertEquals('cancelled', $expiredBooking->getRawOriginal('state'));
        $this->assertInstanceOf(CancelledState::class, $expiredBooking->state);
        $this->assertNotNull($expiredBooking->cancelled_at);
        $this->assertEquals('Automatically cancelled due to missed confirmation deadline', $expiredBooking->cancellation_reason);
        
        // Check that the valid booking was not affected
        $this->assertEquals('scheduled', $validBooking->getRawOriginal('state'));
        $this->assertInstanceOf(ScheduledState::class, $validBooking->state);
        $this->assertNull($validBooking->cancelled_at);
    }

    /** @test */
    public function it_does_not_make_changes_in_dry_run_mode()
    {
        // Create a booking with an expired confirmation deadline
        $expiredBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDays(3),
            'confirmation_deadline' => now()->subDay(),
        ]);
        
        // Run the command in dry-run mode
        Artisan::call('practice-space:process-expired-confirmations', ['--dry-run' => true]);
        
        // Refresh the booking from the database
        $expiredBooking->refresh();
        
        // Check that the booking was not affected
        $this->assertEquals('scheduled', $expiredBooking->getRawOriginal('state'));
        $this->assertInstanceOf(ScheduledState::class, $expiredBooking->state);
        $this->assertNull($expiredBooking->cancelled_at);
    }
} 