<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\CancelledState;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
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
    protected $description = 'Cancel bookings that have not been confirmed by the deadline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("Finding scheduled bookings with expired confirmation deadlines...");

        // Find bookings that:
        // 1. Are in the Scheduled state
        // 2. Have a confirmation deadline that has passed
        // 3. Have not been cancelled yet

        $bookings = Booking::query()
            ->where('state', ScheduledState::$name)
            ->whereNotNull('confirmation_deadline')
            ->where('confirmation_deadline', '<', now())
            ->whereNull('cancelled_at')
            ->get();

        $this->info("Found {$bookings->count()} bookings with expired confirmation deadlines.");

        if ($isDryRun) {
            $this->warn("DRY RUN: No bookings will be cancelled.");

            foreach ($bookings as $booking) {
                $this->info("Would cancel booking #{$booking->id} for user #{$booking->user_id}");
            }

            return;
        }

        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        foreach ($bookings as $booking) {
            try {
                // Cancel the booking
                $booking->state->transitionTo(CancelledState::class, [
                    'reason' => 'Automatically cancelled due to missed confirmation deadline',
                ]);

                Log::info("Automatically cancelled booking #{$booking->id} for user #{$booking->user_id} due to missed confirmation deadline");
            } catch (\Exception $e) {
                $this->error("Failed to cancel booking #{$booking->id}: {$e->getMessage()}");
                Log::error("Failed to cancel booking: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'exception' => $e,
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Expired booking confirmations processed successfully!");
    }
}
