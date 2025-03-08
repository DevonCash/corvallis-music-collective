@component('mail::message')
# Booking Cancelled: {{ $roomName }}

Hello {{ $userName }},

Your booking for **{{ $roomName }}** has been cancelled.

**Cancelled Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}
- **Cancellation Reason:** {{ $reason }}

If you did not intend to cancel this booking, please contact us immediately.

@component('mail::button', ['url' => $bookAgainUrl])
Book Another Session
@endcomponent

Thank you for using our practice spaces.

Regards,<br>
{{ config('app.name') }}
@endcomponent 