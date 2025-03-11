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
                            {--dry-run : Run without sending actual notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send confirmation requests to users with bookings that have entered the confirmation window';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info("Finding bookings that have entered the confirmation window...");
        
        // Find bookings that:
        // 1. Are in the Scheduled state
        // 2. Have a confirmation_requested_at date that is today or in the past
        // 3. Have not been confirmed yet
        // 4. Have not been cancelled
        // 5. Have not had a confirmation request sent yet
        
        $bookings = Booking::query()
            ->where('state', ScheduledState::$name)
            ->whereNotNull('confirmation_requested_at')
            ->where('confirmation_requested_at', '<=', now())
            ->whereNull('confirmed_at')
            ->whereNull('cancelled_at')
            ->get()
            ->filter(function ($booking) {
                // Check if this notification has already been sent using the activity log
                return !$booking->hasNotificationBeenSent(BookingConfirmationRequestNotification::class);
            });
        
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
                $user->notify(new BookingConfirmationRequestNotification($booking));
                
                // Log the notification in the activity log
                $booking->logNotificationSent(BookingConfirmationRequestNotification::class, [
                    'confirmation_deadline' => $booking->confirmation_deadline,
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
        $this->info("Booking confirmation requests sent successfully!");
    }
} 