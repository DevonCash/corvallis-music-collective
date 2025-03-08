@component('mail::message')
# URGENT: {{ $reminderText }} to confirm your booking - {{ $roomName }}

Hello {{ $userName }},

**This is an urgent reminder that you need to confirm your booking for {{ $roomName }}.**

If you don't confirm by {{ $deadlineTime }}, your booking will be automatically cancelled and the space will be made available to others.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}

@component('mail::button', ['url' => $confirmUrl, 'color' => 'success'])
Confirm Booking Now
@endcomponent

If you can no longer attend this session, please cancel your booking:

@component('mail::button', ['url' => $cancelUrl, 'color' => 'red'])
Cancel Booking
@endcomponent

Thank you for using our practice spaces!

Regards,<br>
{{ config('app.name') }}
@endcomponent 