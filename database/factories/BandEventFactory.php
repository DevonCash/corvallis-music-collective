<?php

namespace Database\Factories;

use App\Models\BandEvent;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class BandEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BandEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "featured" => fake()->word(),
            "cancelled_at" => fake()
                ->optional($weight = 0.1)
                ->dateTime(),
        ];
    }
}
