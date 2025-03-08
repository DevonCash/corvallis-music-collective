@component('mail::message')
# Reminder: {{ $reminderText }} - {{ $roomName }}

Hello {{ $userName }},

This is a reminder about your upcoming booking for **{{ $roomName }}**.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Booking ID:** {{ $bookingId }}

Please remember to arrive on time and check in with staff when you arrive.

@if($hasEquipment)
**Available Equipment:** {{ $equipmentList }}
@endif

@component('mail::button', ['url' => $viewUrl])
View Booking Details
@endcomponent

If you need to cancel, please do so as soon as possible so others can use the space.

Regards,<br>
{{ config('app.name') }}
@endcomponent 