<?php

namespace CorvMC\PracticeSpace\Console\Commands;

use Illuminate\Console\Command;
use CorvMC\PracticeSpace\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class RecalculateBookingPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice-space:recalculate-prices {user_id? : The ID of the user to recalculate prices for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate prices for future bookings based on current membership tiers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            // Recalculate for a specific user
            $this->recalculateForUser($userId);
        } else {
            // Recalculate for all users
            $this->recalculateForAllUsers();
        }
        
        $this->info('Booking prices recalculated successfully.');
        
        return Command::SUCCESS;
    }
    
    /**
     * Recalculate prices for a specific user
     */
    protected function recalculateForUser(int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }
        
        $this->info("Recalculating prices for user: {$user->name}");
        
        $bookings = Booking::where('user_id', $userId)
            ->where('start_time', '>=', Carbon::now())
            ->get();
            
        $this->recalculateBookings($bookings);
    }
    
    /**
     * Recalculate prices for all users
     */
    protected function recalculateForAllUsers(): void
    {
        $this->info("Recalculating prices for all users' future bookings");
        
        $bookings = Booking::where('start_time', '>=', Carbon::now())
            ->get();
            
        $this->recalculateBookings($bookings);
    }
    
    /**
     * Recalculate prices for a collection of bookings
     */
    protected function recalculateBookings($bookings): void
    {
        $count = $bookings->count();
        $this->info("Found {$count} future bookings to recalculate");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($bookings as $booking) {
            $booking->recalculatePrice();
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }
} 