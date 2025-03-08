<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationRequestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmationRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice-space:send-confirmation-requests 
                            {--hours=48 : Hours before booking to send confirmation request}
                            {--window=24 : Hours users have to confirm their booking}
                            {--dry-run : Run without sending actual notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send confirmation requests to users with upcoming bookings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursBeforeBooking = $this->option('hours');
        $confirmationWindow = $this->option('window');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Finding bookings that need confirmation requests...");
        
        // Find bookings that:
        // 1. Are in the Scheduled state
        // 2. Start in approximately $hoursBeforeBooking hours
        // 3. Haven't had a confirmation request sent yet
        
        $targetStartTime = Carbon::now()->addHours($hoursBeforeBooking);
        $startTimeMin = $targetStartTime->copy()->subHours(1);
        $startTimeMax = $targetStartTime->copy()->addHours(1);
        
        $bookings = Booking::query()
            ->where('state', ScheduledState::$name)
            ->whereBetween('start_time', [$startTimeMin, $startTimeMax])
            ->whereNull('confirmation_requested_at') // Assuming this column exists or would be added
            ->get();
        
        $this->info("Found {$bookings->count()} bookings that need confirmation requests.");
        
        if ($isDryRun) {
            $this->warn("DRY RUN: No notifications will be sent.");
            
            foreach ($bookings as $booking) {
                $user = User::find($booking->user_id);
                $this->info("Would send confirmation request to {$user->email} for booking #{$booking->id}");
            }
            
            return;
        }
        
        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();
        
        foreach ($bookings as $booking) {
            $user = User::find($booking->user_id);
            
            try {
                // Send the notification
                $user->notify(new BookingConfirmationRequestNotification($booking, $confirmationWindow));
                
                // Update the booking to record that a confirmation request was sent
                $booking->update([
                    'confirmation_requested_at' => now(),
                    'confirmation_deadline' => now()->addHours($confirmationWindow),
                ]);
                
                // Log the notification in the activity log
                $booking->logNotificationSent(BookingConfirmationRequestNotification::class, [
                    'confirmation_window_hours' => $confirmationWindow,
                    'confirmation_deadline' => now()->addHours($confirmationWindow),
                ]);
                
                Log::info("Sent booking confirmation request to user {$user->id} for booking {$booking->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send confirmation request for booking #{$booking->id}: {$e->getMessage()}");
                Log::error("Failed to send booking confirmation request: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Confirmation requests sent successfully!");
    }
} 