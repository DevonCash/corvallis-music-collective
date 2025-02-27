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
        // Create products for rooms if they don't exist
        $products = [
            [
                'name' => 'Basic Practice Room',
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
                'description' => 'Basic room for practicing',
            ],
            [
                'name' => 'Premium Practice Room',
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
                'description' => 'Premium room with better acoustics',
            ],
            [
                'name' => 'Recording Studio',
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
                'description' => 'Professional recording studio',
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }

        // Create rooms
        $rooms = [
            [
                'name' => 'Practice Room A',
                'product_id' => Product::where('name', 'Basic Practice Room')->first()->id,
                'description' => 'Small practice room suitable for individual practice',
                'capacity' => 2,
                'amenities' => ['Piano', 'Music Stand', 'Chair'],
                'hours' => ['open' => '9:00', 'close' => '22:00'],
            ],
            [
                'name' => 'Practice Room B',
                'product_id' => Product::where('name', 'Basic Practice Room')->first()->id,
                'description' => 'Small practice room with natural lighting',
                'capacity' => 2,
                'amenities' => ['Music Stand', 'Chair', 'Mirror'],
                'hours' => ['open' => '9:00', 'close' => '22:00'],
            ],
            [
                'name' => 'Ensemble Room',
                'product_id' => Product::where('name', 'Premium Practice Room')->first()->id,
                'description' => 'Medium-sized room good for small ensembles',
                'capacity' => 6,
                'amenities' => ['Piano', 'Music Stands', 'Chairs', 'Amplifier'],
                'hours' => ['open' => '9:00', 'close' => '22:00'],
            ],
            [
                'name' => 'Studio',
                'product_id' => Product::where('name', 'Recording Studio')->first()->id,
                'description' => 'Professional recording studio with sound isolation',
                'capacity' => 8,
                'amenities' => ['Piano', 'Mixing Console', 'Microphones', 'Headphones'],
                'hours' => ['open' => '10:00', 'close' => '20:00'],
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::firstOrCreate(
                ['name' => $roomData['name']],
                $roomData
            );
        }
    }
}
