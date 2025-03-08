<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCheckInNotification extends Notification implements ShouldQueue
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
        $checkInTime = $this->booking->check_in_time->format('g:i A');
        $checkOutTime = $this->booking->end_time->format('g:i A');
        
        return (new MailMessage)
            ->subject("Check-In Confirmed: {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have successfully checked in to your booking for {$roomName}.")
            ->line("**Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Check-In Time:** {$checkInTime}")
            ->line("- **Expected Check-Out Time:** {$checkOutTime}")
            ->when($this->booking->room->equipment->count() > 0, function (MailMessage $mail) {
                return $mail->line("**Available Equipment:** " . $this->booking->room->equipment->pluck('name')->join(', '));
            })
            ->line("Please remember to check out when you're finished and leave the space clean and tidy for the next user.")
            ->line("If you need any assistance during your session, please contact staff.")
            ->action('View Booking Details', url('/practice-space/bookings/' . $this->booking->id))
            ->line("Enjoy your practice session!");
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
            'check_in_time' => $this->booking->check_in_time,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $checkInTime = $this->booking->check_in_time->format('g:i A');
        $checkOutTime = $this->booking->end_time->format('g:i A');
        
        return [
            'title' => "Checked In: {$roomName}",
            'icon' => 'heroicon-o-login',
            'iconColor' => 'success',
            'body' => "You've checked in to {$roomName} at {$checkInTime}. Expected check-out: {$checkOutTime}.",
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
        $startTime = $this->booking->start_time->format('l, F j, Y');
        $checkInTime = $this->booking->check_in_time->format('g:i A');
        $checkOutTime = $this->booking->end_time->format('g:i A');
        
        return [
            'title' => "Checked In: {$roomName}",
            'icon' => 'heroicon-o-login',
            'iconColor' => 'success',
            'body' => "You've successfully checked in to {$roomName} on {$startTime} at {$checkInTime}. Your expected check-out time is {$checkOutTime}. Enjoy your session!",
            'actions' => [
                [
                    'label' => 'View Booking',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id),
                ],
            ],
        ];
    }
} 