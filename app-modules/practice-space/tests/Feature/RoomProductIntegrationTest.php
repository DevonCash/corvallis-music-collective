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
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Practice Room Booking',
            'description' => 'Booking for practice room',
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        // Create a room with a different hourly rate
        $room = Room::factory()->create([
            'product_id' => $product->id,
            'hourly_rate' => 20.00,
        ]);
        
        // Sync the room's hourly rate with the product price
        $room->syncWithProduct();
        
        // Assert that the hourly rate was updated
        $this->assertEquals(25.00, $room->hourly_rate);
    }

    /** @test */
    public function product_price_can_be_updated_when_room_hourly_rate_changes()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Practice Room Booking',
            'description' => 'Booking for practice room',
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        // Create a room
        $room = Room::factory()->create([
            'product_id' => $product->id,
            'hourly_rate' => 25.00,
        ]);
        
        // Update the room's hourly rate
        $room->update(['hourly_rate' => 30.00]);
        
        // Update the product price based on the room's hourly rate
        $room->updateProductPrice();
        
        // Refresh the product
        $product->refresh();
        
        // Assert that the product price was updated
        $this->assertEquals(30.00, $product->price);
    }

    /** @test */
    public function room_can_be_deactivated_which_deactivates_product()
    {
        // Create a product
        $product = Product::factory()->create([
            'name' => 'Practice Room Booking',
            'description' => 'Booking for practice room',
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        // Create a room
        $room = Room::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        
        // Deactivate the room
        $room->update(['is_active' => false]);
        $room->deactivateProduct();
        
        // Refresh the product
        $product->refresh();
        
        // Assert that the product was deactivated
        $this->assertFalse($product->is_active);
    }
} 