<?php

namespace Database\Factories;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\Payments\Models\States\PaymentState\Pending;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Payments\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Modules\Payments\Models\Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'stripe_payment_intent_id' => 'pi_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'method' => fake()->randomElement(['card', 'bank_transfer', 'cash']),
            'amount' => fake()->numberBetween(1000, 50000), // $10-$500 in cents
            'state' => Pending::class, // Default state is pending
            'payable_type' => User::class, // Default to User as payable type
            'payable_id' => function (array $attributes) {
                return $attributes['user_id'];
            },
        ];
    }

    /**
     * Set a specific user for the payment
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'payable_id' => $user->id,
        ]);
    }

    /**
     * Set a specific product for the payment
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Set the payment state
     */
    public function withState(string $stateClass): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => $stateClass,
        ]);
    }

    /**
     * Set a specific payable entity for the payment
     */
    public function forPayable(Model $payable): static
    {
        return $this->state(fn (array $attributes) => [
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
        ]);
    }
} 