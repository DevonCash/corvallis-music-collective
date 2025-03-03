<?php

namespace Database\Seeders;

use App\Modules\Payments\Models\Product;
use App\Modules\PracticeSpace\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create products for rooms using ProductFactory
        $basicProduct = Product::firstOrCreate(
            ['name' => 'Basic Practice Room'],
            Product::factory()->create([
                'name' => 'Basic Practice Room',
                'description' => 'Basic room for practicing',
                'prices' => [
                    'hourly' => [
                        'amount' => 2000, // $20.00
                        'currency' => 'usd',
                    ],
                    'half_day' => [
                        'amount' => 8000, // $80.00
                        'currency' => 'usd',
                    ],
                    'full_day' => [
                        'amount' => 15000, // $150.00
                        'currency' => 'usd',
                    ],
                ],
                'stripe_product_id' => 'prod_basicRoom' . fake()->regexify('[A-Za-z0-9]{8}'),
            ])->toArray()
        );

        $premiumProduct = Product::firstOrCreate(
            ['name' => 'Premium Practice Room'],
            Product::factory()->create([
                'name' => 'Premium Practice Room',
                'description' => 'Premium room with better acoustics',
                'prices' => [
                    'hourly' => [
                        'amount' => 3500, // $35.00
                        'currency' => 'usd',
                    ],
                    'half_day' => [
                        'amount' => 14000, // $140.00
                        'currency' => 'usd',
                    ],
                    'full_day' => [
                        'amount' => 25000, // $250.00
                        'currency' => 'usd',
                    ],
                ],
                'stripe_product_id' => 'prod_premiumRoom' . fake()->regexify('[A-Za-z0-9]{8}'),
            ])->toArray()
        );

        $studioProduct = Product::firstOrCreate(
            ['name' => 'Recording Studio'],
            Product::factory()->create([
                'name' => 'Recording Studio',
                'description' => 'Professional recording studio',
                'prices' => [
                    'hourly' => [
                        'amount' => 5000, // $50.00
                        'currency' => 'usd',
                    ],
                    'half_day' => [
                        'amount' => 20000, // $200.00
                        'currency' => 'usd',
                    ],
                    'full_day' => [
                        'amount' => 35000, // $350.00
                        'currency' => 'usd',
                    ],
                ],
                'stripe_product_id' => 'prod_studioRoom' . fake()->regexify('[A-Za-z0-9]{8}'),
            ])->toArray()
        );

        // Create rooms using RoomFactory
        Room::firstOrCreate(
            ['name' => 'Practice Room A'],
            Room::factory()
                ->withProduct($basicProduct)
                ->create([
                    'name' => 'Practice Room A',
                    'description' => 'Small practice room suitable for individual practice',
                    'capacity' => 2,
                    'amenities' => [
                        'piano' => true,
                        'music_stand' => true,
                        'chair' => true
                    ],
                    'hours' => $this->generateStandardHours('9:00', '22:00'),
                ])->toArray()
        );

        Room::firstOrCreate(
            ['name' => 'Practice Room B'],
            Room::factory()
                ->withProduct($basicProduct)
                ->create([
                    'name' => 'Practice Room B',
                    'description' => 'Small practice room with natural lighting',
                    'capacity' => 2,
                    'amenities' => [
                        'music_stand' => true,
                        'chair' => true,
                        'mirror' => true
                    ],
                    'hours' => $this->generateStandardHours('9:00', '22:00'),
                ])->toArray()
        );

        Room::firstOrCreate(
            ['name' => 'Ensemble Room'],
            Room::factory()
                ->withProduct($premiumProduct)
                ->create([
                    'name' => 'Ensemble Room',
                    'description' => 'Medium-sized room good for small ensembles',
                    'capacity' => 6,
                    'amenities' => [
                        'piano' => true,
                        'music_stand' => true,
                        'chair' => true,
                        'amplifier' => true
                    ],
                    'hours' => $this->generateStandardHours('9:00', '22:00'),
                ])->toArray()
        );

        Room::firstOrCreate(
            ['name' => 'Studio'],
            Room::factory()
                ->withProduct($studioProduct)
                ->create([
                    'name' => 'Studio',
                    'description' => 'Professional recording studio with sound isolation',
                    'capacity' => 8,
                    'amenities' => [
                        'piano' => true, 
                        'mixing_console' => true, 
                        'microphone' => true, 
                        'headphones' => true
                    ],
                    'hours' => $this->generateStandardHours('10:00', '20:00'),
                ])->toArray()
        );
    }

    /**
     * Generate standard hours for all days of the week
     */
    private function generateStandardHours(string $open, string $close): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];
        
        foreach ($days as $day) {
            $hours[$day] = [
                'open' => $open,
                'close' => $close,
                'is_open' => true,
            ];
        }
        
        return $hours;
    }
}
