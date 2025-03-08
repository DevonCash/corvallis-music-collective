@component('mail::message')
# Check-In Confirmed: {{ $roomName }}

Hello {{ $userName }},

You have successfully checked in to your booking for **{{ $roomName }}**.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Check-In Time:** {{ $checkInTime }}
- **Expected Check-Out Time:** {{ $checkOutTime }}

@if($hasEquipment)
**Available Equipment:** {{ $equipmentList }}
@endif

Please remember to check out when you're finished and leave the space clean and tidy for the next user.

If you need any assistance during your session, please contact staff.

@component('mail::button', ['url' => $viewUrl])
View Booking Details
@endcomponent

Enjoy your practice session!

Regards,<br>
{{ config('app.name') }}
@endcomponent 