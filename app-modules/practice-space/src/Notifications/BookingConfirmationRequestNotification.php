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

    /**
     * Create a new notification instance.
     *
     * @param \CorvMC\PracticeSpace\Models\Booking $booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
        $confirmationDeadline = $this->booking->confirmation_deadline->format('l, F j, Y \a\t g:i A');
        $bookingDate = $this->booking->start_time->format('l, F j, Y');
        $bookingTime = $this->booking->start_time->format('g:i A') . ' - ' . $this->booking->end_time->format('g:i A');
        $roomName = $this->booking->room->name;
        
        $confirmUrl = url(route('practice-space.bookings.confirm', [
            'booking' => $this->booking->id,
            'token' => hash_hmac('sha256', $this->booking->id . $notifiable->email, config('app.key')),
        ]));
        
        $cancelUrl = url(route('practice-space.bookings.cancel', [
            'booking' => $this->booking->id,
            'token' => hash_hmac('sha256', $this->booking->id . $notifiable->email, config('app.key')),
        ]));

        return (new MailMessage)
            ->subject('Please Confirm Your Practice Space Booking')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have a practice space booking coming up that needs your confirmation:')
            ->line("**Room:** {$roomName}")
            ->line("**Date:** {$bookingDate}")
            ->line("**Time:** {$bookingTime}")
            ->line("**Please confirm by:** {$confirmationDeadline}")
            ->line('If you do not confirm by the deadline, your booking will be automatically cancelled.')
            ->action('Confirm Booking', $confirmUrl)
            ->line('If you no longer need this booking, you can cancel it:')
            ->action('Cancel Booking', $cancelUrl)
            ->line('Thank you for using our practice spaces!');
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
            'confirmation_deadline' => $this->booking->confirmation_deadline->toIso8601String(),
            'type' => 'booking_confirmation_request',
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $confirmByTime = $this->booking->confirmation_deadline->format('l, F j, Y \a\t g:i A');
        
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
        $confirmByTime = $this->booking->confirmation_deadline->format('l, F j, Y \a\t g:i A');
        
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