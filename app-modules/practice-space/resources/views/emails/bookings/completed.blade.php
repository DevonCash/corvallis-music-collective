@component('mail::message')
# Booking Completed: {{ $roomName }}

Hello {{ $userName }},

Your booking for **{{ $roomName }}** has been completed.

**Booking Details:**
- **Date and Time:** {{ $startTime }} to {{ $endTime }}
- **Room:** {{ $roomName }}
- **Check-In Time:** {{ $checkInTime }}
- **Check-Out Time:** {{ $checkOutTime }}
- **Total Duration:** {{ $duration }} hours
@if($totalPrice)
- **Total Cost:** ${{ $totalPrice }}
@endif

Thank you for using our practice spaces! We hope you had a productive session.

**Please take a moment to provide feedback on your experience:**

@component('mail::button', ['url' => $feedbackUrl])
Provide Feedback
@endcomponent

@component('mail::button', ['url' => $bookAgainUrl, 'color' => 'success'])
Book Again
@endcomponent

We look forward to seeing you again soon!

Regards,<br>
{{ config('app.name') }}
@endcomponent 