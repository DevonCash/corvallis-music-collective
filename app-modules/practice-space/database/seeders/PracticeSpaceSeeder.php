<?php

namespace CorvMC\PracticeSpace\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\BookingPolicy;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\BookingPolicyOverride;
use CorvMC\PracticeSpace\Models\RoomEquipment;
use CorvMC\PracticeSpace\Models\MaintenanceSchedule;
use CorvMC\PracticeSpace\Models\RoomFavorite;
use CorvMC\PracticeSpace\Models\WaitlistEntry;
use Carbon\Carbon;

class PracticeSpaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Practice Space module...');

        // Create users if none exist
        if (User::count() === 0) {
            $this->command->info('Creating users...');
            User::factory()->count(10)->create();
        }

        // Create room categories
        $this->command->info('Creating room categories...');
        $categories = [
            [
                'name' => 'Standard Practice Room',
                'description' => 'Basic practice rooms suitable for individual practice or small ensembles.',
                'is_active' => true,
            ],
            [
                'name' => 'Recording Studio',
                'description' => 'Professional recording studios with sound isolation and equipment.',
                'is_active' => true,
            ],
            [
                'name' => 'Rehearsal Hall',
                'description' => 'Large spaces for full band rehearsals and performances.',
                'is_active' => true,
            ],
            [
                'name' => 'Teaching Studio',
                'description' => 'Rooms designed for music lessons and teaching.',
                'is_active' => true,
            ],
            [
                'name' => 'Percussion Room',
                'description' => 'Specialized rooms for percussion practice with additional sound isolation.',
                'is_active' => true,
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $category) {
            $categoryModels[] = RoomCategory::create($category);
        }

        // Create booking policies for each category
        $this->command->info('Creating booking policies...');
        foreach ($categoryModels as $index => $category) {
            $policyData = [
                'room_category_id' => $category->id,
                'name' => $category->name . ' Policy',
                'description' => 'Booking policy for ' . $category->name,
                'is_active' => true,
            ];

            // Different policies based on room type
            switch ($index) {
                case 0: // Standard Practice Room
                    $policyData = array_merge($policyData, [
                        'max_booking_duration_hours' => 3,
                        'min_booking_duration_hours' => 1,
                        'max_advance_booking_days' => 14,
                        'min_advance_booking_hours' => 2,
                        'cancellation_policy' => 'Cancel at least 24 hours in advance for a full refund.',
                        'cancellation_hours' => 24,
                        'max_bookings_per_week' => 5,
                    ]);
                    break;
                case 1: // Recording Studio
                    $policyData = array_merge($policyData, [
                        'max_booking_duration_hours' => 8,
                        'min_booking_duration_hours' => 2,
                        'max_advance_booking_days' => 30,
                        'min_advance_booking_hours' => 48,
                        'cancellation_policy' => 'Cancel at least 72 hours in advance for a full refund.',
                        'cancellation_hours' => 72,
                        'max_bookings_per_week' => 3,
                    ]);
                    break;
                case 2: // Rehearsal Hall
                    $policyData = array_merge($policyData, [
                        'max_booking_duration_hours' => 6,
                        'min_booking_duration_hours' => 2,
                        'max_advance_booking_days' => 21,
                        'min_advance_booking_hours' => 24,
                        'cancellation_policy' => 'Cancel at least 48 hours in advance for a full refund.',
                        'cancellation_hours' => 48,
                        'max_bookings_per_week' => 3,
                    ]);
                    break;
                case 3: // Teaching Studio
                    $policyData = array_merge($policyData, [
                        'max_booking_duration_hours' => 4,
                        'min_booking_duration_hours' => 0.5,
                        'max_advance_booking_days' => 60,
                        'min_advance_booking_hours' => 1,
                        'cancellation_policy' => 'Cancel at least 12 hours in advance for a full refund.',
                        'cancellation_hours' => 12,
                        'max_bookings_per_week' => 10,
                    ]);
                    break;
                case 4: // Percussion Room
                    $policyData = array_merge($policyData, [
                        'max_booking_duration_hours' => 4,
                        'min_booking_duration_hours' => 1,
                        'max_advance_booking_days' => 14,
                        'min_advance_booking_hours' => 4,
                        'cancellation_policy' => 'Cancel at least 24 hours in advance for a full refund.',
                        'cancellation_hours' => 24,
                        'max_bookings_per_week' => 4,
                    ]);
                    break;
            }

            BookingPolicy::create($policyData);
        }

        // Create rooms for each category
        $this->command->info('Creating rooms...');
        $roomsData = [
            // Standard Practice Rooms
            [
                'room_category_id' => $categoryModels[0]->id,
                'name' => 'Practice Room A',
                'description' => 'Small practice room with upright piano.',
                'capacity' => 3,
                'hourly_rate' => 15.00,
                'is_active' => true,
                'size_sqft' => 100,
                'amenities' => ['Piano', 'Music Stand', 'Chair'],
                'specifications' => ['Sound Isolation Rating: Medium', 'Natural Light: Yes'],
                'photos' => ['practice_room_a_1.jpg', 'practice_room_a_2.jpg'],
            ],
            [
                'room_category_id' => $categoryModels[0]->id,
                'name' => 'Practice Room B',
                'description' => 'Medium practice room with grand piano.',
                'capacity' => 5,
                'hourly_rate' => 20.00,
                'is_active' => true,
                'size_sqft' => 150,
                'amenities' => ['Grand Piano', 'Music Stands', 'Chairs'],
                'specifications' => ['Sound Isolation Rating: High', 'Natural Light: Yes'],
                'photos' => ['practice_room_b_1.jpg', 'practice_room_b_2.jpg'],
            ],
            
            // Recording Studios
            [
                'room_category_id' => $categoryModels[1]->id,
                'name' => 'Studio 1',
                'description' => 'Professional recording studio with control room and live room.',
                'capacity' => 8,
                'hourly_rate' => 75.00,
                'is_active' => true,
                'size_sqft' => 400,
                'amenities' => ['Pro Tools', 'Mixing Console', 'Microphones', 'Monitors'],
                'specifications' => ['Sound Isolation Rating: Very High', 'Control Room: Yes', 'Live Room: Yes'],
                'photos' => ['studio_1_1.jpg', 'studio_1_2.jpg'],
            ],
            
            // Rehearsal Halls
            [
                'room_category_id' => $categoryModels[2]->id,
                'name' => 'Main Hall',
                'description' => 'Large rehearsal space for full bands and orchestras.',
                'capacity' => 30,
                'hourly_rate' => 60.00,
                'is_active' => true,
                'size_sqft' => 800,
                'amenities' => ['PA System', 'Drum Kit', 'Piano', 'Chairs', 'Music Stands'],
                'specifications' => ['Sound Isolation Rating: High', 'Stage Area: Yes'],
                'photos' => ['main_hall_1.jpg', 'main_hall_2.jpg'],
            ],
            
            // Teaching Studios
            [
                'room_category_id' => $categoryModels[3]->id,
                'name' => 'Teaching Room 1',
                'description' => 'Comfortable room for one-on-one lessons.',
                'capacity' => 4,
                'hourly_rate' => 25.00,
                'is_active' => true,
                'size_sqft' => 120,
                'amenities' => ['Piano', 'Whiteboard', 'Music Stand', 'Chairs'],
                'specifications' => ['Sound Isolation Rating: Medium', 'Natural Light: Yes'],
                'photos' => ['teaching_1_1.jpg', 'teaching_1_2.jpg'],
            ],
            
            // Percussion Rooms
            [
                'room_category_id' => $categoryModels[4]->id,
                'name' => 'Percussion Studio',
                'description' => 'Specialized room for percussion practice with drum kit and percussion instruments.',
                'capacity' => 6,
                'hourly_rate' => 30.00,
                'is_active' => true,
                'size_sqft' => 300,
                'amenities' => ['Drum Kit', 'Congas', 'Bongos', 'Timpani', 'Xylophone'],
                'specifications' => ['Sound Isolation Rating: Very High', 'Reinforced Floor: Yes'],
                'photos' => ['percussion_1_1.jpg', 'percussion_1_2.jpg'],
            ],
        ];

        $roomModels = [];
        foreach ($roomsData as $roomData) {
            $roomModels[] = Room::create($roomData);
        }

        // Create room equipment
        $this->command->info('Creating room equipment...');
        $equipmentData = [
            [
                'room_id' => $roomModels[0]->id,
                'name' => 'Yamaha Upright Piano',
                'description' => 'Well-maintained upright piano',
                'status' => 'available',
            ],
            [
                'room_id' => $roomModels[1]->id,
                'name' => 'Steinway Grand Piano',
                'description' => 'Concert grand piano',
                'status' => 'available',
            ],
            [
                'room_id' => $roomModels[2]->id,
                'name' => 'Pro Tools HD System',
                'description' => 'Professional recording system',
                'status' => 'available',
            ],
            [
                'room_id' => $roomModels[3]->id,
                'name' => 'JBL PA System',
                'description' => 'Complete PA system with speakers and mixer',
                'status' => 'available',
            ],
            [
                'room_id' => $roomModels[4]->id,
                'name' => 'Yamaha Digital Piano',
                'description' => 'Digital piano with weighted keys',
                'status' => 'available',
            ],
            [
                'room_id' => $roomModels[5]->id,
                'name' => 'Pearl Drum Kit',
                'description' => 'Complete drum kit with cymbals',
                'status' => 'available',
            ],
        ];

        foreach ($equipmentData as $equipment) {
            RoomEquipment::create($equipment);
        }

        // Create maintenance schedules
        $this->command->info('Creating maintenance schedules...');
        $maintenanceData = [
            [
                'room_id' => $roomModels[0]->id,
                'title' => 'Piano Tuning',
                'start_time' => Carbon::now()->addDays(7)->setHour(8)->setMinute(0),
                'end_time' => Carbon::now()->addDays(7)->setHour(10)->setMinute(0),
                'description' => 'Piano tuning',
                'status' => 'scheduled',
            ],
            [
                'room_id' => $roomModels[2]->id,
                'title' => 'Studio Equipment Maintenance',
                'start_time' => Carbon::now()->addDays(14)->setHour(18)->setMinute(0),
                'end_time' => Carbon::now()->addDays(14)->setHour(22)->setMinute(0),
                'description' => 'Software updates and equipment maintenance',
                'status' => 'scheduled',
            ],
            [
                'room_id' => $roomModels[5]->id,
                'title' => 'Drum Kit Maintenance',
                'start_time' => Carbon::now()->addDays(10)->setHour(9)->setMinute(0),
                'end_time' => Carbon::now()->addDays(10)->setHour(12)->setMinute(0),
                'description' => 'Drum kit maintenance and replacement of drum heads',
                'status' => 'scheduled',
            ],
        ];

        foreach ($maintenanceData as $maintenance) {
            MaintenanceSchedule::create($maintenance);
        }

        // Create bookings
        $this->command->info('Creating bookings...');
        $users = User::all();
        
        // Ensure we have at least 2 users for the seeder
        if ($users->count() < 2) {
            $this->command->info('Creating additional users for seeding...');
            $usersToCreate = 2 - $users->count();
            $newUsers = User::factory()->count($usersToCreate)->create();
            $users = $users->merge($newUsers);
        }
        
        // Create a mix of bookings with different statuses
        foreach ($roomModels as $room) {
            foreach ($users as $index => $user) {
                // Create a scheduled booking in the future
                $startTime = Carbon::now()->addDays(rand(1, 14))->setHour(rand(9, 20))->setMinute(0);
                $endTime = (clone $startTime)->addHours(rand(1, 3));
                
                Booking::create([
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => 'Regular practice session',
                    'status' => 'reserved',
                    'state' => 'scheduled',
                    'total_price' => $room->hourly_rate * $startTime->diffInHours($endTime),
                    'payment_status' => 'pending',
                ]);
                
                // Create a completed booking in the past
                $startTime = Carbon::now()->subDays(rand(1, 30))->setHour(rand(9, 20))->setMinute(0);
                $endTime = (clone $startTime)->addHours(rand(1, 3));
                
                Booking::create([
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => 'Past practice session',
                    'status' => 'completed',
                    'state' => 'completed',
                    'check_in_time' => (clone $startTime)->addMinutes(rand(-15, 15)),
                    'check_out_time' => (clone $endTime)->addMinutes(rand(-15, 15)),
                    'total_price' => $room->hourly_rate * $startTime->diffInHours($endTime),
                    'payment_status' => 'paid',
                ]);
                
                // Add some variety with other booking states
                if ($index % 5 == 0) {
                    // Cancelled booking
                    $startTime = Carbon::now()->addDays(rand(5, 20))->setHour(rand(9, 20))->setMinute(0);
                    $endTime = (clone $startTime)->addHours(rand(1, 3));
                    
                    Booking::create([
                        'room_id' => $room->id,
                        'user_id' => $user->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'notes' => 'Cancelled session',
                        'status' => 'cancelled',
                        'state' => 'cancelled',
                        'total_price' => $room->hourly_rate * $startTime->diffInHours($endTime),
                        'payment_status' => 'refunded',
                    ]);
                } else if ($index % 5 == 1) {
                    // No-show booking
                    $startTime = Carbon::now()->subDays(rand(1, 10))->setHour(rand(9, 20))->setMinute(0);
                    $endTime = (clone $startTime)->addHours(rand(1, 3));
                    
                    Booking::create([
                        'room_id' => $room->id,
                        'user_id' => $user->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'notes' => 'No-show session',
                        'status' => 'no_show',
                        'state' => 'no_show',
                        'total_price' => $room->hourly_rate * $startTime->diffInHours($endTime),
                        'payment_status' => 'charged',
                    ]);
                }
            }
        }

        // Create policy overrides for some users
        $this->command->info('Creating policy overrides...');
        $policies = BookingPolicy::all();
        
        foreach ($policies as $index => $policy) {
            if ($index % 2 == 0) {
                // Create override for first user
                BookingPolicyOverride::updateOrCreate(
                    [
                        'booking_policy_id' => $policy->id,
                        'user_id' => $users[0]->id,
                    ],
                    [
                        'overrides' => [
                            'max_booking_duration_hours' => $policy->max_booking_duration_hours + 2,
                            'max_bookings_per_week' => $policy->max_bookings_per_week + 3,
                        ],
                    ]
                );
            } else {
                // Create override for second user
                BookingPolicyOverride::updateOrCreate(
                    [
                        'booking_policy_id' => $policy->id,
                        'user_id' => $users[1]->id,
                    ],
                    [
                        'overrides' => [
                            'min_advance_booking_hours' => 0,
                            'cancellation_hours' => (int)($policy->cancellation_hours / 2),
                        ],
                    ]
                );
            }
        }

        // Create room favorites
        $this->command->info('Creating room favorites...');
        foreach ($users as $index => $user) {
            // Each user favorites 2 random rooms
            $favoriteRooms = collect($roomModels)->random(min(2, count($roomModels)));
            foreach ($favoriteRooms as $room) {
                RoomFavorite::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'room_id' => $room->id,
                    ],
                    []
                );
            }
        }

        // Create waitlist entries
        $this->command->info('Creating waitlist entries...');
        // Pick a popular time slot that's already booked
        $popularRoom = $roomModels[2]; // Studio 1
        $popularStartTime = Carbon::now()->addDays(3)->setHour(18)->setMinute(0);
        $popularEndTime = (clone $popularStartTime)->addHours(3);
        
        // Create a booking for this time slot
        Booking::create([
            'room_id' => $popularRoom->id,
            'user_id' => $users[0]->id,
            'start_time' => $popularStartTime,
            'end_time' => $popularEndTime,
            'notes' => 'Popular time slot',
            'status' => 'reserved',
            'state' => 'scheduled',
            'total_price' => $popularRoom->hourly_rate * $popularStartTime->diffInHours($popularEndTime),
            'payment_status' => 'pending',
        ]);
        
        // Create waitlist entries for other users
        $otherUsers = $users->slice(1, min(3, $users->count() - 1));
        foreach ($otherUsers as $user) {
            WaitlistEntry::create([
                'room_id' => $popularRoom->id,
                'user_id' => $user->id,
                'preferred_date' => $popularStartTime->toDateString(),
                'preferred_start_time' => $popularStartTime->format('H:i:s'),
                'preferred_end_time' => $popularEndTime->format('H:i:s'),
                'notes' => 'Hoping for a cancellation',
                'status' => 'waiting',
            ]);
        }

        $this->command->info('Practice Space module seeded successfully!');
    }
} 