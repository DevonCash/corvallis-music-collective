<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class BookingConfirmationRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The booking instance.
     *
     * @var \CorvMC\PracticeSpace\Models\Booking
     */
    public $booking;

    public $confirmationDeadline;

    /**
     * Create a new notification instance.
     *
     * @param \CorvMC\PracticeSpace\Models\Booking $booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        $this->confirmationDeadline = $booking->start_time->subDays(1);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $confirmUrl = url(route('practice-space.bookings.confirm', [
            'booking' => $this->booking->id,
            'token' => hash_hmac('sha256', $this->booking->id . $notifiable->email, config('app.key')),
        ]));

        $cancelUrl = url(route('practice-space.bookings.cancel', [
            'booking' => $this->booking->id,
            'token' => hash_hmac('sha256', $this->booking->id . $notifiable->email, config('app.key')),
        ]));

        return (new MailMessage)
            ->subject('Action Required: Confirm Your Booking')
            ->markdown(
                'practice-space::emails.bookings.confirmation-request',
                [
                    'user' => $notifiable,
                    'booking' => $this->booking,
                    'confirmUrl' => $confirmUrl,
                    'cancelUrl' => $cancelUrl,
                ]
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'room_name' => $this->booking->room->name,
            'start_time' => $this->booking->start_time->toIso8601String(),
            'end_time' => $this->booking->end_time->toIso8601String(),
            'confirmation_deadline' => $this->confirmationDeadline->toIso8601String(),
            'type' => 'booking_confirmation_request',
        ];
    }

    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $confirmByTime = $this->confirmationDeadline->format('l, F j, Y \a\t g:i A');

        return [
            'title' => "Action Required: Confirm Booking",
            'icon' => 'heroicon-o-exclamation-circle',
            'iconColor' => 'warning',
            'body' => "Please confirm your booking for {$roomName} by {$confirmByTime}.",
            'actions' => [
                [
                    'label' => 'View Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id),
                ],
            ],
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $startTime = $this->booking->start_time->format('l, F j, Y \a\t g:i A');
        $confirmByTime = $this->booking->start_time->addDays(3)->format('l, F j, Y \a\t g:i A');

        return [
            'title' => "Action Required: Confirm Your Booking",
            'icon' => 'heroicon-o-exclamation-circle',
            'iconColor' => 'warning',
            'body' => "You need to confirm your booking for {$roomName} on {$startTime} by {$confirmByTime}.",
            'actions' => [
                [
                    'label' => 'Confirm Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id . '/confirm'),
                ],
                [
                    'label' => 'Cancel Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id . '/cancel'),
                ],
            ],
        ];
    }
}
