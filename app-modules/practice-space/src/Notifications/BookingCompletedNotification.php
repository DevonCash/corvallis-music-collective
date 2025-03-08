<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompletedNotification extends Notification implements ShouldQueue
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
        $checkOutTime = $this->booking->check_out_time->format('g:i A');
        $duration = $this->booking->getDurationInHours();
        
        return (new MailMessage)
            ->subject("Booking Completed: {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your booking for {$roomName} has been completed.")
            ->line("**Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Check-In Time:** {$checkInTime}")
            ->line("- **Check-Out Time:** {$checkOutTime}")
            ->line("- **Total Duration:** {$duration} hours")
            ->when($this->booking->total_price, function (MailMessage $mail) {
                return $mail->line("- **Total Cost:** $" . number_format($this->booking->total_price, 2));
            })
            ->line("Thank you for using our practice spaces! We hope you had a productive session.")
            ->line("**Please take a moment to provide feedback on your experience:**")
            ->action('Provide Feedback', url('/practice-space/bookings/' . $this->booking->id . '/feedback'))
            ->line("We look forward to seeing you again soon!");
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
            'check_out_time' => $this->booking->check_out_time,
            'total_price' => $this->booking->total_price,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $duration = $this->booking->getDurationInHours();
        
        return [
            'title' => "Booking Completed: {$roomName}",
            'icon' => 'heroicon-o-check-badge',
            'iconColor' => 'success',
            'body' => "Your {$duration}-hour session in {$roomName} has been completed. Thank you for using our practice spaces!",
            'actions' => [
                [
                    'label' => 'Provide Feedback',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id . '/feedback'),
                ],
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
        $startTime = $this->booking->start_time->format('l, F j, Y');
        $checkInTime = $this->booking->check_in_time->format('g:i A');
        $checkOutTime = $this->booking->check_out_time->format('g:i A');
        $duration = $this->booking->getDurationInHours();
        
        $message = "Your {$duration}-hour session in {$roomName} on {$startTime} has been completed. You checked in at {$checkInTime} and checked out at {$checkOutTime}.";
        
        if ($this->booking->total_price) {
            $message .= " Total cost: $" . number_format($this->booking->total_price, 2) . ".";
        }
        
        $message .= " Thank you for using our practice spaces!";
        
        return [
            'title' => "Booking Completed: {$roomName}",
            'icon' => 'heroicon-o-check-badge',
            'iconColor' => 'success',
            'body' => $message,
            'actions' => [
                [
                    'label' => 'Provide Feedback',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id . '/feedback'),
                ],
                [
                    'label' => 'Book Again',
                    'url' => url('/admin/practice-space/bookings/create'),
                ],
            ],
        ];
    }
} 