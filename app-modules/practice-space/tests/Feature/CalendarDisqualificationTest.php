<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Room;
use App\Models\User;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\Traits\GeneratesCalendarData;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for calendar disqualification reasons
 * 
 * @test
 * @covers REQ-005
 */
class CalendarDisqualificationTest extends TestCase
{
    use RefreshDatabase;
    use GeneratesCalendarData;

    private User $user;
    private Room $room;
    private Carbon $testTime;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();

        // Create room with specific booking policy
        $policy = new BookingPolicy(
            openingTime: '10:00',
            closingTime: '22:00',
            maxBookingDurationHours: 4,
            minBookingDurationHours: 1,
            maxAdvanceBookingDays: 14,
            minAdvanceBookingHours: 1,
            cancellationHours: 24
        );
        
        $this->room = Room::factory()->create([
            'booking_policy' => $policy
        ]);

        // Set test time to 2 PM today
        $this->testTime = Carbon::today()->setHour(14)->setMinute(0)->setSecond(0);
    }

    /** @test */
    public function it_marks_past_time_slots_as_invalid()
    {
        Carbon::setTestNow($this->testTime);
        
        $startDate = Carbon::today();
        $endDate = Carbon::today();
        
        $cellData = $this->generateCalendarCellData($this->room, $startDate, $endDate, []);

        // Check morning slot (should be invalid)
        $morningSlot = $this->findSlotByTime($cellData[0], '10:00');
        $this->assertTrue($morningSlot['invalid_duration']);
        $this->assertEquals('past', $morningSlot['invalid_reason']);

        // Check current hour slot (should be invalid due to advance notice)
        $currentSlot = $this->findSlotByTime($cellData[0], '14:30');
        $this->assertTrue($currentSlot['invalid_duration']);
        $this->assertEquals('advance_notice', $currentSlot['invalid_reason']);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_marks_slots_too_close_to_current_time_as_invalid()
    {
        Carbon::setTestNow($this->testTime);
        
        $startDate = Carbon::today();
        $endDate = Carbon::today();
        
        $cellData = $this->generateCalendarCellData($this->room, $startDate, $endDate, []);

        // Check slot too close to current time (should be invalid)
        $tooCloseSlot = $this->findSlotByTime($cellData[0], '14:30');
        $this->assertTrue($tooCloseSlot['invalid_duration']);
        $this->assertEquals('advance_notice', $tooCloseSlot['invalid_reason']);

        // Check slot with enough advance notice (should be valid)
        $validSlot = $this->findSlotByTime($cellData[0], '15:30');
        $this->assertFalse($validSlot['invalid_duration']);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_marks_slots_too_close_to_closing_time_as_invalid()
    {
        Carbon::setTestNow($this->testTime);
        
        $startDate = Carbon::today();
        $endDate = Carbon::today();
        
        $cellData = $this->generateCalendarCellData($this->room, $startDate, $endDate, []);

        // Check slot too close to closing time (should be invalid)
        $tooLateSlot = $this->findSlotByTime($cellData[0], '21:30');
        $this->assertTrue($tooLateSlot['invalid_duration']);
        $this->assertEquals('closing_time', $tooLateSlot['invalid_reason']);

        // Check slot with enough time before closing (should be valid)
        $validSlot = $this->findSlotByTime($cellData[0], '21:00');
        $this->assertFalse($validSlot['invalid_duration']);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_marks_slots_adjacent_to_bookings_as_invalid()
    {
        Carbon::setTestNow($this->testTime);
        
        $startDate = Carbon::today();
        $endDate = Carbon::today();
        
        // Create a booking at 2 PM
        $bookings = [[
            'id' => 1,
            'date_index' => 0,
            'time_index' => 8, // 2 PM is 8th slot after 10 AM
            'slots' => 2,
            'is_current_user' => false
        ]];
        
        $cellData = $this->generateCalendarCellData($this->room, $startDate, $endDate, $bookings);

        // Check slot adjacent to booking (should be invalid)
        $adjacentSlot = $this->findSlotByTime($cellData[0], '13:30');
        $this->assertTrue($adjacentSlot['invalid_duration']);
        $this->assertEquals('adjacent_booking', $adjacentSlot['invalid_reason']);

        // Check slot far from booking (should be valid)
        $validSlot = $this->findSlotByTime($cellData[0], '16:00');
        $this->assertFalse($validSlot['invalid_duration']);

        Carbon::setTestNow();
    }

    private function findSlotByTime(array $dayData, string $time): ?array
    {
        foreach ($dayData as $slot) {
            if ($slot['time'] === $time) {
                return $slot;
            }
        }
        return null;
    }
} 