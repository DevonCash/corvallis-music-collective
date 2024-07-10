<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "title" => fake()->words(10, true),
            "content" => fake()->realText(1000),
            "tags" => [fake()->word(), fake()->word(), fake()->word()],
            "published_at" => fake()
                ->optional($weight = 0.9)
                ->dateTimeBetween("-1 year", "2 weeks"),
        ];
    }
}
