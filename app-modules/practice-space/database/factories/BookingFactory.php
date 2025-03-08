<?php

namespace CorvMC\PracticeSpace\Database\Factories;

use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition()
    {
        $startTime = $this->faker->dateTimeBetween('+1 day', '+1 month');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(1, 4) . ' hours');

        return [
            'room_id' => Room::factory(),
            'user_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'reserved', // Legacy status field, can be overridden
            'state' => 'scheduled', // Default state, can be overridden
        ];
    }

    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'confirmed',
                'state' => 'confirmed',
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'state' => 'completed',
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'state' => 'cancelled',
            ];
        });
    }

    public function checkedIn()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'checked_in',
                'state' => 'checked_in',
                'check_in_time' => now(),
            ];
        });
    }

    public function noShow()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'no_show',
                'state' => 'no_show',
            ];
        });
    }
} 