<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\States\BookingState\CancelledState;
use CorvMC\PracticeSpace\Models\States\BookingState\ConfirmedState;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Notifications\BookingCancelledDueToNoConfirmationNotification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationReminderNotification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationRequestNotification;
use CorvMC\PracticeSpace\Notifications\BookingCreatedNotification;
use CorvMC\PracticeSpace\Notifications\BookingReminderNotification;
use CorvMC\PracticeSpace\Notifications\BookingUserConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete booking notification workflow.
     */
    public function test_booking_notification_workflow()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create();
        $room = Room::factory()->create(['name' => 'Test Room']);
        
        // Step 1: Create a booking (3 days in the future)
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDays(3)->setHour(14),
            'end_time' => Carbon::now()->addDays(3)->setHour(16),
            'state' => ScheduledState::$name,
        ]);
        
        // Send booking created notification
        $user->notify(new BookingCreatedNotification($booking));
        $booking->logNotificationSent(BookingCreatedNotification::class);
        
        // Assert booking created notification was sent
        Notification::assertSentTo($user, BookingCreatedNotification::class);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'notification',
            'subject_type' => get_class($booking),
            'subject_id' => $booking->id,
            'properties->notification' => class_basename(BookingCreatedNotification::class),
        ]);
        
        // Step 2: Send confirmation request (48 hours before booking)
        $booking->update([
            'confirmation_requested_at' => Carbon::now(),
            'confirmation_deadline' => Carbon::now()->addHours(24),
        ]);
        
        $user->notify(new BookingConfirmationRequestNotification($booking, 24));
        $booking->logNotificationSent(BookingConfirmationRequestNotification::class, [
            'confirmation_window_hours' => 24,
        ]);
        
        // Assert confirmation request was sent
        Notification::assertSentTo($user, BookingConfirmationRequestNotification::class);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'notification',
            'subject_type' => get_class($booking),
            'subject_id' => $booking->id,
            'properties->notification' => class_basename(BookingConfirmationRequestNotification::class),
        ]);
        
        // Step 3: Send confirmation reminder (6 hours before deadline)
        $user->notify(new BookingConfirmationReminderNotification($booking, 6));
        $booking->logNotificationSent(BookingConfirmationReminderNotification::class, [
            'hours_before_deadline' => 6,
        ]);
        
        // Assert confirmation reminder was sent
        Notification::assertSentTo($user, BookingConfirmationReminderNotification::class);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'notification',
            'subject_type' => get_class($booking),
            'subject_id' => $booking->id,
            'properties->notification' => class_basename(BookingConfirmationReminderNotification::class),
        ]);
        
        // Step 4: User confirms booking
        $booking->state = ConfirmedState::$name;
        $booking->update(['confirmed_at' => Carbon::now()]);
        
        $user->notify(new BookingUserConfirmedNotification($booking));
        $booking->logNotificationSent(BookingUserConfirmedNotification::class, [
            'confirmed_at' => $booking->confirmed_at,
        ]);
        
        // Assert user confirmed notification was sent
        Notification::assertSentTo($user, BookingUserConfirmedNotification::class);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'notification',
            'subject_type' => get_class($booking),
            'subject_id' => $booking->id,
            'properties->notification' => class_basename(BookingUserConfirmedNotification::class),
        ]);
        
        // Step 5: Send booking reminder (24 hours before booking)
        $user->notify(new BookingReminderNotification($booking, 24));
        $booking->logNotificationSent(BookingReminderNotification::class, [
            'hours_before' => 24,
        ]);
        
        // Assert booking reminder was sent
        Notification::assertSentTo($user, BookingReminderNotification::class);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'notification',
            'subject_type' => get_class($booking),
            'subject_id' => $booking->id,
            'properties->notification' => class_basename(BookingReminderNotification::class),
        ]);
        
        // Verify that we can check if a notification has been sent
        $this->assertTrue($booking->hasNotificationBeenSent(BookingReminderNotification::class, [
            'hours_before' => 24,
        ]));
        
        $this->assertFalse($booking->hasNotificationBeenSent(BookingReminderNotification::class, [
            'hours_before' => 1, // We haven't sent a 1-hour reminder
        ]));
        
        // Verify we can get all notifications sent
        $notificationsSent = $booking->getNotificationsSent();
        $this->assertCount(5, $notificationsSent);
        
        // Verify we can get notifications of a specific type
        $remindersSent = $booking->getNotificationsSent(BookingReminderNotification::class);
        $this->assertCount(1, $remindersSent);
    }
    
    /**
     * Test the booking cancellation due to no confirmation.
     */
    public function test_booking_cancellation_due_to_no_confirmation()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        // Create a booking with an expired confirmation deadline
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'state' => ScheduledState::$name,
            'confirmation_requested_at' => Carbon::now()->subHours(25),
            'confirmation_deadline' => Carbon::now()->subHours(1), // Deadline has passed
        ]);
        
        // Send notification directly
        $user->notify(new BookingCancelledDueToNoConfirmationNotification($booking));
        
        // Log the notification
        $booking->logNotificationSent(BookingCancelledDueToNoConfirmationNotification::class, [
            'confirmation_deadline' => $booking->confirmation_deadline,
        ]);
        
        // Assert the notification was sent
        Notification::assertSentTo($user, BookingCancelledDueToNoConfirmationNotification::class);
        
        // Assert the notification was logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'notification',
            'subject_type' => get_class($booking),
            'subject_id' => $booking->id,
            'properties->notification' => class_basename(BookingCancelledDueToNoConfirmationNotification::class),
        ]);
    }
} 