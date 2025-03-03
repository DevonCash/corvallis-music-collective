<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\Payments\Models\States\PaymentState\Paid;
use App\Modules\Payments\Models\States\PaymentState\Pending;
use App\Modules\Payments\Models\States\PaymentState\Failed;
use App\Modules\Payments\Models\States\PaymentState\Refunded;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\Room;
use App\Modules\PracticeSpace\Models\States\BookingState\Confirmed;
use App\Modules\PracticeSpace\Models\States\BookingState\Completed;
use App\Modules\PracticeSpace\Models\States\BookingState\Cancelled;
use App\Modules\PracticeSpace\Models\States\BookingState\Scheduled;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create 5 additional users
        $users = User::factory(5)->create();
        $allUsers = $users->push($testUser);

        // Create 3 products with different price ranges
        $products = [
            Product::factory()->create([
                'name' => 'Economy Room',
                'description' => 'Basic practice room with essential equipment',
                'prices' => [
                    'hourly' => [
                        'amount' => 1500, // $15 in cents
                        'currency' => 'usd'
                    ]
                ],
                'stripe_product_id' => 'prod_economyRoom123',
            ]),
            Product::factory()->create([
                'name' => 'Standard Room',
                'description' => 'Well-equipped practice room for bands and groups',
                'prices' => [
                    'hourly' => [
                        'amount' => 2500, // $25 in cents
                        'currency' => 'usd'
                    ]
                ],
                'stripe_product_id' => 'prod_standardRoom456',
            ]),
            Product::factory()->create([
                'name' => 'Premium Room',
                'description' => 'Luxury practice room with premium equipment and amenities',
                'prices' => [
                    'hourly' => [
                        'amount' => 4000, // $40 in cents
                        'currency' => 'usd'
                    ]
                ],
                'stripe_product_id' => 'prod_premiumRoom789',
            ]),
        ];

        // Create 6 rooms based on the products
        $rooms = [];
        foreach ($products as $index => $product) {
            $roomsForProduct = Room::factory(2)
                ->withProduct($product)
                ->create([
                    'name' => ($index === 0 ? 'Economy' : ($index === 1 ? 'Standard' : 'Premium')) . ' Room ' . fake()->randomLetter() . fake()->numberBetween(1, 5),
                ]);
            $rooms = array_merge($rooms, $roomsForProduct->toArray());
        }

        // Create bookings with different states
        $startDate = Carbon::now()->addDays(1)->setHour(9)->setMinute(0)->setSecond(0);
        
        // Create past bookings (completed) - all completed bookings must have Paid payments
        for ($i = 0; $i < 5; $i++) {
            $user = $allUsers[fake()->numberBetween(0, count($allUsers) - 1)];
            $room = Room::find($rooms[array_rand($rooms)]['id']);
            $startTime = (clone $startDate)->subDays(random_int(1, 14))->addHours(random_int(0, 8));
            $endTime = (clone $startTime)->addHours(random_int(1, 3));
            
            // Create booking first
            $booking = Booking::factory()
                ->forUser($user)
                ->forRoom($room)
                ->withState(Completed::class)
                ->forTimeSlot($startTime, $endTime)
                ->create();
            
            // For completed bookings, payments must be in Paid state
            Payment::factory()
                ->forUser($user)
                ->forProduct($room->product)
                ->forPayable($booking)
                ->withState(Paid::class)
                ->create([
                    'amount' => $room->product->prices['hourly']['amount'] * 
                        $startTime->diffInHours($endTime),
                ]);
        }

        // Create a mix of confirmed, scheduled, and cancelled bookings with appropriate payment states
        for ($i = 0; $i < 15; $i++) {
            $user = $allUsers[fake()->numberBetween(0, count($allUsers) - 1)];
            $room = Room::find($rooms[array_rand($rooms)]['id']);
            $startTime = (clone $startDate)->addDays(random_int(1, 14))->addHours(random_int(0, 8));
            $endTime = (clone $startTime)->addHours(random_int(1, 3));
            
            // Decide on booking and payment state combinations that make sense
            $bookingPaymentCombination = fake()->randomElement([
                // Confirmed bookings with Paid payments
                ['booking' => Confirmed::class, 'payment' => Paid::class],
                ['booking' => Confirmed::class, 'payment' => Paid::class],
                ['booking' => Confirmed::class, 'payment' => Paid::class],
                
                // Scheduled bookings with Pending payments
                ['booking' => Scheduled::class, 'payment' => Pending::class],
                ['booking' => Scheduled::class, 'payment' => Pending::class],
                
                // Cancelled bookings with Failed payments
                ['booking' => Cancelled::class, 'payment' => Failed::class],
                
                // Cancelled bookings with Refunded payments (must have been paid first)
                ['booking' => Cancelled::class, 'payment' => Refunded::class],
            ]);
            
            $bookingState = $bookingPaymentCombination['booking'];
            $paymentState = $bookingPaymentCombination['payment'];
            
            // Create booking with appropriate state
            $booking = Booking::factory()
                ->forUser($user)
                ->forRoom($room)
                ->withState($bookingState)
                ->forTimeSlot($startTime, $endTime)
                ->create();
            
            // Create payment with appropriate state
            Payment::factory()
                ->forUser($user)
                ->forProduct($room->product)
                ->forPayable($booking)
                ->withState($paymentState)
                ->create([
                    'amount' => $room->product->prices['hourly']['amount'] * 
                        $startTime->diffInHours($endTime),
                ]);
        }

        // Run our existing seeders
        $this->call([
            RoomSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
