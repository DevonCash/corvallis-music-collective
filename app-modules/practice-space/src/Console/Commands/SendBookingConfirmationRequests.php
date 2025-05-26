<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\ScheduledState;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationRequestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Facades\LogBatch;

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
            ->where('start_time', '<=', Carbon::now()->addDays(3))
            ->with('activities')
            ->get();

        $this->info("Found {$bookings->count()} bookings that need confirmation requests.");



        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        LogBatch::startBatch();
        foreach ($bookings as $booking) {
            $bar->advance();

            // Check if the confirmation request has already been sent
            $confirmationRequestSent = $booking->activities()
                ->where('event', 'confirmation.sent')
                ->exists();

            if ($confirmationRequestSent) {
                $this->info("Confirmation request already sent for booking #{$booking->id}. Skipping.");
                continue;
            }

            if ($isDryRun) {
                $this->info("DRY RUN: Would send confirmation request to {$booking->user->email} for booking #{$booking->id}");
                continue;
            }

            $this->notifyUserForBooking($booking);
        }
        LogBatch::endBatch();

        $bar->finish();
        $this->newLine();

        if ($isDryRun) {
            $this->info("DRY RUN: No actual notifications were sent.");
        } else {
            $this->info("All notifications sent successfully.");
        }
    }

    private function notifyUserForBooking(Booking $booking)
    {
        try {
            $user = $booking->user;

            $user->notify(new BookingConfirmationRequestNotification($booking));

            activity('notification')
                ->performedOn($booking)
                ->event('confirmation.sent')
                ->log("Sent booking confirmation request to user {$user->id} for booking {$booking->id}");

            // Send the notification
            $this->info("Sent confirmation request to {$user->email} for booking #{$booking->id}");
        } catch (\Exception $e) {
            $this->error("Failed to send confirmation request for booking #{$booking->id}: {$e->getMessage()}");
            Log::error("Failed to send booking confirmation request: {$e->getMessage()}", [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'exception' => $e,
            ]);
        }
    }
}
