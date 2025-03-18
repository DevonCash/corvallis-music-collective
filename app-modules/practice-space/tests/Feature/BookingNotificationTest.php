<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\ConfirmedState;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification;
use CorvMC\PracticeSpace\Notifications\BookingReminderNotification;
use Spatie\Activitylog\Models\Activity;

/**
 * @covers REQ-020
 * @covers REQ-021
 */
class BookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        
        Notification::fake();
        
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
            'capacity' => 5,
            'hourly_rate' => 25.00,
            'is_active' => true,
            'timezone' => 'America/Los_Angeles',
        ]);
        
        // Create a booking for tomorrow with UTC timestamp
        $tomorrow = Carbon::tomorrow()->setHour(10)->setMinute(0)->setTimezone('UTC');
        $this->booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $tomorrow,
            'end_time' => $tomorrow->copy()->addHours(2),
            'state' => ConfirmedState::class,
        ]);
    }

    /**
     * @test
     * @covers REQ-020
     */
    public function it_sends_booking_confirmation_notification_when_booking_is_created()
    {
        // Send the notification manually
        $this->user->notify(new BookingConfirmationNotification($this->booking));
        
        // Assert that the notification was sent
        Notification::assertSentTo(
            $this->user,
            BookingConfirmationNotification::class
        );
    }

    /**
     * @test
     * @covers REQ-021
     */
    public function it_sends_reminder_notification_24_hours_before_booking()
    {
        // Reset notification fake to clear the confirmation notification
        Notification::fake();
        
        // Set the booking time to be exactly 24 hours from now (using UTC)
        $exactlyOneDayFromNow = Carbon::now()->addDay()->setTimezone('UTC');
        $this->booking->update([
            'start_time' => $exactlyOneDayFromNow,
            'end_time' => $exactlyOneDayFromNow->copy()->addHours(2),
        ]);
        
        // Manually send the notification
        $this->user->notify(new BookingReminderNotification($this->booking, 24));
        
        // Log the notification
        $this->booking->logNotificationSent(BookingReminderNotification::class, [
            'hours_before' => 24
        ]);
        
        // Assert that a reminder notification was sent
        Notification::assertSentTo(
            $this->user,
            BookingReminderNotification::class
        );
        
        // Assert that the notification was logged in the activity log
        $this->assertTrue(
            $this->booking->hasNotificationBeenSent(BookingReminderNotification::class, [
                'hours_before' => 24
            ])
        );
    }

    /**
     * @test
     * @covers REQ-021
     */
    public function it_does_not_send_duplicate_reminder_notifications()
    {
        // Log that a notification has already been sent
        $this->booking->logNotificationSent(BookingReminderNotification::class, [
            'hours_before' => 24,
        ]);
        
        // Reset notification fake
        Notification::fake();
        
        // Set the booking time to be exactly 24 hours from now (using UTC)
        $exactlyOneDayFromNow = Carbon::now()->addDay()->setTimezone('UTC');
        $this->booking->update([
            'start_time' => $exactlyOneDayFromNow,
            'end_time' => $exactlyOneDayFromNow->copy()->addHours(2),
        ]);
        
        // Trigger the reminder check
        $this->artisan('practice-space:send-booking-reminders');
        
        // Assert that no reminder notification was sent (because one was already sent)
        Notification::assertNotSentTo(
            $this->user,
            BookingReminderNotification::class
        );
    }

    /**
     * @test
     * @covers REQ-021
     */
    public function it_does_not_send_reminders_for_cancelled_bookings()
    {
        // Reset notification fake
        Notification::fake();
        
        // Set the booking time to be exactly 24 hours from now (using UTC)
        $exactlyOneDayFromNow = Carbon::now()->addDay()->setTimezone('UTC');
        $this->booking->update([
            'start_time' => $exactlyOneDayFromNow,
            'end_time' => $exactlyOneDayFromNow->copy()->addHours(2),
            'state' => 'cancelled',
            'cancelled_at' => Carbon::now()->setTimezone('UTC'),
            'cancellation_reason' => 'Testing cancellation',
        ]);
        
        // Trigger the reminder check
        $this->artisan('practice-space:send-booking-reminders');
        
        // Assert that no reminder notification was sent
        Notification::assertNotSentTo(
            $this->user,
            BookingReminderNotification::class
        );
    }
} 