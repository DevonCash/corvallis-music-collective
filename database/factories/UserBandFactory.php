<?php

namespace Database\Factories;

use App\Models\UserBand;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserBandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserBand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => fake()->randomElement(['owner', 'manager', 'member']),
            'visible' => fake()->word(),
        ];
    }
}
