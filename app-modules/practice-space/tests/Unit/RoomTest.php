<?php

namespace CorvMC\PracticeSpace\Tests\Unit;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_room()
    {
        $room = new Room([
            'name' => 'Test Room',
            'description' => 'A test practice room',
            'capacity' => 5,
            'hourly_rate' => 20.00,
            'is_active' => true,
        ]);

        $this->assertEquals('Test Room', $room->name);
        $this->assertEquals('A test practice room', $room->description);
        $this->assertEquals(5, $room->capacity);
        $this->assertEquals(20.00, $room->hourly_rate);
        $this->assertTrue($room->is_active);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'room_category_id',
            'name',
            'description',
            'capacity',
            'hourly_rate',
            'is_active',
            'photos',
            'specifications',
            'booking_policy',
        ];

        $room = new Room();
        $this->assertEquals($expectedFillable, $room->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $expectedCasts = [
            'capacity' => 'integer',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'photos' => 'array',
            'specifications' => 'array',
            'size_sqft' => 'integer',
            'amenities' => 'array',
            'booking_policy' => 'CorvMC\PracticeSpace\Casts\BookingPolicyCast',
        ];

        $room = new Room();
        foreach ($expectedCasts as $key => $value) {
            $this->assertArrayHasKey($key, $room->getCasts());
            $this->assertEquals($value, $room->getCasts()[$key]);
        }
    }
} 