<?php

namespace Database\Factories;

use App\Modules\Payments\Models\Payment;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\Room;
use App\Modules\PracticeSpace\Models\States\BookingState\Scheduled;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\PracticeSpace\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Modules\PracticeSpace\Models\Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create a booking for a random future date and time
        $startTime = fake()->dateTimeBetween('+1 day', '+30 days');
        $endTime = (clone $startTime)->modify('+' . fake()->numberBetween(1, 4) . ' hours');
        
        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => Scheduled::class, // Default state is scheduled
        ];
    }

    /**
     * Set a specific user for the booking
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set a specific room for the booking
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => $room->id,
        ]);
    }

    /**
     * Set the booking state
     */
    public function withState(string $stateClass): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => $stateClass,
        ]);
    }

    /**
     * Set the booking for a specific time range
     */
    public function forTimeSlot(\DateTime $startTime, \DateTime $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
} 