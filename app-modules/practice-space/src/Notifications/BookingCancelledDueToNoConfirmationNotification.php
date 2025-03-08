<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledDueToNoConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
        $requestedAt = $this->booking->confirmation_requested_at->format('l, F j');
        $deadlineTime = $this->booking->confirmation_deadline->format('l, F j \a\t g:i A');
        
        return (new MailMessage)
            ->subject("Booking Cancelled: No Confirmation Received - {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your booking for {$roomName} has been automatically cancelled because we did not receive your confirmation by the deadline.")
            ->line("**Cancelled Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Booking ID:** {$this->booking->id}")
            ->line("- **Confirmation Requested On:** {$requestedAt}")
            ->line("- **Confirmation Deadline:** {$deadlineTime}")
            ->line("This space will now be made available to other members.")
            ->line("If you still wish to use this space and it remains available, you can make a new booking.")
            ->action('Book Another Session', url('/practice-space/bookings/create'))
            ->line("Thank you for using our practice spaces.");
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
            'confirmation_requested_at' => $this->booking->confirmation_requested_at,
            'confirmation_deadline' => $this->booking->confirmation_deadline,
            'cancelled_at' => $this->booking->cancelled_at,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $startTime = $this->booking->start_time->format('l, F j');
        
        return [
            'title' => "Booking Cancelled: No Confirmation",
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'body' => "Your booking for {$roomName} on {$startTime} was cancelled because it wasn't confirmed by the deadline.",
            'actions' => [
                [
                    'label' => 'Book Again',
                    'url' => url('/admin/practice-space/bookings/create'),
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
        $requestedAt = $this->booking->confirmation_requested_at->format('l, F j');
        $deadlineTime = $this->booking->confirmation_deadline->format('l, F j \a\t g:i A');
        
        return [
            'title' => "Booking Cancelled: No Confirmation",
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'body' => "Your booking for {$roomName} on {$startTime} was automatically cancelled because you did not confirm it by the deadline ({$deadlineTime}). Confirmation was requested on {$requestedAt}.",
            'actions' => [
                [
                    'label' => 'Book Again',
                    'url' => url('/admin/practice-space/bookings/create'),
                ],
            ],
        ];
    }
} 