@component('mail::message')
# Booking Cancelled: No Confirmation Received - {{ $roomName }}

Hello {{ $userName }},

Your booking for **{{ $roomName }}** has been automatically cancelled because we did not receive your confirmation by the deadline.

**Cancelled Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}
- **Confirmation Requested On:** {{ $requestedAt }}
- **Confirmation Deadline:** {{ $deadlineTime }}

This space will now be made available to other members.

If you still wish to use this space and it remains available, you can make a new booking.

@component('mail::button', ['url' => $bookAgainUrl])
Book Another Session
@endcomponent

Thank you for using our practice spaces.

Regards,<br>
{{ config('app.name') }}
@endcomponent 