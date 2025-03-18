<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\ConfirmedState;
use CorvMC\PracticeSpace\Notifications\BookingReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBookingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice-space:send-booking-reminders 
                            {--hours=24 : Hours before booking to send reminder}
                            {--dry-run : Run without sending actual notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications to users with upcoming confirmed bookings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursBeforeBooking = $this->option('hours');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Finding confirmed bookings that start in approximately {$hoursBeforeBooking} hours...");
        
        // Find bookings that:
        // 1. Are in the Confirmed state
        // 2. Start in approximately $hoursBeforeBooking hours
        // 3. Haven't had a reminder sent for this time window yet
        
        // We need to calculate the target time in UTC since the start_time is stored in UTC
        $targetStartTime = now()->addHours((int)$hoursBeforeBooking)->setTimezone('UTC');
        $startTimeMin = $targetStartTime->copy()->subMinutes(30);
        $startTimeMax = $targetStartTime->copy()->addMinutes(30);
        
        $this->info("Looking for bookings between {$startTimeMin->toDateTimeString()} and {$startTimeMax->toDateTimeString()} UTC");
        
        $bookings = Booking::query()
            ->where('state', ConfirmedState::class)
            ->whereBetween('start_time', [$startTimeMin, $startTimeMax])
            ->get()
            ->filter(function ($booking) use ($hoursBeforeBooking) {
                // Check if this reminder has already been sent using the activity log
                return !$booking->hasNotificationBeenSent(BookingReminderNotification::class, [
                    'hours_before' => $hoursBeforeBooking
                ]);
            });
        
        $this->info("Found {$bookings->count()} bookings that need reminders.");
        
        if ($isDryRun) {
            $this->warn("DRY RUN: No notifications will be sent.");
            
            foreach ($bookings as $booking) {
                $user = User::find($booking->user_id);
                $this->info("Would send {$hoursBeforeBooking}-hour reminder to {$user->email} for booking #{$booking->id}");
                $this->info("  Booking start: {$booking->start_time} UTC / {$booking->start_time->copy()->setTimezone($booking->room->timezone)} Room time");
            }
            
            return;
        }
        
        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();
        
        foreach ($bookings as $booking) {
            $user = User::find($booking->user_id);
            
            try {
                // Send the notification
                $user->notify(new BookingReminderNotification($booking, $hoursBeforeBooking));
                
                // Log the notification in the activity log
                $booking->logNotificationSent(BookingReminderNotification::class, [
                    'hours_before' => $hoursBeforeBooking,
                ]);
                
                Log::info("Sent {$hoursBeforeBooking}-hour booking reminder to user {$user->id} for booking {$booking->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for booking #{$booking->id}: {$e->getMessage()}");
                Log::error("Failed to send booking reminder: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Booking reminders sent successfully!");
    }
} 