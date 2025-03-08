<?php

namespace CorvMC\PracticeSpace\Tests\Unit;

use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomCategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_room_category()
    {
        $category = new RoomCategory([
            'name' => 'Drum Room',
            'description' => 'Rooms equipped for drummers',
            'is_active' => true,
        ]);

        $this->assertEquals('Drum Room', $category->name);
        $this->assertEquals('Rooms equipped for drummers', $category->description);
        $this->assertTrue($category->is_active);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'name',
            'description',
            'is_active',
        ];

        $category = new RoomCategory();
        $this->assertEquals($expectedFillable, $category->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $expectedCasts = [
            'is_active' => 'boolean',
        ];

        $category = new RoomCategory();
        $this->assertArrayHasKey('is_active', $category->getCasts());
        $this->assertEquals('boolean', $category->getCasts()['is_active']);
    }
} 