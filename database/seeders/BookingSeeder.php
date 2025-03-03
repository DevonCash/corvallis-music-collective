<?php

namespace Database\Seeders;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\States\PaymentState\Paid;
use App\Modules\Payments\Models\States\PaymentState\Pending;
use App\Modules\Payments\Models\States\PaymentState\Failed;
use App\Modules\Payments\Models\States\PaymentState\Refunded;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\Room;
use App\Modules\PracticeSpace\Models\States\BookingState\Cancelled;
use App\Modules\PracticeSpace\Models\States\BookingState\Completed;
use App\Modules\PracticeSpace\Models\States\BookingState\Confirmed;
use App\Modules\PracticeSpace\Models\States\BookingState\Scheduled;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available rooms
        $rooms = Room::all();
        if ($rooms->isEmpty()) {
            $this->command->info('No rooms available. Creating rooms first...');
            $this->call(RoomSeeder::class);
            $rooms = Room::all();
        }

        // Define valid booking-payment state combinations that are logically consistent
        $validStatesCombinations = [
            // Valid combinations with weights to control distribution
            ['booking' => Confirmed::class, 'payment' => Paid::class, 'weight' => 40],           // Confirmed bookings must have Paid payments
            ['booking' => Completed::class, 'payment' => Paid::class, 'weight' => 20],           // Completed bookings must have Paid payments
            ['booking' => Scheduled::class, 'payment' => Pending::class, 'weight' => 20],        // Scheduled bookings typically have Pending payments
            ['booking' => Cancelled::class, 'payment' => Failed::class, 'weight' => 10],         // Cancelled bookings can have Failed payments
            ['booking' => Cancelled::class, 'payment' => Refunded::class, 'weight' => 10],       // Cancelled bookings can have Refunded payments (previously paid)
        ];

        // Create 10 random bookings with valid state combinations
        for ($i = 0; $i < 10; $i++) {
            // Check if we already have enough bookings
            if (Booking::count() >= 10 + $i) {
                continue;
            }

            // Create a user for this booking
            $user = User::factory()->create();
            
            // Get a random room
            $room = $rooms->random();
            
            // Generate random start and end times in the future
            $startTime = Carbon::now()
                ->addDays(rand(1, 30))
                ->setHour(rand(9, 20))
                ->setMinute(0)
                ->setSecond(0);
            $endTime = (clone $startTime)->addHours(rand(1, 3));
            
            // Select a valid booking-payment state combination based on weights
            $selectedCombination = $this->getWeightedRandomCombination($validStatesCombinations);
            $bookingState = $selectedCombination['booking'];
            $paymentState = $selectedCombination['payment'];
            
            // Adjust start time for completed bookings to be in the past
            if ($bookingState === Completed::class) {
                $startTime = Carbon::now()->subDays(rand(1, 7));
                $endTime = (clone $startTime)->addHours(rand(1, 3));
            }
            
            // Create the booking with appropriate state
            $booking = Booking::factory()
                ->forUser($user)
                ->forRoom($room)
                ->withState($bookingState)
                ->forTimeSlot($startTime, $endTime)
                ->create();
            
            // Create payment with appropriate state
            $payment = Payment::factory()
                ->forUser($user)
                ->forProduct($room->product)
                ->forPayable($booking)
                ->withState($paymentState)
                ->create([
                    'amount' => $booking->calculateAmount(),
                ]);
            
            // Create activity logs for state history (some bookings will have random state history)
            // Only create logical state transitions
            if ($bookingState === Completed::class || $bookingState === Confirmed::class) {
                $this->createLogicalStateHistory($booking, $paymentState);
            } elseif ($bookingState === Cancelled::class) {
                $this->createCancellationHistory($booking, $paymentState);
            }
        }
        
        $this->command->info('Created ' . Booking::count() . ' bookings with compatible payment states');
    }
    
    /**
     * Get a weighted random state combination
     */
    private function getWeightedRandomCombination(array $combinations): array
    {
        $totalWeight = array_sum(array_column($combinations, 'weight'));
        $randomValue = mt_rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($combinations as $combination) {
            $currentWeight += $combination['weight'];
            if ($randomValue <= $currentWeight) {
                return $combination;
            }
        }
        
        // Fallback (should never reach here)
        return $combinations[0];
    }
    
    /**
     * Create logical state history for completed or confirmed bookings
     */
    private function createLogicalStateHistory(Booking $booking, string $paymentState): void
    {
        $states = [Scheduled::class, Confirmed::class];
        
        // If the booking is completed, add Completed to the history
        if ($booking->state === Completed::class) {
            $states[] = Completed::class;
        }
        
        // Create logs for each state transition
        $date = $booking->created_at;
        foreach ($states as $state) {
            // Add some time between state changes
            $date = $date->addMinutes(rand(30, 120));
            
            activity()
                ->performedOn($booking)
                ->withProperties(['attributes' => ['state' => $state], 'old' => ['state' => 'previous state']])
                ->createdAt($date)
                ->log('updated');
            
            // Update the booking's actual state if this is the last state
            if ($state === end($states)) {
                $booking->update(['state' => $state]);
            }
        }
    }
    
    /**
     * Create cancellation history
     */
    private function createCancellationHistory(Booking $booking, string $paymentState): void
    {
        // For cancelled bookings with refunded payments, they must have been confirmed first
        $states = [Scheduled::class];
        
        // If refunded, it must have been confirmed and then cancelled
        if ($paymentState === Refunded::class) {
            $states[] = Confirmed::class;
        }
        
        // Add the final cancelled state
        $states[] = Cancelled::class;
        
        // Create logs for each state transition
        $date = $booking->created_at;
        foreach ($states as $state) {
            // Add some time between state changes
            $date = $date->addMinutes(rand(30, 120));
            
            activity()
                ->performedOn($booking)
                ->withProperties(['attributes' => ['state' => $state], 'old' => ['state' => 'previous state']])
                ->createdAt($date)
                ->log('updated');
            
            // Update the booking's actual state if this is the last state
            if ($state === end($states)) {
                $booking->update(['state' => $state]);
            }
        }
    }
}
