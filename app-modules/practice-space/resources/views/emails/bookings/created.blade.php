@component('mail::message')
# Booking Request Received: {{ $roomName }}

Hello {{ $userName }},

Your booking request for **{{ $roomName }}** has been received and is now in the **Scheduled** state.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}

**Important:** You will need to confirm this booking before {{ $confirmationNeededBy }}. We'll send you a confirmation request email closer to the date.

If you need to cancel or modify this booking, please do so as early as possible.

@component('mail::button', ['url' => $viewUrl])
View Booking Details
@endcomponent

Thank you for using our practice spaces!

Regards,<br>
{{ config('app.name') }}
@endcomponent 