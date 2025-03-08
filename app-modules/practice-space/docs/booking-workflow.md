# Practice Space Booking Workflow

This document outlines the complete booking workflow for practice spaces, including the notification system that supports each stage of the process.

## Booking States

The Practice Space module uses a state machine to track the lifecycle of bookings:

1. **Scheduled**: Initial state for all new bookings
   - Booking has been created and approved (if approval is required)
   - Awaiting user confirmation

2. **Confirmed**: Booking has been confirmed by the user
   - User has explicitly confirmed they will attend the session
   - Not available until x days before the booking, where x is defined by the booked room's Booking Policy
   - If confirmation not recieved at least y hours before the booking, it's cancelled automatically. Y is also defined in the room's Booking Policy

3. **CheckedIn**: User has arrived and checked in to the practice space
   - User is currently using the space
   - Tracked for attendance and space utilization metrics
   - If payments are available, payment must be accepted before the user can be checked in. The check in is done by a member of staff.

4. **Completed**: Practice session has been successfully completed
   - User has checked out and the session is over
   - Room is available for the next booking

5. **Cancelled**: Booking was cancelled before it occurred
   - Can happen from Scheduled or Confirmed states
   - May be initiated by user, admin, or system (e.g., due to no confirmation)

6. **NoShow**: User did not arrive for their confirmed booking
   - Tracked for attendance and reliability metrics
   - May affect future booking privileges

## Booking Workflow

### 1. Booking Creation Phase
- User creates a booking request for a practice space
- System validates availability and user permissions
- If approved, booking enters **Scheduled** state
- Initial booking confirmation is sent to user

### 2. User Confirmation Phase
- System sends confirmation request to user (e.g., 48 hours before booking)
- User must confirm their intention to use the space
- If user confirms, booking transitions to **Confirmed** state
- If user doesn't confirm within the deadline, booking may transition to **Cancelled** state
- Reminder notifications are sent if confirmation is pending

### 3. Pre-Session Phase
- Reminder notifications are sent to confirmed users (e.g., 24 hours, 1 hour before)
- Any equipment requests or special arrangements are confirmed
- Room is prepared according to booking requirements

### 4. Active Session Phase
- User arrives and checks in (manually or via system)
- Booking transitions to **CheckedIn** state
- System may send notifications about session start/end times
- Staff may be notified of any issues or assistance needed

### 5. Post-Session Phase
- User completes session and checks out
- Booking transitions to **Completed** state
- System sends feedback request to user
- Payment receipt/confirmation is sent if applicable
- Room status is updated for next booking

## Notification Points

The following notifications support the booking workflow:

### Booking Creation & Confirmation
1. **Booking Created Notification**: Sent when booking is initially created
2. **Confirmation Request Notification**: Sent to request explicit confirmation from user
3. **Booking Confirmed Notification**: Sent when user confirms their booking
4. **Confirmation Reminder Notification**: Sent if user hasn't confirmed within a certain timeframe
5. **Booking Cancelled Due To No Confirmation Notification**: Sent if booking is cancelled due to lack of confirmation

### Session Preparation & Reminders
6. **Booking Reminder Notification**: Sent before scheduled session (e.g., 24 hours, 1 hour)
7. **Equipment Request Confirmation Notification**: Sent when equipment requests are approved

### Session Activity
8. **Check-In Notification**: Sent when user checks in
9. **Check-Out Notification**: Sent when user checks out

### Post-Session
10. **Feedback Request Notification**: Sent after session to request feedback
11. **Booking Payment Receipt Notification**: Sent after payment is processed

### Administrative Notifications
12. **Booking Cancellation Notification**: Sent when booking is cancelled
13. **Booking Modification Notification**: Sent when booking details are changed
14. **Waitlist Notification**: Sent when a waitlisted user can now book a previously unavailable slot
15. **Maintenance Notification**: Sent when a room becomes unavailable due to maintenance

## Confirmation Timing and Rules

### Confirmation Request Timing
- Standard bookings: 48 hours before scheduled time
- High-demand rooms: 72 hours before scheduled time
- Last-minute bookings: Immediate confirmation required at booking time

### Confirmation Window
- Users have 24 hours to confirm after receiving the request
- For bookings made less than 24 hours in advance, confirmation must be immediate

### Automated Actions
- If user doesn't confirm within the window, a reminder is sent
- If still unconfirmed 12 hours before the booking, the booking is automatically cancelled
- Cancelled slots are made available to waitlisted users

## Testing Email Notifications

For testing the notification system without using a real email service:

### Development Environment
1. **Log Driver**: Configure mail to use the 'log' driver in development
   ```php
   // .env
   MAIL_MAILER=log
   ```
   Emails will be written to `storage/logs/laravel.log`

2. **Mailtrap/Mailhog**: For more realistic testing
   ```php
   // .env
   MAIL_MAILER=smtp
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   ```

### Testing Tools
1. **Mail Fake**: For unit tests
   ```php
   Mail::fake();
   
   // Perform action that triggers notification
   
   Mail::assertSent(BookingConfirmationNotification::class);
   ```

2. **Notification Fake**: For notification-specific tests
   ```php
   Notification::fake();
   
   // Perform action that triggers notification
   
   Notification::assertSentTo($user, BookingConfirmationNotification::class);
   ```

3. **Manual Testing**: Use Tinker to send test notifications
   ```php
   $booking = \CorvMC\PracticeSpace\Models\Booking::find(1);
   $user = \App\Models\User::find(1);
   $user->notify(new \CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification($booking));
   ``` 