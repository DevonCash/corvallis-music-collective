<?php

namespace CorvMC\PracticeSpace\Tests\Unit\Casts;

use CorvMC\PracticeSpace\Casts\BookingPolicyCast;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;

class BookingPolicyCastTest extends TestCase
{
    /** @test */
    public function it_can_cast_null_to_default_booking_policy()
    {
        $cast = new BookingPolicyCast();
        $result = $cast->get(new Room(), 'booking_policy', null, []);
        
        $this->assertInstanceOf(BookingPolicy::class, $result);
        $this->assertEquals('08:00', $result->openingTime);
        $this->assertEquals('22:00', $result->closingTime);
    }
    
    /** @test */
    public function it_can_cast_json_to_booking_policy()
    {
        $cast = new BookingPolicyCast();
        $json = json_encode([
            'opening_time' => '10:00',
            'closing_time' => '20:00',
            'min_booking_duration_hours' => 0.5,
            'max_booking_duration_hours' => 6,
        ]);
        
        $result = $cast->get(new Room(), 'booking_policy', $json, []);
        
        $this->assertInstanceOf(BookingPolicy::class, $result);
        $this->assertEquals('10:00', $result->openingTime);
        $this->assertEquals('20:00', $result->closingTime);
        $this->assertEquals(0.5, $result->minBookingDurationHours);
        $this->assertEquals(6, $result->maxBookingDurationHours);
    }
    
    /** @test */
    public function it_can_cast_array_to_booking_policy()
    {
        $cast = new BookingPolicyCast();
        $array = [
            'opening_time' => '10:00',
            'closing_time' => '20:00',
            'min_booking_duration_hours' => 0.5,
            'max_booking_duration_hours' => 6,
        ];
        
        $result = $cast->get(new Room(), 'booking_policy', $array, []);
        
        $this->assertInstanceOf(BookingPolicy::class, $result);
        $this->assertEquals('10:00', $result->openingTime);
        $this->assertEquals('20:00', $result->closingTime);
        $this->assertEquals(0.5, $result->minBookingDurationHours);
        $this->assertEquals(6, $result->maxBookingDurationHours);
    }
    
    /** @test */
    public function it_can_cast_booking_policy_to_json()
    {
        $cast = new BookingPolicyCast();
        $policy = new BookingPolicy(
            openingTime: '10:00',
            closingTime: '20:00',
            maxBookingDurationHours: 6,
            minBookingDurationHours: 0.5
        );
        
        $result = $cast->set(new Room(), 'booking_policy', $policy, []);
        
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('10:00', $decoded['opening_time']);
        $this->assertEquals('20:00', $decoded['closing_time']);
        $this->assertEquals(6, $decoded['max_booking_duration_hours']);
        $this->assertEquals(0.5, $decoded['min_booking_duration_hours']);
    }
    
    /** @test */
    public function it_can_cast_array_to_json()
    {
        $cast = new BookingPolicyCast();
        $array = [
            'opening_time' => '10:00',
            'closing_time' => '20:00',
            'min_booking_duration_hours' => 0.5,
            'max_booking_duration_hours' => 6,
        ];
        
        $result = $cast->set(new Room(), 'booking_policy', $array, []);
        
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('10:00', $decoded['opening_time']);
        $this->assertEquals('20:00', $decoded['closing_time']);
        $this->assertEquals(6, $decoded['max_booking_duration_hours']);
        $this->assertEquals(0.5, $decoded['min_booking_duration_hours']);
    }
    
    /** @test */
    public function it_returns_null_when_casting_null_to_json()
    {
        $cast = new BookingPolicyCast();
        $result = $cast->set(new Room(), 'booking_policy', null, []);
        
        $this->assertNull($result);
    }
    
    /** @test */
    public function it_throws_exception_when_casting_invalid_value_to_json()
    {
        $cast = new BookingPolicyCast();
        
        $this->expectException(\InvalidArgumentException::class);
        $cast->set(new Room(), 'booking_policy', 'invalid', []);
    }
} 