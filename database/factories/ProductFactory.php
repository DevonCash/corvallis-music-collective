<?php

namespace Database\Factories;

use App\Modules\Payments\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Payments\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Modules\Payments\Models\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(2),
            'prices' => [
                'hourly' => [
                    'amount' => fake()->numberBetween(1000, 5000), // $10-$50 in cents
                    'currency' => 'usd'
                ],
                'daily' => [
                    'amount' => fake()->numberBetween(8000, 20000), // $80-$200 in cents
                    'currency' => 'usd'
                ]
            ],
            'stripe_product_id' => 'prod_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'is_visible' => fake()->boolean(80), // 80% chance to be visible
            'subscription_interval' => null,
        ];
    }

    /**
     * Configure the model to be invisible
     */
    public function invisible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }

    /**
     * Configure the model as a subscription
     */
    public function subscription(string $interval = 'month'): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_interval' => $interval,
        ]);
    }
} 