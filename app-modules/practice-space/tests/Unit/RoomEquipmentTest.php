<?php

namespace CorvMC\PracticeSpace\Tests\Unit;

use CorvMC\PracticeSpace\Models\RoomEquipment;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @covers REQ-003
     */
    public function it_can_create_room_equipment()
    {
        $equipment = new RoomEquipment([
            'room_id' => 1,
            'name' => 'Drum Kit',
            'description' => 'Professional drum kit',
            'quantity' => 1,
            'condition' => 'Good',
            'last_maintenance_date' => '2023-01-15',
        ]);

        $this->assertEquals(1, $equipment->room_id);
        $this->assertEquals('Drum Kit', $equipment->name);
        $this->assertEquals('Professional drum kit', $equipment->description);
        $this->assertEquals(1, $equipment->quantity);
        $this->assertEquals('Good', $equipment->condition);
        $this->assertEquals('2023-01-15', $equipment->last_maintenance_date->format('Y-m-d'));
    }

    /**
     * @test
     * @covers REQ-003
     */
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'room_id',
            'name',
            'description',
            'quantity',
            'condition',
            'last_maintenance_date',
        ];

        $equipment = new RoomEquipment();
        $this->assertEquals($expectedFillable, $equipment->getFillable());
    }

    /**
     * @test
     * @covers REQ-003
     */
    public function it_has_correct_casts()
    {
        $equipment = new RoomEquipment();
        $this->assertArrayHasKey('quantity', $equipment->getCasts());
        $this->assertEquals('integer', $equipment->getCasts()['quantity']);
        $this->assertArrayHasKey('last_maintenance_date', $equipment->getCasts());
        $this->assertEquals('date', $equipment->getCasts()['last_maintenance_date']);
    }
} 