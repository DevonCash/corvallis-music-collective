<?php

namespace CorvMC\PracticeSpace\Notifications;

use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class BookingConfirmationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking $booking;
    protected int $hoursUntilDeadline;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, int $hoursUntilDeadline)
    {
        $this->booking = $booking;
        $this->hoursUntilDeadline = $hoursUntilDeadline;
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
        $deadlineTime = $this->booking->confirmation_deadline->format('l, F j \a\t g:i A');
        
        // Generate a signed URL for confirmation
        $confirmUrl = URL::temporarySignedRoute(
            'practice-space.bookings.confirm',
            $this->booking->confirmation_deadline,
            ['booking' => $this->booking->id]
        );
        
        // Generate a signed URL for cancellation
        $cancelUrl = URL::temporarySignedRoute(
            'practice-space.bookings.cancel',
            $this->booking->confirmation_deadline,
            ['booking' => $this->booking->id]
        );
        
        $reminderText = $this->hoursUntilDeadline === 1 
            ? "1 hour left" 
            : "{$this->hoursUntilDeadline} hours left";
        
        return (new MailMessage)
            ->subject("URGENT: {$reminderText} to confirm your booking - {$roomName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("**This is an urgent reminder that you need to confirm your booking for {$roomName}.**")
            ->line("If you don't confirm by {$deadlineTime}, your booking will be automatically cancelled and the space will be made available to others.")
            ->line("**Booking Details:**")
            ->line("- **Date and Time:** {$startTime} to {$endTime}")
            ->line("- **Room:** {$roomName}")
            ->line("- **Booking ID:** {$this->booking->id}")
            ->action('Confirm Booking Now', $confirmUrl)
            ->line("If you can no longer attend this session, please cancel your booking:")
            ->action('Cancel Booking', $cancelUrl)
            ->line("Thank you for using our practice spaces!");
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
            'confirmation_deadline' => $this->booking->confirmation_deadline,
            'hours_until_deadline' => $this->hoursUntilDeadline,
        ];
    }
    
    /**
     * Get the Filament representation of the notification.
     */
    public function toFilament(object $notifiable): array
    {
        $roomName = $this->booking->room->name;
        $deadlineTime = $this->booking->confirmation_deadline->format('l, F j \a\t g:i A');
        
        $reminderText = $this->hoursUntilDeadline === 1 
            ? "1 hour left" 
            : "{$this->hoursUntilDeadline} hours left";
        
        return [
            'title' => "URGENT: Confirm Your Booking",
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'danger',
            'body' => "Only {$reminderText} to confirm your booking for {$roomName}. Deadline: {$deadlineTime}.",
            'actions' => [
                [
                    'label' => 'Confirm Now',
                    'url' => url('/admin/practice-space/bookings/' . $this->booking->id . '/confirm'),
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
        $deadlineTime = $this->booking->confirmation_deadline->format('l, F j \a\t g:i A');
        
        $reminderText = $this->hoursUntilDeadline === 1 
            ? "1 hour left" 
            : "{$this->hoursUntilDeadline} hours left";
        
        return [
            'title' => "URGENT: Confirm Your Booking",
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'danger',
            'body' => "You have only {$reminderText} to confirm your booking for {$roomName} on {$startTime}. If you don't confirm by {$deadlineTime}, your booking will be automatically cancelled.",
            'actions' => [
                [
                    'label' => 'Confirm Now',
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