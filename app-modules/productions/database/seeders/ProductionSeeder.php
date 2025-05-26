<?php

namespace CorvMC\Productions\Database\Seeders;

use Illuminate\Database\Seeder;
use CorvMC\Productions\Models\Venue;
use CorvMC\Productions\Models\Production;
use CorvMC\Productions\Models\ProductionTag;
use CorvMC\Productions\Models\Act;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $now = Carbon::now();

        // Create tags
        $tags = [
            // Genre tags
            ['name' => 'Jazz', 'type' => 'genre'],
            ['name' => 'Rock', 'type' => 'genre'],
            ['name' => 'Folk', 'type' => 'genre'],
            ['name' => 'Classical', 'type' => 'genre'],
            ['name' => 'Blues', 'type' => 'genre'],
            ['name' => 'Electronic', 'type' => 'genre'],
            
            // Audience tags
            ['name' => 'All Ages', 'type' => 'audience'],
            ['name' => '21+', 'type' => 'audience'],
            ['name' => 'Family Friendly', 'type' => 'audience'],
            
            // General tags
            ['name' => 'Featured', 'type' => 'general'],
            ['name' => 'Seasonal', 'type' => 'general'],
            ['name' => 'Workshop', 'type' => 'general'],
        ];

        foreach ($tags as $tag) {
            ProductionTag::firstOrCreate(
                ['name' => $tag['name']],
                ['type' => $tag['type']]
            );
        }

        // Create acts
        $acts = [
            [
                'name' => 'The Jazz Collective',
                'description' => $faker->paragraph(),
                'website' => $faker->url(),
                'social_links' => [
                    ['platform' => 'facebook', 'url' => $faker->url()],
                    ['platform' => 'instagram', 'url' => $faker->url()],
                ],
                'contact_info' => [
                    'name' => $faker->name(),
                    'email' => $faker->email(),
                    'phone' => $faker->phoneNumber(),
                ],
            ],
            [
                'name' => 'Acoustic Harmony',
                'description' => $faker->paragraph(),
                'website' => $faker->url(),
                'social_links' => [
                    ['platform' => 'instagram', 'url' => $faker->url()],
                    ['platform' => 'spotify', 'url' => $faker->url()],
                ],
                'contact_info' => [
                    'name' => $faker->name(),
                    'email' => $faker->email(),
                    'phone' => $faker->phoneNumber(),
                ],
            ],
            [
                'name' => 'Rock Revolution',
                'description' => $faker->paragraph(),
                'website' => $faker->url(),
                'social_links' => [
                    ['platform' => 'facebook', 'url' => $faker->url()],
                    ['platform' => 'youtube', 'url' => $faker->url()],
                ],
                'contact_info' => [
                    'name' => $faker->name(),
                    'email' => $faker->email(),
                    'phone' => $faker->phoneNumber(),
                ],
            ],
        ];

        foreach ($acts as $act) {
            Act::firstOrCreate(
                ['name' => $act['name']],
                $act
            );
        }

        // Create venues
        $mainStage = Venue::firstOrCreate(
            ['name' => 'Main Stage'],
            [
                'description' => $faker->paragraph(),
                'capacity' => 100,
                'address' => [
                    'street' => '6775 A Philomath Blvd',
                    'city' => 'Corvallis',
                    'state' => 'OR',
                    'postal_code' => '97333'
                ],
                'contact_info' => [
                    'name' => $faker->name(),
                    'role' => 'Venue Coordinator',
                    'email' => $faker->email(),
                    'phone' => $faker->phoneNumber(),
                ]
            ]
        );

        $intimateRoom = Venue::firstOrCreate(
            ['name' => 'Intimate Room'],
            [
                'description' => $faker->paragraph(),
                'capacity' => 40,
                'address' => [
                    'street' => '6775 A Philomath Blvd',
                    'city' => 'Corvallis',
                    'state' => 'OR',
                    'postal_code' => '97333'
                ],
                'contact_info' => [
                    'name' => $faker->name(),
                    'role' => 'Venue Coordinator',
                    'email' => $faker->email(),
                    'phone' => $faker->phoneNumber(),
                ]
            ]
        );

        // Generate a year's worth of events (2 per week)
        $venues = [$mainStage, $intimateRoom];
        $allActs = Act::all();
        $allTags = ProductionTag::all();
        
        // Start from 6 months ago
        $startDate = $now->copy()->subMonths(6);
        $endDate = $now->copy()->addMonths(6);
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            // Create two events per week
            for ($i = 0; $i < 2; $i++) {
                // Randomly choose between weekday (Tue-Thu) and weekend (Fri-Sun)
                if ($i === 0) {
                    // First event of the week (Tue-Thu)
                    $showDate = $currentDate->copy()->addDays(rand(2, 4))->setHour(19)->setMinute(0);
                } else {
                    // Second event of the week (Fri-Sun)
                    $showDate = $currentDate->copy()->addDays(rand(5, 7))->setHour(20)->setMinute(0);
                }
                
                $venue = $venues[array_rand($venues)];
                $act = $allActs->random();
                $duration = rand(2, 4); // 2-4 hours
                
                // Determine status based on date
                $status = 'published';
                if ($showDate < $now) {
                    $status = 'finished';
                    if ($showDate < $now->copy()->subMonths(3)) {
                        $status = 'archived';
                    }
                } elseif ($showDate->diffInHours($now) < 24) {
                    $status = 'active';
                }
                
                $production = Production::create([
                    'title' => $faker->sentence(3),
                'description' => $faker->paragraph(),
                    'venue_id' => $venue->id,
                    'start_date' => $showDate,
                    'end_date' => $showDate->copy()->addHours($duration),
                    'status' => $status,
                    'capacity' => $venue->capacity,
                'ticket_link' => $faker->url(),
                ]);
                
                // Attach random tags (2-4 tags)
                $randomTags = $allTags->random(rand(2, 4));
                $production->tags()->attach($randomTags);
                
                // Attach the act
                $production->acts()->attach($act->id, [
                    'order' => 1,
                    'set_length' => $duration * 60, // Convert hours to minutes
                    ]);
                }
            
            // Move to next week
            $currentDate->addWeek();
        }
    }
} 