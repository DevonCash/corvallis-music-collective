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
        
        $hasEquipment = $this->booking->room->equipment->count() > 0;
        $equipmentList = $hasEquipment 
            ? $this->booking->room->equipment->pluck('name')->join(', ')
            : '';
        
        return (new MailMessage)
            ->subject("Check-In Confirmed: {$roomName}")
            ->markdown('practice-space::emails.bookings.check-in', [
                'userName' => $notifiable->name,
                'roomName' => $roomName,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'checkInTime' => $checkInTime,
                'checkOutTime' => $checkOutTime,
                'bookingId' => $this->booking->id,
                'hasEquipment' => $hasEquipment,
                'equipmentList' => $equipmentList,
                'viewUrl' => url('/practice-space/bookings/' . $this->booking->id),
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