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

    protected Booking $booking;
    protected int $confirmationWindowHours;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, int $confirmationWindowHours = 24)
    {
        $this->booking = $booking;
        $this->confirmationWindowHours = $confirmationWindowHours;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $roomName = $this->booking->room->name;
        $startTime = $this->booking->start_time->format('l, F j, Y \a\t g:i A');
        $endTime = $this->booking->end_time->format('g:i A');
        $confirmByTime = now()->addHours($this->confirmationWindowHours)->format('l, F j \a\t g:i A');
        
        // Generate a signed URL for confirmation
        $confirmUrl = URL::temporarySignedRoute(
            'practice-space.bookings.confirm',
            now()->addHours($this->confirmationWindowHours),
            ['booking' => $this->booking->id]
        );
        
        // Generate a signed URL for cancellation
        $cancelUrl = URL::temporarySignedRoute(
            'practice-space.bookings.cancel',
            now()->addHours($this->confirmationWindowHours),
            ['booking' => $this->booking->id]
        );
        
        return (new MailMessage)
            ->subject("Action Required: Confirm Your Practice Space Booking")
            ->markdown('practice-space::emails.bookings.confirmation-request', [
                'userName' => $notifiable->name,
                'roomName' => $roomName,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'bookingId' => $this->booking->id,
                'confirmByTime' => $confirmByTime,
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'start_time' => $this->booking->start_time,
            'end_time' => $this->booking->end_time,
            'confirmation_deadline' => now()->addHours($this->confirmationWindowHours),
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $confirmByTime = now()->addHours($this->confirmationWindowHours)->format('l, F j \a\t g:i A');
        
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
        $confirmByTime = now()->addHours($this->confirmationWindowHours)->format('l, F j \a\t g:i A');
        
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