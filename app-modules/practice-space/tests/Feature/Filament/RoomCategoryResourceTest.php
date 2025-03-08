<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Filament;

use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource;

class RoomCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_room_category()
    {
        $this->actingAs($this->createAdminUser());

        $data = [
            'name' => 'Test Category',
            'description' => 'Test Description',
            'is_active' => true,
        ];

        // Create the category directly
        $category = RoomCategory::create($data);

        // Verify the data was saved correctly
        $this->assertDatabaseHas('practice_space_room_categories', [
            'name' => 'Test Category',
            'description' => 'Test Description',
            'is_active' => 1,
        ]);
        
        // Verify the model attributes
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('Test Description', $category->description);
        $this->assertTrue($category->is_active);
    }

    /** @test */
    public function it_can_update_room_category()
    {
        $this->actingAs($this->createAdminUser());

        // Create a category
        $category = RoomCategory::factory()->create();
        $originalName = $category->name;

        // Update data
        $data = [
            'name' => 'Updated Category',
            'description' => 'Updated Description',
            'is_active' => false,
        ];

        // Update the category
        $category->update($data);
        $category->refresh();

        // Verify the data was updated correctly
        $this->assertDatabaseHas('practice_space_room_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'description' => 'Updated Description',
            'is_active' => 0,
        ]);
        
        // Verify the model attributes
        $this->assertNotEquals($originalName, $category->name);
        $this->assertEquals('Updated Category', $category->name);
        $this->assertEquals('Updated Description', $category->description);
        $this->assertFalse($category->is_active);
    }

    /** @test */
    public function it_can_delete_room_category()
    {
        $this->actingAs($this->createAdminUser());

        // Create a category
        $category = RoomCategory::factory()->create();
        $categoryId = $category->id;
        
        // Verify it exists
        $this->assertDatabaseHas('practice_space_room_categories', [
            'id' => $categoryId,
        ]);

        // Delete the category
        $category->delete();

        // Verify it was deleted (not using assertSoftDeleted since the table doesn't have deleted_at)
        $this->assertDatabaseMissing('practice_space_room_categories', [
            'id' => $categoryId,
        ]);
    }
    
    /** @test */
    public function it_can_list_room_categories()
    {
        $this->actingAs($this->createAdminUser());
        
        // Create multiple categories
        $categories = RoomCategory::factory()->count(3)->create();
        
        // Verify we can retrieve them all
        $retrievedCategories = RoomCategory::all();
        
        $this->assertCount(3, $retrievedCategories);
        $this->assertEquals($categories->pluck('id')->sort()->values(), $retrievedCategories->pluck('id')->sort()->values());
    }
    
    /** @test */
    public function it_has_correct_resource_configuration()
    {
        // Test basic resource configuration
        $this->assertEquals(RoomCategory::class, RoomCategoryResource::getModel());
        $this->assertNotEmpty(RoomCategoryResource::getNavigationIcon());
        $this->assertEquals('Practice Space', RoomCategoryResource::getNavigationGroup());
        
        // Test that the resource has the expected pages
        $pages = RoomCategoryResource::getPages();
        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }
} 