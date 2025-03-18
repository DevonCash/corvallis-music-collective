<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\Finance\Models\Product;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomProductIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip these tests if the Finance module is not available
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Finance module is not available');
        }
    }

    /** @test */
    public function room_can_be_associated_with_product()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Practice Room Booking',
            'description' => 'Booking for practice room',
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        // Create a room and associate it with the product
        $room = Room::factory()->create([
            'product_id' => $product->id,
        ]);
        
        // Assert that the room is associated with the product
        $this->assertEquals($product->id, $room->product_id);
    }

    /** @test */
    public function room_has_product_relationship()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Practice Room Booking',
            'description' => 'Booking for practice room',
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        // Create a room and associate it with the product
        $room = Room::factory()->create([
            'product_id' => $product->id,
        ]);
        
        // Assert that the room has a product relationship
        $this->assertInstanceOf(Product::class, $room->product);
        $this->assertEquals($product->id, $room->product->id);
    }

    /** @test */
    public function room_hourly_rate_can_be_synced_with_product_price()
    {
        // Skip this test as we're now interacting directly with the product model
        $this->markTestSkipped('Room model methods for product syncing are deprecated');
    }

    /** @test */
    public function product_price_can_be_updated_when_room_hourly_rate_changes()
    {
        // Skip this test as we're now interacting directly with the product model
        $this->markTestSkipped('Room model methods for product syncing are deprecated');
    }

    /** @test */
    public function room_can_be_deactivated_which_deactivates_product()
    {
        // Skip this test as we're now interacting directly with the product model
        $this->markTestSkipped('Room model methods for product syncing are deprecated');
    }
} 