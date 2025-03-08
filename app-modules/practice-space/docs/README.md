# Practice Space Module Documentation

This directory contains documentation for the Practice Space module.

## Contents

- [Booking Workflow](booking-workflow.md): Detailed explanation of the booking workflow, including states and notifications
- [Email Testing](email-testing.md): Instructions for testing email notifications without using a real email service

## Email Notification System

The Practice Space module includes a comprehensive email notification system to support the booking workflow:

### Notification Classes

1. **BookingConfirmationNotification**: Sent to users when their booking is initially created
2. **BookingConfirmationRequestNotification**: Sent to users requesting them to confirm their booking
3. **BookingUserConfirmedNotification**: Sent to users when they confirm their booking
4. **BookingCancellationNotification**: Sent to users when their booking is cancelled

### Testing Tools

- **Command Line Tool**: `php artisan practice-space:send-confirmation-requests`
- **Unit Tests**: See `tests/Feature/NotificationTest.php`
- **Log Driver**: Configure `MAIL_MAILER=log` in `.env` for development testing

### Implementation Details

- **Controller**: `BookingConfirmationController` handles confirmation and cancellation actions
- **Routes**: Signed URLs for secure confirmation and cancellation
- **Database**: Additional fields for tracking confirmation status

## Getting Started

To test the email notification system:

1. Configure your environment for testing:
   ```
   # .env
   MAIL_MAILER=log
   ```

2. Run the command with dry-run option:
   ```bash
   php artisan practice-space:send-confirmation-requests --dry-run
   ```

3. Check the logs to see what would be sent:
   ```bash
   tail -f storage/logs/laravel.log
   ```

For more detailed instructions, see [Email Testing](email-testing.md). 