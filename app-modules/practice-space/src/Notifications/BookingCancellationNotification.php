<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancellationNotification extends Notification implements ShouldQueue
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
        $reason = $this->booking->cancellation_reason ?? 'User requested cancellation';
        
        return (new MailMessage)
            ->subject("Booking Cancelled: {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your booking for {$roomName} has been cancelled.")
            ->line("**Cancelled Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Booking ID:** {$this->booking->id}")
            ->line("- **Cancellation Reason:** {$reason}")
            ->line("If you did not intend to cancel this booking, please contact us immediately.")
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
            'cancelled_at' => $this->booking->cancelled_at,
            'cancellation_reason' => $this->booking->cancellation_reason,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $startTime = $this->booking->start_time->format('l, F j');
        $reason = $this->booking->cancellation_reason ?? 'User requested cancellation';
        
        return [
            'title' => "Booking Cancelled: {$roomName}",
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'body' => "Your booking for {$roomName} on {$startTime} has been cancelled. Reason: {$reason}",
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
        $endTime = $this->booking->end_time->format('g:i A');
        $reason = $this->booking->cancellation_reason ?? 'User requested cancellation';
        
        return [
            'title' => "Booking Cancelled: {$roomName}",
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'body' => "Your booking for {$roomName} from {$startTime} to {$endTime} has been cancelled. Reason: {$reason}",
            'actions' => [
                [
                    'label' => 'Book Again',
                    'url' => url('/admin/practice-space/bookings/create'),
                ],
            ],
        ];
    }
} 