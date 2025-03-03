<?php

namespace Database\Factories;

use App\Modules\Payments\Models\Product;
use App\Modules\PracticeSpace\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\PracticeSpace\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Modules\PracticeSpace\Models\Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Room ' . fake()->randomLetter() . fake()->numberBetween(1, 20),
            'product_id' => Product::factory(),
            'description' => fake()->paragraph(),
            'capacity' => fake()->numberBetween(1, 10),
            'amenities' => $this->generateAmenities(),
            'hours' => $this->generateHours(),
        ];
    }

    /**
     * Generate a set of random amenities
     */
    private function generateAmenities(): array
    {
        $possibleAmenities = [
            'wifi', 'amplifier', 'microphone', 'drum_kit', 'piano', 'keyboard',
            'speakers', 'projector', 'whiteboard', 'air_conditioning'
        ];
        
        $count = fake()->numberBetween(2, 6);
        $selectedKeys = fake()->randomElements($possibleAmenities, $count);
        
        $amenities = [];
        foreach ($selectedKeys as $key) {
            $amenities[$key] = true;
        }
        
        return $amenities;
    }

    /**
     * Generate operating hours for the room
     */
    private function generateHours(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];
        
        foreach ($days as $day) {
            $isOpen = fake()->boolean(80); // 80% chance to be open
            
            if ($isOpen) {
                $openHour = fake()->numberBetween(8, 12);
                $closeHour = fake()->numberBetween(17, 22);
                
                $hours[$day] = [
                    'open' => $openHour . ':00',
                    'close' => $closeHour . ':00',
                    'is_open' => true,
                ];
            } else {
                $hours[$day] = [
                    'open' => null,
                    'close' => null,
                    'is_open' => false,
                ];
            }
        }
        
        return $hours;
    }

    /**
     * Configure the room with a specific product
     */
    public function withProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
} 