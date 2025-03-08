<?php

namespace CorvMC\PracticeSpace\Tests\Unit\ValueObjects;

use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;

class BookingPolicyTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated_with_default_values()
    {
        $policy = new BookingPolicy();
        
        $this->assertEquals('08:00', $policy->openingTime);
        $this->assertEquals('22:00', $policy->closingTime);
        $this->assertEquals(8.0, $policy->maxBookingDurationHours);
        $this->assertEquals(0.5, $policy->minBookingDurationHours);
        $this->assertEquals(90, $policy->maxAdvanceBookingDays);
        $this->assertEquals(1.0, $policy->minAdvanceBookingHours);
        $this->assertEquals(24, $policy->cancellationHours);
        $this->assertEquals(5, $policy->maxBookingsPerWeek);
    }
    
    /** @test */
    public function it_can_be_instantiated_with_custom_values()
    {
        $policy = new BookingPolicy(
            openingTime: '10:00',
            closingTime: '20:00',
            maxBookingDurationHours: 6.0,
            minBookingDurationHours: 1.0,
            maxAdvanceBookingDays: 30,
            minAdvanceBookingHours: 2.0,
            cancellationHours: 48,
            maxBookingsPerWeek: 3
        );
        
        $this->assertEquals('10:00', $policy->openingTime);
        $this->assertEquals('20:00', $policy->closingTime);
        $this->assertEquals(6.0, $policy->maxBookingDurationHours);
        $this->assertEquals(1.0, $policy->minBookingDurationHours);
        $this->assertEquals(30, $policy->maxAdvanceBookingDays);
        $this->assertEquals(2.0, $policy->minAdvanceBookingHours);
        $this->assertEquals(48, $policy->cancellationHours);
        $this->assertEquals(3, $policy->maxBookingsPerWeek);
    }
    
    /** @test */
    public function it_can_be_created_from_array_with_snake_case_keys()
    {
        $data = [
            'opening_time' => '10:00',
            'closing_time' => '20:00',
            'max_booking_duration_hours' => 6.0,
            'min_booking_duration_hours' => 1.0,
            'max_advance_booking_days' => 30,
            'min_advance_booking_hours' => 2.0,
            'cancellation_hours' => 48,
            'max_bookings_per_week' => 3
        ];
        
        $policy = BookingPolicy::fromArray($data);
        
        $this->assertEquals('10:00', $policy->openingTime);
        $this->assertEquals('20:00', $policy->closingTime);
        $this->assertEquals(6.0, $policy->maxBookingDurationHours);
        $this->assertEquals(1.0, $policy->minBookingDurationHours);
        $this->assertEquals(30, $policy->maxAdvanceBookingDays);
        $this->assertEquals(2.0, $policy->minAdvanceBookingHours);
        $this->assertEquals(48, $policy->cancellationHours);
        $this->assertEquals(3, $policy->maxBookingsPerWeek);
    }
    
    /** @test */
    public function it_uses_default_values_for_missing_array_keys()
    {
        $data = [
            'opening_time' => '10:00',
            'closing_time' => '20:00',
        ];
        
        $policy = BookingPolicy::fromArray($data);
        
        $this->assertEquals('10:00', $policy->openingTime);
        $this->assertEquals('20:00', $policy->closingTime);
        $this->assertEquals(8.0, $policy->maxBookingDurationHours);
        $this->assertEquals(0.5, $policy->minBookingDurationHours);
        $this->assertEquals(90, $policy->maxAdvanceBookingDays);
        $this->assertEquals(1.0, $policy->minAdvanceBookingHours);
        $this->assertEquals(24, $policy->cancellationHours);
        $this->assertEquals(5, $policy->maxBookingsPerWeek);
    }
    
    /** @test */
    public function it_can_be_converted_to_array()
    {
        $policy = new BookingPolicy(
            openingTime: '10:00',
            closingTime: '20:00',
            maxBookingDurationHours: 6.0,
            minBookingDurationHours: 1.0
        );
        
        $array = $policy->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('10:00', $array['opening_time']);
        $this->assertEquals('20:00', $array['closing_time']);
        $this->assertEquals(6.0, $array['max_booking_duration_hours']);
        $this->assertEquals(1.0, $array['min_booking_duration_hours']);
    }
    
    /** @test */
    public function it_can_be_json_serialized()
    {
        $policy = new BookingPolicy(
            openingTime: '10:00',
            closingTime: '20:00'
        );
        
        $json = json_encode($policy);
        $decoded = json_decode($json, true);
        
        $this->assertIsString($json);
        $this->assertEquals('10:00', $decoded['opening_time']);
        $this->assertEquals('20:00', $decoded['closing_time']);
    }
    
    /** @test */
    public function it_validates_time_formats()
    {
        $this->expectException(\InvalidArgumentException::class);
        new BookingPolicy(openingTime: 'invalid');
    }
} 