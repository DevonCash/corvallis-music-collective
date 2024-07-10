<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Venue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => fake()->words(fake()->numberBetween(1, 3), true),
            "description" => fake()->text(),
            "location" => [
                "type" => "Feature",
                "geometry" => [
                    "type" => "Point",
                    "coordinates" => [fake()->longitude(), fake()->latitude()],
                ],
                "properties" => [
                    "city" => fake()->city(),
                    "state" => fake()->state(),
                    "country" => fake()->country(),
                ],
            ],
        ];
    }
}
