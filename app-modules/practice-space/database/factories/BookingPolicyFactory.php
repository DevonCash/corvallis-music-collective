<?php

namespace CorvMC\PracticeSpace\Database\Factories;

use CorvMC\PracticeSpace\Models\BookingPolicy;
use CorvMC\PracticeSpace\Models\RoomCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingPolicyFactory extends Factory
{
    protected $model = BookingPolicy::class;

    public function definition()
    {
        return [
            'room_category_id' => RoomCategory::factory(),
            'name' => $this->faker->words(3, true) . ' Policy',
            'description' => $this->faker->paragraph(),
            'max_booking_duration_hours' => $this->faker->numberBetween(2, 8),
            'min_booking_duration_hours' => 1,
            'max_advance_booking_days' => $this->faker->numberBetween(14, 60),
            'min_advance_booking_hours' => $this->faker->numberBetween(1, 24),
            'cancellation_policy' => $this->faker->paragraph(),
            'cancellation_hours' => $this->faker->numberBetween(12, 48),
            'max_bookings_per_week' => $this->faker->numberBetween(2, 7),
            'is_active' => true,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (BookingPolicy $policy) {
            // Additional setup after making the model
        })->afterCreating(function (BookingPolicy $policy) {
            // Additional setup after creating the model
        });
    }

    /**
     * Create a policy with strict limits.
     */
    public function strict()
    {
        return $this->state(function (array $attributes) {
            return [
                'max_booking_duration_hours' => 2,
                'min_booking_duration_hours' => 1,
                'max_advance_booking_days' => 14,
                'min_advance_booking_hours' => 24,
                'cancellation_hours' => 48,
                'max_bookings_per_week' => 2,
            ];
        });
    }

    /**
     * Create a policy with flexible limits.
     */
    public function flexible()
    {
        return $this->state(function (array $attributes) {
            return [
                'max_booking_duration_hours' => 8,
                'min_booking_duration_hours' => 0.5,
                'max_advance_booking_days' => 60,
                'min_advance_booking_hours' => 1,
                'cancellation_hours' => 12,
                'max_bookings_per_week' => 7,
            ];
        });
    }
} 