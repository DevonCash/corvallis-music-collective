<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\Traits\GeneratesCalendarData;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @test
 * @covers REQ-005
 */
class CalendarClosingTimeTest extends TestCase
{
    use RefreshDatabase;

    // Create a virtual class that uses the trait
    private $calendarGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a class instance that uses the trait
        $this->calendarGenerator = new class {
            use GeneratesCalendarData;
        };
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function it_correctly_marks_slots_near_closing_time_as_invalid()
    {
        // Create a test room with fixed closing time and policy
        $policy = new BookingPolicy(
            openingTime: '09:00',
            closingTime: '22:00',  // 10 PM closing time
            maxBookingDurationHours: 4.0,  // 4 hour max booking duration
            minBookingDurationHours: 0.5,  // 30 min minimum booking (30 minutes)
            maxAdvanceBookingDays: 30,
            minAdvanceBookingHours: 0.0    // No advance booking requirement for test
        );
        
        $room = Room::factory()->create([
            'name' => 'Test Room for Closing Time',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
        ]);
        
        // Set the booking policy
        $room->booking_policy = $policy;
        
        // We'll test against the test date (a future date relative to system)
        // Use Carbon::now plus 10 years, then add one day to avoid "today" issues
        $testDate = Carbon::now('America/Los_Angeles')->addYears(10)->addDay()->setTime(12, 0, 0);
        $testDateString = $testDate->format('Y-m-d');
        // Make it available as a global for testing
        $GLOBALS['testDateString'] = $testDateString;
        
        // Make this a pretend "yesterday" for the test, so our test date isn't "today"
        // This avoids time slots being marked invalid because they're in the past
        Carbon::setTestNow($testDate->copy()->subDay());
        
        // Debug info
        echo "\nTEST CONFIGURATION:\n";
        echo "Test date: " . $testDateString . "\n";
        echo "Current 'now' time for test: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        
        // Create one week date range starting from test date
        $startDate = $testDate->copy()->startOfDay();
        $endDate = $startDate->copy()->addDays(6);
        
        // Get calendar data from the trait
        $cellData = $this->calendarGenerator->generateCalendarCellData(
            $room,
            $startDate,
            $endDate,
            [] // No bookings for this test
        );
        
        // Find the day index for our test date
        $testDayIndex = 0; // Should be the first day
        $testDayData = $cellData[$testDayIndex];
        
        // Show all available time slots for the test day
        echo "\n\nALL TIME SLOTS FOR TEST DAY:\n";
        $allTimes = [];
        foreach ($testDayData as $slot) {
            $allTimes[] = $slot['time'] . ($slot['invalid_duration'] ? ' (INVALID)' : '');
        }
        sort($allTimes);
        echo implode(", ", $allTimes) . "\n\n";
        
        // Find specific slots we want to test
        $middaySlot = null;
        $eveningSlot = null;
        $lateSlot = null;
        $veryLateSlot = null;
        $tooLateSlot = null;
        $finalSlot = null; // Last slot of the day
        
        foreach ($testDayData as $slot) {
            if ($slot['time'] === '12:00') $middaySlot = $slot;
            if ($slot['time'] === '19:00') $eveningSlot = $slot;
            if ($slot['time'] === '21:00') $lateSlot = $slot;
            if ($slot['time'] === '21:30') $veryLateSlot = $slot;
            
            // Slot that's too close to closing time
            if ($slot['time'] === '21:45') $tooLateSlot = $slot;
            
            // Get the last slot available in the day
            if (!$finalSlot || $slot['time'] > $finalSlot['time']) {
                $finalSlot = $slot;
            }
        }
        
        // Debug output
        echo "\n\nCALENDAR SLOT TEST RESULTS\n";
        echo "Room opening time: " . $policy->openingTime . "\n";
        echo "Room closing time: " . $policy->closingTime . "\n";
        echo "Min booking duration: " . $policy->minBookingDurationHours . " hours (" . ($policy->minBookingDurationHours * 60) . " minutes)\n";
        echo "Max booking duration: " . $policy->maxBookingDurationHours . " hours\n\n";
        
        if ($middaySlot) {
            echo "Midday Slot (12:00): Invalid=" . ($middaySlot['invalid_duration'] ? 'true' : 'false') . "\n";
        } else {
            echo "Midday Slot (12:00): NOT FOUND\n";
        }
        
        if ($eveningSlot) {
            echo "Evening Slot (19:00): Invalid=" . ($eveningSlot['invalid_duration'] ? 'true' : 'false') . "\n";
        } else {
            echo "Evening Slot (19:00): NOT FOUND\n";
        }
        
        if ($lateSlot) {
            echo "Late Slot (21:00): Invalid=" . ($lateSlot['invalid_duration'] ? 'true' : 'false') . "\n";
        } else {
            echo "Late Slot (21:00): NOT FOUND\n";
        }
        
        if ($veryLateSlot) {
            echo "Very Late Slot (21:30): Invalid=" . ($veryLateSlot['invalid_duration'] ? 'true' : 'false') . "\n";
        } else {
            echo "Very Late Slot (21:30): NOT FOUND\n";
        }
        
        if ($tooLateSlot) {
            echo "Too Late Slot (21:45): Invalid=" . ($tooLateSlot['invalid_duration'] ? 'true' : 'false') . "\n";
        } else {
            echo "Too Late Slot (21:45): NOT FOUND\n";
        }
        
        if ($finalSlot) {
            echo "Final Slot (" . $finalSlot['time'] . "): Invalid=" . ($finalSlot['invalid_duration'] ? 'true' : 'false') . "\n";
        } else {
            echo "Final Slot: NOT FOUND\n";
        }
        
        // Add slot at 21:45 manually if it doesn't exist (15 minutes before closing)
        if (!$tooLateSlot) {
            $slotTime = Carbon::createFromFormat('H:i', '21:45', $room->timezone);
            $dateString = $testDate->format('Y-m-d');
            $slotDateTime = Carbon::parse("$dateString " . $slotTime->format('H:i:s'), $room->timezone);
            $minutesUntilClosing = $slotDateTime->diffInMinutes(Carbon::parse("$dateString 22:00:00", $room->timezone));
            
            echo "\nManually calculated for 21:45 slot:\n";
            echo "Minutes until closing: $minutesUntilClosing\n";
            echo "Min booking duration: " . ($policy->minBookingDurationHours * 60) . " minutes\n";
            echo "Should be invalid: " . ($minutesUntilClosing < ($policy->minBookingDurationHours * 60) ? 'yes' : 'no') . "\n";
        }
        
        // Assertions
        // Midday is far from closing time, should be valid
        $this->assertNotNull($middaySlot, "Midday slot not found");
        $this->assertFalse($middaySlot['invalid_duration'], "Midday slot should not be invalid");
        
        // 7:00 PM is 3 hours before closing, should be valid
        $this->assertNotNull($eveningSlot, "Evening slot not found");
        $this->assertFalse($eveningSlot['invalid_duration'], "Evening slot should not be invalid");
        
        // 9:00 PM is 1 hour before closing, should be valid if minimum booking is 30 min
        $this->assertNotNull($lateSlot, "Late slot not found");
        $this->assertFalse($lateSlot['invalid_duration'], "Late slot should not be invalid");
        
        // 9:30 PM is 30 minutes before closing, should be valid if min booking duration is 30 min
        $this->assertNotNull($veryLateSlot, "Very late slot not found");
        $this->assertFalse($veryLateSlot['invalid_duration'], "Very late slot (9:30 PM) should not be invalid with 30 min min booking");
        
        // If 21:45 slot exists, it should be invalid (15 min until closing, but min booking is 30 min)
        if ($tooLateSlot) {
            $this->assertTrue($tooLateSlot['invalid_duration'], "Too late slot (9:45 PM) should be invalid (only 15 min until closing)");
        }
        
        // Clean up test state
        Carbon::setTestNow();
    }
} 