@component('mail::message')
# Booking Confirmed: {{ $roomName }}

Hello {{ $userName }},

Your booking for **{{ $roomName }}** has been confirmed.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}

@component('mail::button', ['url' => $viewUrl])
View Booking Details
@endcomponent

Thank you for using our practice spaces!

Regards,<br>
{{ config('app.name') }}
@endcomponent 