<?php

namespace Database\Seeders;

use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\Room;
use App\Modules\PracticeSpace\Models\States\BookingState\{Confirmed, Scheduled};
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure we have users
        if (User::count() === 0) {
            User::factory()->count(5)->create();
        }

        $users = User::all();
        $rooms = Room::all();

        if ($users->isEmpty() || $rooms->isEmpty()) {
            $this->command->info('Cannot create bookings: no users or rooms available.');
            return;
        }

        $bookings = [
            [
                'user_id' => $users->random()->id,
                'room_id' => $rooms->where('name', 'Practice Room A')->first()->id,
                'start_time' => Carbon::today()->setTime(10, 0),
                'end_time' => Carbon::today()->setTime(12, 0),
                'state' => Confirmed::class,
            ],
            [
                'user_id' => $users->random()->id,
                'room_id' => $rooms->where('name', 'Practice Room B')->first()->id,
                'start_time' => Carbon::today()->setTime(14, 0),
                'end_time' => Carbon::today()->setTime(16, 0),
                'state' => Confirmed::class,
            ],
            [
                'user_id' => $users->random()->id,
                'room_id' => $rooms->where('name', 'Ensemble Room')->first()->id,
                'start_time' => Carbon::tomorrow()->setTime(9, 0),
                'end_time' => Carbon::tomorrow()->setTime(12, 0),
                'state' => Confirmed::class,
            ],
            [
                'user_id' => $users->random()->id,
                'room_id' => $rooms->where('name', 'Studio')->first()->id,
                'start_time' => Carbon::tomorrow()->setTime(15, 0),
                'end_time' => Carbon::tomorrow()->setTime(19, 0),
                'state' => Scheduled::class,
            ],
        ];

        foreach ($bookings as $bookingData) {
            // Check if booking already exists
            $exists = Booking::where([
                'room_id' => $bookingData['room_id'],
                'start_time' => $bookingData['start_time'],
                'end_time' => $bookingData['end_time'],
            ])->exists();

            if (!$exists) {
                Booking::create($bookingData);
            }
        }
    }
}
