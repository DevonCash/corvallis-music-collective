<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedNotification extends Notification implements ShouldQueue
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
        $confirmationNeededBy = $this->booking->start_time->copy()->subHours(48)->format('l, F j');
        
        return (new MailMessage)
            ->subject("Booking Request Received: {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your booking request for {$roomName} has been received and is now in the **Scheduled** state.")
            ->line("**Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Booking ID:** {$this->booking->id}")
            ->line("**Important:** You will need to confirm this booking before {$confirmationNeededBy}. We'll send you a confirmation request email closer to the date.")
            ->line("If you need to cancel or modify this booking, please do so as early as possible.")
            ->action('View Booking Details', url('/practice-space/bookings/' . $this->booking->id))
            ->line('Thank you for using our practice spaces!');
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
            'title' => "Booking Created: {$roomName}",
            'icon' => 'heroicon-o-calendar',
            'iconColor' => 'info',
            'body' => "Your booking for {$roomName} on {$startTime} has been created.",
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
        $confirmationNeededBy = $this->booking->start_time->copy()->subHours(48)->format('l, F j');
        
        return [
            'title' => "Booking Created: {$roomName}",
            'icon' => 'heroicon-o-calendar',
            'iconColor' => 'info',
            'body' => "Your booking for {$roomName} from {$startTime} to {$endTime} has been created. You will need to confirm this booking before {$confirmationNeededBy}.",
            'actions' => [
                [
                    'label' => 'View Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id),
                ],
            ],
        ];
    }
} 