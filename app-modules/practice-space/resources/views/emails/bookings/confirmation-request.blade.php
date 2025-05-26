<x-mail::message>
# Action Required: Confirm Your Practice Space Booking

Hello {{ $user->name }}!

You have an upcoming booking for **{{ $booking->room->name }}** that requires your confirmation.

<x-mail::panel>
### Booking Details
- **Date and Time:** {{ $booking->start_time->format('l, F j, Y \a\t g:i A') }} to {{ $booking->end_time->format('l, F j, Y \a\t g:i A') }}
- **Room:** {{ $booking->room->name }}
- **Booking ID:** {{ $booking->id }}
</x-mail::panel>

**Please confirm by {{ $booking->start_time->subDay()->format('l, F j, Y \a\t g:i A') }} or your booking may be cancelled.**

<x-mail::button :url="$confirmUrl" color="success">
    Confirm Booking
</x-mail::button>

If you can no longer attend this session, please cancel your booking:

<x-mail::button :url="$cancelUrl" color="red">
    Cancel Booking
</x-mail::button>

If you don't confirm within the time window, your booking will be automatically cancelled and the space will be made
available to others.

Thank you for using our practice space!<br/>
{{ config('app.name') }}
</x-mail::message>
