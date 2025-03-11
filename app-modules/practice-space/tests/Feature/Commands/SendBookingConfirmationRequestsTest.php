<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationRequestNotification;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

class SendBookingConfirmationRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-confirmation-requests@example.com',
            'name' => 'Test Confirmation Requests User',
        ]);
        
        // Create a room
        $this->room = Room::factory()->create([
            'hourly_rate' => 25.00,
        ]);
    }

    /** @test */
    public function it_sends_confirmation_requests_to_bookings_in_confirmation_window()
    {
        Notification::fake();
        
        // Create a booking that has entered the confirmation window
        $inWindowBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(2)->setHour(10),
            'end_time' => now()->addDays(2)->setHour(12),
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDay(), // 1 day ago
            'confirmation_deadline' => now()->addDay(), // 1 day in the future
        ]);
        
        // Create a booking that has not yet entered the confirmation window
        $futureWindowBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(5)->setHour(10),
            'end_time' => now()->addDays(5)->setHour(12),
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->addDay(), // 1 day in the future
            'confirmation_deadline' => now()->addDays(3), // 3 days in the future
        ]);
        
        // Run the command
        Artisan::call('practice-space:send-confirmation-requests');
        
        // Check that a notification was sent for the booking in the confirmation window
        Notification::assertSentTo(
            $this->testUser,
            BookingConfirmationRequestNotification::class,
            function ($notification, $channels) use ($inWindowBooking) {
                return $notification->booking->id === $inWindowBooking->id;
            }
        );
        
        // Check that no notification was sent for the booking not yet in the confirmation window
        Notification::assertNotSentTo(
            $this->testUser,
            BookingConfirmationRequestNotification::class,
            function ($notification, $channels) use ($futureWindowBooking) {
                return $notification->booking->id === $futureWindowBooking->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notifications_for_already_confirmed_bookings()
    {
        Notification::fake();
        
        // Create a booking that has entered the confirmation window but is already confirmed
        $confirmedBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(2)->setHour(10),
            'end_time' => now()->addDays(2)->setHour(12),
            'state' => 'confirmed',
            'confirmation_requested_at' => now()->subDay(), // 1 day ago
            'confirmation_deadline' => now()->addDay(), // 1 day in the future
            'confirmed_at' => now()->subHours(2), // Confirmed 2 hours ago
        ]);
        
        // Run the command
        Artisan::call('practice-space:send-confirmation-requests');
        
        // Check that no notification was sent for the already confirmed booking
        Notification::assertNotSentTo(
            $this->testUser,
            BookingConfirmationRequestNotification::class,
            function ($notification, $channels) use ($confirmedBooking) {
                return $notification->booking->id === $confirmedBooking->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notifications_in_dry_run_mode()
    {
        Notification::fake();
        
        // Create a booking that has entered the confirmation window
        $inWindowBooking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDays(2)->setHour(10),
            'end_time' => now()->addDays(2)->setHour(12),
            'state' => 'scheduled',
            'confirmation_requested_at' => now()->subDay(), // 1 day ago
            'confirmation_deadline' => now()->addDay(), // 1 day in the future
        ]);
        
        // Run the command in dry-run mode
        Artisan::call('practice-space:send-confirmation-requests', ['--dry-run' => true]);
        
        // Check that no notification was sent
        Notification::assertNothingSent();
    }
} 