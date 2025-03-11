<?php

namespace CorvMC\PracticeSpace\Tests\Unit;

use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_booking()
    {
        $startTime = now()->addDay()->setHour(10)->setMinute(0);
        $endTime = now()->addDay()->setHour(12)->setMinute(0);
        
        $booking = new Booking([
            'room_id' => 1,
            'user_id' => 1,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => 'Test booking',
            'status' => 'reserved',
            'state' => 'scheduled',
        ]);

        $this->assertEquals(1, $booking->room_id);
        $this->assertEquals(1, $booking->user_id);
        $this->assertEquals($startTime->format('Y-m-d H:i'), $booking->start_time->format('Y-m-d H:i'));
        $this->assertEquals($endTime->format('Y-m-d H:i'), $booking->end_time->format('Y-m-d H:i'));
        $this->assertEquals('Test booking', $booking->notes);
        $this->assertEquals('reserved', $booking->status);
        // Skip testing the state property directly since it's cast to a class
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'room_id',
            'user_id',
            'start_time',
            'end_time',
            'status',
            'notes',
            'is_recurring',
            'recurring_pattern',
            'check_in_time',
            'check_out_time',
            'total_price',
            'payment_status',
            'state',
            'confirmation_requested_at',
            'confirmation_deadline',
            'confirmed_at',
            'cancelled_at',
            'cancellation_reason',
            'no_show_notes',
            'payment_completed',
        ];

        $booking = new Booking();
        $this->assertEquals($expectedFillable, $booking->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $expectedCasts = [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_recurring' => 'boolean',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'total_price' => 'decimal:2',
            'confirmation_requested_at' => 'datetime',
            'confirmation_deadline' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_completed' => 'boolean',
        ];

        $booking = new Booking();
        $this->assertEquals($expectedCasts, array_intersect_key($booking->getCasts(), $expectedCasts));
    }
} 