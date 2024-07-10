<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Venue;
use DateInterval;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_time = fake()->dateTimeBetween(
            $startDate = "-1 month",
            $endDate = "+2 months"
        );

        $door_time = fake()
            ->optional()
            ->dateTimeBetween(
                $startDate = $start_time->sub(
                    DateInterval::createFromDateString("1 hour")
                ),
                $endDate = $start_time
            );

        $end_time = fake()
            ->optional()
            ->dateTimeBetween(
                $startDate = $start_time,
                $endDate = $start_time->add(
                    DateInterval::createFromDateString("2 hours")
                )
            );

        return [
            "name" => fake()->name(),
            "description" => fake()->realText(),
            "links" => [
                ["label" => fake()->word(), "url" => fake()->url()],
                ["label" => fake()->word(), "url" => fake()->url()],
                ["label" => fake()->word(), "url" => fake()->url()],
            ],
            "door_time" => $door_time,
            "start_time" => $start_time,
            "end_time" => $end_time,
            "price" => [
                [
                    "label" => "General Admission",
                    "amount" => fake()->randomFloat(2, 0, 100),
                    "currency" => "USD",
                ],
            ],
            "published_at" => fake()
                ->optional($weight = 0.9)
                ->dateTimeBetween(
                    $startDate = "-1 years",
                    $endDate = "+2 weeks"
                ),
            "venue_id" => Venue::factory(),
        ];
    }
}
