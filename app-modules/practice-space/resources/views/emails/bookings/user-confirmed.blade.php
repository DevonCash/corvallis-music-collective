@component('mail::message')
# Booking Confirmed: {{ $roomName }}

Hello {{ $userName }},

Thank you for confirming your booking for **{{ $roomName }}**.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}

@component('mail::button', ['url' => $viewUrl])
View Booking Details
@endcomponent

We look forward to seeing you at your scheduled time. Please remember to check in when you arrive.

If you need to make any changes to your booking, please do so at least 24 hours in advance.

Regards,<br>
{{ config('app.name') }}
@endcomponent 