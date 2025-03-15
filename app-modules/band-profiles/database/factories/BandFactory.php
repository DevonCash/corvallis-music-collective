<?php

namespace CorvMC\BandProfiles\Database\Factories;

use CorvMC\BandProfiles\Models\Band;
use Illuminate\Database\Eloquent\Factories\Factory;

class BandFactory extends Factory
{
    protected $model = Band::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'formation_date' => fake()->date(),
            'genre' => fake()->word(),
            'location' => fake()->city(),
            'bio' => fake()->paragraph(),
        ];
    }
} 