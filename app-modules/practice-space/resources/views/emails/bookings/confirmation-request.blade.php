@component('mail::message')
# Action Required: Confirm Your Practice Space Booking

Hello {{ $userName }},

You have an upcoming booking for **{{ $roomName }}** that requires your confirmation.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}

**Please confirm by {{ $confirmByTime }} or your booking may be cancelled.**

@component('mail::button', ['url' => $confirmUrl])
Confirm Booking
@endcomponent

If you can no longer attend this session, please cancel your booking:

@component('mail::button', ['url' => $cancelUrl, 'color' => 'red'])
Cancel Booking
@endcomponent

If you don't confirm within the time window, your booking will be automatically cancelled and the space will be made available to others.

Thank you for using our practice spaces!

Regards,<br>
{{ config('app.name') }}
@endcomponent 