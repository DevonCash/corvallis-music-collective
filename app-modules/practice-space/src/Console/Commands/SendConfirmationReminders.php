<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendConfirmationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice-space:send-confirmation-reminders 
                            {--hours=6 : Hours before deadline to send reminder}
                            {--dry-run : Run without sending actual notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to users who have not yet confirmed their bookings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursBeforeDeadline = $this->option('hours');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Finding bookings that need confirmation reminders...");
        
        // Find bookings that:
        // 1. Are in the Scheduled state
        // 2. Have a confirmation request sent
        // 3. Have a confirmation deadline approaching in approximately $hoursBeforeDeadline hours
        // 4. Haven't had a confirmation reminder sent for this time window yet
        
        $targetDeadlineTime = Carbon::now()->addHours($hoursBeforeDeadline);
        $deadlineTimeMin = $targetDeadlineTime->copy()->subMinutes(30);
        $deadlineTimeMax = $targetDeadlineTime->copy()->addMinutes(30);
        
        $bookings = Booking::query()
            ->where('state', ScheduledState::$name)
            ->whereNotNull('confirmation_requested_at')
            ->whereNotNull('confirmation_deadline')
            ->whereBetween('confirmation_deadline', [$deadlineTimeMin, $deadlineTimeMax])
            ->get()
            ->filter(function ($booking) use ($hoursBeforeDeadline) {
                // Check if this reminder has already been sent using the activity log
                return !$booking->hasNotificationBeenSent(BookingConfirmationReminderNotification::class, [
                    'hours_before_deadline' => $hoursBeforeDeadline
                ]);
            });
        
        $this->info("Found {$bookings->count()} bookings that need confirmation reminders.");
        
        if ($isDryRun) {
            $this->warn("DRY RUN: No notifications will be sent.");
            
            foreach ($bookings as $booking) {
                $user = User::find($booking->user_id);
                $this->info("Would send confirmation reminder to {$user->email} for booking #{$booking->id} (deadline in {$hoursBeforeDeadline} hours)");
            }
            
            return;
        }
        
        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();
        
        foreach ($bookings as $booking) {
            $user = User::find($booking->user_id);
            
            try {
                // Send the notification
                $user->notify(new BookingConfirmationReminderNotification($booking, $hoursBeforeDeadline));
                
                // Log the notification in the activity log
                $booking->logNotificationSent(BookingConfirmationReminderNotification::class, [
                    'hours_before_deadline' => $hoursBeforeDeadline,
                ]);
                
                Log::info("Sent confirmation reminder to user {$user->id} for booking {$booking->id} (deadline in {$hoursBeforeDeadline} hours)");
            } catch (\Exception $e) {
                $this->error("Failed to send confirmation reminder for booking #{$booking->id}: {$e->getMessage()}");
                Log::error("Failed to send confirmation reminder: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Confirmation reminders sent successfully!");
    }
} 