<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\CancelledState;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Notifications\BookingCancelledDueToNoConfirmationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessExpiredConfirmations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice-space:process-expired-confirmations
                            {--dry-run : Run without making actual changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process bookings with expired confirmation deadlines';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info("Finding bookings with expired confirmation deadlines...");
        
        // Find bookings that:
        // 1. Are in the Scheduled state
        // 2. Have a confirmation request sent
        // 3. Have a confirmation deadline that has passed
        // 4. Have not been cancelled yet
        
        $bookings = Booking::query()
            ->where('state', ScheduledState::$name)
            ->whereNotNull('confirmation_requested_at')
            ->whereNotNull('confirmation_deadline')
            ->where('confirmation_deadline', '<', Carbon::now())
            ->get();
        
        $this->info("Found {$bookings->count()} bookings with expired confirmation deadlines.");
        
        if ($isDryRun) {
            $this->warn("DRY RUN: No changes will be made.");
            
            foreach ($bookings as $booking) {
                $user = User::find($booking->user_id);
                $this->info("Would cancel booking #{$booking->id} for {$user->email} due to expired confirmation deadline");
            }
            
            return;
        }
        
        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();
        
        foreach ($bookings as $booking) {
            $user = User::find($booking->user_id);
            
            try {
                // Transition to cancelled state
                $booking->state = CancelledState::$name;
                
                // Update cancellation reason and timestamp
                $booking->update([
                    'cancellation_reason' => 'Automatically cancelled due to no confirmation',
                    'cancelled_at' => now(),
                ]);
                
                // Send notification
                $user->notify(new BookingCancelledDueToNoConfirmationNotification($booking));
                
                // Log the notification in the activity log
                $booking->logNotificationSent(BookingCancelledDueToNoConfirmationNotification::class, [
                    'cancellation_reason' => 'Automatically cancelled due to no confirmation',
                    'confirmation_deadline' => $booking->confirmation_deadline,
                ]);
                
                Log::info("Cancelled booking #{$booking->id} for user {$user->id} due to expired confirmation deadline");
            } catch (\Exception $e) {
                $this->error("Failed to process expired confirmation for booking #{$booking->id}: {$e->getMessage()}");
                Log::error("Failed to process expired confirmation: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Expired confirmations processed successfully!");
    }
} 