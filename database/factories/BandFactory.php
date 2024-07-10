<?php

namespace Database\Factories;

use App\Models\Band;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class BandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Band::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => fake()->word() . " " . fake()->word(),
            "description" => fake()->sentence(15),
            "tags" => [fake()->word(), fake()->word(), fake()->word()],
            "home_city" => fake()->word(),
            "published_at" => fake()
                ->optional($weight = 0.9)
                ->dateTime(),
            "links" => [
                ["label" => fake()->word(), "url" => fake()->url()],
                ["label" => fake()->word(), "url" => fake()->url()],
                ["label" => fake()->word(), "url" => fake()->url()],
            ],
            "deleted_at" => fake()->optional(0.1)->dateTime(),
        ];
    }
}
