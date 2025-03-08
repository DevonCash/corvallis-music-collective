# Email Testing for Practice Space Module

This document provides instructions for testing email notifications in the Practice Space module without using a real email service.

## Available Notifications

The Practice Space module includes the following notification classes:

1. **BookingCreatedNotification**: Sent when a booking is initially created
2. **BookingConfirmationRequestNotification**: Sent to users requesting them to confirm their booking
3. **BookingConfirmationReminderNotification**: Sent to remind users to confirm their booking
4. **BookingUserConfirmedNotification**: Sent when a user confirms their booking
5. **BookingReminderNotification**: Sent to remind users about upcoming bookings
6. **BookingCancelledDueToNoConfirmationNotification**: Sent when a booking is cancelled due to lack of confirmation
7. **BookingCancellationNotification**: Sent when a booking is cancelled
8. **BookingCheckInNotification**: Sent when a user checks in
9. **BookingCompletedNotification**: Sent when a booking is completed

## Testing Methods

### Method 1: Using Laravel's Log Driver

The simplest way to test emails without sending them is to use Laravel's log driver, which writes emails to the log file instead of sending them.

1. **Configure your environment**:
   ```
   # .env
   MAIL_MAILER=log
   ```

2. **Send a test notification**:
   ```php
   // Using Tinker
   $booking = \CorvMC\PracticeSpace\Models\Booking::first();
   $user = \App\Models\User::first();
   $user->notify(new \CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification($booking));
   ```

3. **Check the logs**:
   The email content will be written to `storage/logs/laravel.log`

### Method 2: Using Mailtrap or Mailhog

For more realistic testing, you can use a service like Mailtrap or Mailhog:

1. **Configure your environment for Mailhog**:
   ```
   # .env
   MAIL_MAILER=smtp
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   ```

2. **Send a test notification** (same as Method 1)

3. **Check Mailhog interface** (typically at http://localhost:8025)

### Method 3: Using the Command Line

The module includes several commands to send notifications:

```bash
# Test confirmation requests with dry run (no emails sent)
php artisan practice-space:send-confirmation-requests --dry-run

# Send actual confirmation requests
php artisan practice-space:send-confirmation-requests

# Send booking reminders
php artisan practice-space:send-booking-reminders --hours=24

# Send confirmation reminders
php artisan practice-space:send-confirmation-reminders --hours=6

# Process expired confirmations
php artisan practice-space:process-expired-confirmations --dry-run
```

### Method 4: Using Unit Tests

For automated testing, use Laravel's notification fake:

```php
use Illuminate\Support\Facades\Notification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification;

// Arrange
Notification::fake();
$booking = Booking::factory()->create();
$user = User::find($booking->user_id);

// Act
$user->notify(new BookingConfirmationNotification($booking));

// Assert
Notification::assertSentTo(
    $user,
    BookingConfirmationNotification::class
);
```

## Tracking Notifications

The Practice Space module uses the activity log to track which notifications have been sent. This allows us to:

1. **Avoid sending duplicate notifications**
2. **Track notification history**
3. **Debug notification issues**

### Logging Notifications

When a notification is sent, it's logged to the activity log:

```php
$booking->logNotificationSent(BookingConfirmationNotification::class, [
    'metadata' => 'value',
]);
```

### Checking Notification History

You can check if a notification has been sent:

```php
// Check if a 24-hour reminder has been sent
$booking->hasNotificationBeenSent(BookingReminderNotification::class, [
    'hours_before' => 24,
]);

// Get all notifications sent for a booking
$notificationsSent = $booking->getNotificationsSent();

// Get all reminders sent for a booking
$remindersSent = $booking->getNotificationsSent(BookingReminderNotification::class);
```

## Debugging Email Content

To debug the actual content of emails:

1. **Render the notification to HTML**:
   ```php
   $notification = new BookingConfirmationNotification($booking);
   $mailMessage = $notification->toMail($user);
   $html = $mailMessage->render();
   ```

2. **Save to a file for inspection**:
   ```php
   file_put_contents(storage_path('app/email_preview.html'), $html);
   ```

## Adding New Notification Types

To add a new notification type:

1. Create a new class in `app-modules/practice-space/src/Notifications/`
2. Extend `Illuminate\Notifications\Notification`
3. Implement the `toMail()` method
4. Test using the methods described above

## Common Issues

- **Missing User Email**: Ensure the user model has an email address
- **Invalid Booking Data**: Ensure the booking has valid relationships (room, user)
- **Route Not Found**: For action buttons, ensure the routes exist and are registered
- **Markdown Rendering Issues**: Test with both plain text and HTML email clients 