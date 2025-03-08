<?php

namespace CorvMC\Finance\Database\Factories;

use App\Models\User;
use CorvMC\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\CorvMC\Finance\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 100),
            'status' => fake()->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'description' => fake()->sentence(),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'payment_date' => null,
            'payment_method' => null,
            'transaction_id' => null,
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'payment_method' => fake()->randomElement(['credit_card', 'paypal', 'bank_transfer']),
            'transaction_id' => fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_date' => null,
            'payment_method' => null,
            'transaction_id' => null,
        ]);
    }
} 