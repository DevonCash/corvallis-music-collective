<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
            'capacity' => 5,
            'hourly_rate' => 25.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_supports_daily_recurring_bookings()
    {
        $startDate = Carbon::today()->setHour(10)->setMinute(0);
        
        // Create a daily recurring booking for 5 days
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startDate,
            'end_time' => $startDate->copy()->addHours(2),
            'is_recurring' => true,
            'recurring_pattern' => json_encode([
                'type' => 'daily',
                'interval' => 1, // Every day
                'occurrences' => 5, // 5 occurrences
            ]),
            'state' => 'confirmed',
        ]);
        
        // Check that the room is not available for the next 5 days at the same time
        for ($i = 0; $i < 5; $i++) {
            $checkDate = $startDate->copy()->addDays($i);
            $availableSlots = $this->room->getAvailableTimeSlots($checkDate);
            
            // The 10:00 and 11:00 slots should not be available
            $this->assertArrayNotHasKey('10:00', $availableSlots);
            $this->assertArrayNotHasKey('11:00', $availableSlots);
        }
        
        // The 6th day should be available
        $sixthDay = $startDate->copy()->addDays(5);
        $availableSlots = $this->room->getAvailableTimeSlots($sixthDay);
        $this->assertArrayHasKey('10:00', $availableSlots);
        $this->assertArrayHasKey('11:00', $availableSlots);
    }

    /** @test */
    public function it_supports_weekly_recurring_bookings()
    {
        $startDate = Carbon::today()->setHour(14)->setMinute(0);
        $dayOfWeek = $startDate->dayOfWeek;
        
        // Create a weekly recurring booking for 4 weeks
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startDate,
            'end_time' => $startDate->copy()->addHours(2),
            'is_recurring' => true,
            'recurring_pattern' => json_encode([
                'type' => 'weekly',
                'interval' => 1, // Every week
                'occurrences' => 4, // 4 occurrences
                'days_of_week' => [$dayOfWeek], // Same day of week
            ]),
            'state' => 'confirmed',
        ]);
        
        // Check that the room is not available for the next 4 weeks on the same day and time
        for ($i = 0; $i < 4; $i++) {
            $checkDate = $startDate->copy()->addWeeks($i);
            $availableSlots = $this->room->getAvailableTimeSlots($checkDate);
            
            // The 14:00 and 15:00 slots should not be available
            $this->assertArrayNotHasKey('14:00', $availableSlots);
            $this->assertArrayNotHasKey('15:00', $availableSlots);
        }
        
        // The 5th week should be available
        $fifthWeek = $startDate->copy()->addWeeks(4);
        $availableSlots = $this->room->getAvailableTimeSlots($fifthWeek);
        $this->assertArrayHasKey('14:00', $availableSlots);
        $this->assertArrayHasKey('15:00', $availableSlots);
    }

    /** @test */
    public function it_supports_monthly_recurring_bookings()
    {
        $startDate = Carbon::today()->setHour(16)->setMinute(0);
        $dayOfMonth = $startDate->day;
        
        // Create a monthly recurring booking for 3 months
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startDate,
            'end_time' => $startDate->copy()->addHours(2),
            'is_recurring' => true,
            'recurring_pattern' => json_encode([
                'type' => 'monthly',
                'interval' => 1, // Every month
                'occurrences' => 3, // 3 occurrences
                'day_of_month' => $dayOfMonth, // Same day of month
            ]),
            'state' => 'confirmed',
        ]);
        
        // Check that the room is not available for the next 3 months on the same day and time
        for ($i = 0; $i < 3; $i++) {
            $checkDate = $startDate->copy()->addMonths($i);
            $availableSlots = $this->room->getAvailableTimeSlots($checkDate);
            
            // The 16:00 and 17:00 slots should not be available
            $this->assertArrayNotHasKey('16:00', $availableSlots);
            $this->assertArrayNotHasKey('17:00', $availableSlots);
        }
        
        // The 4th month should be available
        $fourthMonth = $startDate->copy()->addMonths(3);
        $availableSlots = $this->room->getAvailableTimeSlots($fourthMonth);
        $this->assertArrayHasKey('16:00', $availableSlots);
        $this->assertArrayHasKey('17:00', $availableSlots);
    }
} 