<?php

namespace CorvMC\PracticeSpace\Database\Factories;

use CorvMC\PracticeSpace\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->paragraph(),
            'capacity' => $this->faker->numberBetween(1, 20),
            'hourly_rate' => $this->faker->randomFloat(2, 10, 100),
            'is_active' => true,
            'timezone' => $this->faker->randomElement(['America/Los_Angeles', 'America/New_York', 'America/Chicago', 'America/Denver']),
        ];
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
} 