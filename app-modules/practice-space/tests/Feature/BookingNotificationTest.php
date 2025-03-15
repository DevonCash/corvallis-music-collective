<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\BookingReminderSent;
use CorvMC\PracticeSpace\Models\BookingConfirmationReminderSent;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification;
use CorvMC\PracticeSpace\Notifications\BookingReminderNotification;

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
        ]);
        
        // Create a booking for tomorrow
        $tomorrow = Carbon::tomorrow()->setHour(10)->setMinute(0);
        $this->booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $tomorrow,
            'end_time' => $tomorrow->copy()->addHours(2),
            'state' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_sends_booking_confirmation_notification_when_booking_is_created()
    {
        // The booking was created in setUp, so we should have a notification
        Notification::assertSentTo(
            $this->user,
            BookingConfirmationNotification::class,
            function ($notification, $channels) {
                return $notification->booking->id === $this->booking->id;
            }
        );
    }

    /** @test */
    public function it_sends_reminder_notification_24_hours_before_booking()
    {
        // Reset notification fake to clear the confirmation notification
        Notification::fake();
        
        // Set the booking time to be exactly 24 hours from now
        $exactlyOneDayFromNow = Carbon::now()->addDay();
        $this->booking->update([
            'start_time' => $exactlyOneDayFromNow,
            'end_time' => $exactlyOneDayFromNow->copy()->addHours(2),
        ]);
        
        // Trigger the reminder check (this would normally be done by a scheduled command)
        $this->artisan('practice-space:send-booking-reminders');
        
        // Assert that a reminder notification was sent
        Notification::assertSentTo(
            $this->user,
            BookingReminderNotification::class,
            function ($notification, $channels) {
                return $notification->booking->id === $this->booking->id;
            }
        );
        
        // Assert that a record was created to track that the reminder was sent
        $this->assertDatabaseHas('practice_space_booking_reminder_sent', [
            'booking_id' => $this->booking->id,
        ]);
    }

    /** @test */
    public function it_does_not_send_duplicate_reminder_notifications()
    {
        // Create a record indicating that a reminder has already been sent
        BookingReminderSent::create([
            'booking_id' => $this->booking->id,
        ]);
        
        // Reset notification fake
        Notification::fake();
        
        // Set the booking time to be exactly 24 hours from now
        $exactlyOneDayFromNow = Carbon::now()->addDay();
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

    /** @test */
    public function it_does_not_send_reminders_for_cancelled_bookings()
    {
        // Reset notification fake
        Notification::fake();
        
        // Set the booking time to be exactly 24 hours from now
        $exactlyOneDayFromNow = Carbon::now()->addDay();
        $this->booking->update([
            'start_time' => $exactlyOneDayFromNow,
            'end_time' => $exactlyOneDayFromNow->copy()->addHours(2),
            'state' => 'cancelled',
            'cancelled_at' => Carbon::now(),
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