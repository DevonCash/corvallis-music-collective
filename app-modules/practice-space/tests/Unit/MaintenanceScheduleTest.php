<?php

namespace CorvMC\PracticeSpace\Tests\Unit;

use CorvMC\PracticeSpace\Models\MaintenanceSchedule;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_maintenance_schedule()
    {
        $startTime = now()->addDay()->setHour(8)->setMinute(0);
        $endTime = now()->addDay()->setHour(12)->setMinute(0);
        
        $maintenance = new MaintenanceSchedule([
            'room_id' => 1,
            'title' => 'Regular Maintenance',
            'description' => 'Monthly equipment check',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
            'technician_name' => 'John Doe',
            'technician_contact' => '555-1234',
            'notes' => 'Focus on drum kit',
        ]);

        $this->assertEquals(1, $maintenance->room_id);
        $this->assertEquals('Regular Maintenance', $maintenance->title);
        $this->assertEquals('Monthly equipment check', $maintenance->description);
        $this->assertEquals($startTime->format('Y-m-d H:i'), $maintenance->start_time->format('Y-m-d H:i'));
        $this->assertEquals($endTime->format('Y-m-d H:i'), $maintenance->end_time->format('Y-m-d H:i'));
        $this->assertEquals('scheduled', $maintenance->status);
        $this->assertEquals('John Doe', $maintenance->technician_name);
        $this->assertEquals('555-1234', $maintenance->technician_contact);
        $this->assertEquals('Focus on drum kit', $maintenance->notes);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'room_id',
            'title',
            'description',
            'start_time',
            'end_time',
            'status',
            'technician_name',
            'technician_contact',
            'notes',
        ];

        $maintenance = new MaintenanceSchedule();
        $this->assertEquals($expectedFillable, $maintenance->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $maintenance = new MaintenanceSchedule();
        $this->assertArrayHasKey('start_time', $maintenance->getCasts());
        $this->assertEquals('datetime', $maintenance->getCasts()['start_time']);
        $this->assertArrayHasKey('end_time', $maintenance->getCasts());
        $this->assertEquals('datetime', $maintenance->getCasts()['end_time']);
    }
} 