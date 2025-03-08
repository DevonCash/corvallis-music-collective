<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking $booking;
    protected int $hoursUntilBooking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, int $hoursUntilBooking)
    {
        $this->booking = $booking;
        $this->hoursUntilBooking = $hoursUntilBooking;
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
        
        $reminderText = $this->hoursUntilBooking === 1 
            ? "Your booking is in 1 hour" 
            : "Your booking is in {$this->hoursUntilBooking} hours";
        
        return (new MailMessage)
            ->subject("Reminder: {$reminderText} - {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a reminder about your upcoming booking for {$roomName}.")
            ->line("**Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Booking ID:** {$this->booking->id}")
            ->line("Please remember to arrive on time and check in with staff when you arrive.")
            ->when($this->booking->room->equipment->count() > 0, function (MailMessage $mail) {
                return $mail->line("**Available Equipment:** " . $this->booking->room->equipment->pluck('name')->join(', '));
            })
            ->action('View Booking Details', url('/practice-space/bookings/' . $this->booking->id))
            ->line("If you need to cancel, please do so as soon as possible so others can use the space.");
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
            'hours_until_booking' => $this->hoursUntilBooking,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $startTime = $this->booking->start_time->format('l, F j \a\t g:i A');
        
        $reminderText = $this->hoursUntilBooking === 1 
            ? "1 hour" 
            : "{$this->hoursUntilBooking} hours";
        
        return [
            'title' => "Booking Reminder: {$roomName}",
            'icon' => 'heroicon-o-bell',
            'iconColor' => 'primary',
            'body' => "Your booking for {$roomName} is in {$reminderText}. Starts at {$startTime}.",
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
        $startTime = $this->booking->start_time->format('l, F j \a\t g:i A');
        $endTime = $this->booking->end_time->format('g:i A');
        
        $reminderText = $this->hoursUntilBooking === 1 
            ? "1 hour" 
            : "{$this->hoursUntilBooking} hours";
        
        return [
            'title' => "Booking Reminder: {$roomName}",
            'icon' => 'heroicon-o-bell',
            'iconColor' => 'primary',
            'body' => "Your booking for {$roomName} is in {$reminderText}. It starts at {$startTime} and ends at {$endTime}. Please arrive on time and check in with staff when you arrive.",
            'actions' => [
                [
                    'label' => 'View Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id),
                ],
            ],
        ];
    }
} 