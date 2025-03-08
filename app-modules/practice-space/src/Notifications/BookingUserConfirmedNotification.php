<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingUserConfirmedNotification extends Notification implements ShouldQueue
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
        
        return (new MailMessage)
            ->subject("Booking Confirmed: {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Thank you for confirming your booking for {$roomName}.")
            ->line("**Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Booking ID:** {$this->booking->id}")
            ->action('View Booking Details', url('/practice-space/bookings/' . $this->booking->id))
            ->line("We look forward to seeing you at your scheduled time. Please remember to check in when you arrive.")
            ->line("If you need to make any changes to your booking, please do so at least 24 hours in advance.");
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
            'confirmed_at' => $this->booking->confirmed_at,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $startTime = $this->booking->start_time->format('l, F j \a\t g:i A');
        
        return [
            'title' => "Booking Confirmed: {$roomName}",
            'icon' => 'heroicon-o-check-circle',
            'iconColor' => 'success',
            'body' => "You have confirmed your booking for {$roomName} on {$startTime}.",
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
        $endTime = $this->booking->end_time->format('g:i A');
        $confirmedAt = $this->booking->confirmed_at->format('l, F j \a\t g:i A');
        
        return [
            'title' => "Booking Confirmed: {$roomName}",
            'icon' => 'heroicon-o-check-circle',
            'iconColor' => 'success',
            'body' => "You have confirmed your booking for {$roomName} from {$startTime} to {$endTime}. Confirmation was received on {$confirmedAt}.",
            'actions' => [
                [
                    'label' => 'View Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id),
                ],
            ],
        ];
    }
} 