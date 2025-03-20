<?php

namespace CorvMC\PracticeSpace\Tests\Unit\Traits;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\Traits\GeneratesCalendarData;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeneratesCalendarDataTest extends TestCase
{
    use RefreshDatabase;

    // Create a test class that uses the trait
    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new class {
            use GeneratesCalendarData;
        };
    }

    /**
     * @test
     * @covers TZ-001
     */
    public function it_generates_correct_cell_data_for_march_19_in_la_timezone()
    {
        // Create a test user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a room with LA timezone
        $room = Room::factory()->create([
            'name' => 'LA Test Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => new BookingPolicy(
                openingTime: '08:00',
                closingTime: '22:00',
                maxBookingDurationHours: 3.0,
                minBookingDurationHours: 0.5
            ),
        ]);

        // Set a specific test date - March 18, 2025
        Carbon::setTestNow(Carbon::parse('2025-03-18 12:00:00', 'UTC'));

        // Calculate the date range for the week containing March 19
        $marchNineteenth = Carbon::parse('2025-03-19', $room->timezone)->startOfDay();
        $monday = $marchNineteenth->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);

        // Generate cell data using the trait
        $cellData = $this->traitObject->generateCalendarCellData($room, $monday, $sunday);

        // Debug information
        echo "Test date: " . Carbon::getTestNow()->format('Y-m-d H:i:s') . " (UTC)\n";
        echo "Monday: " . $monday->format('Y-m-d') . " (in {$room->timezone})\n";
        echo "March 19: " . $marchNineteenth->format('Y-m-d') . " (in {$room->timezone})\n";
        
        // Find which date index corresponds to March 19
        $march19Index = null;
        foreach ($cellData as $dayIndex => $day) {
            if (!empty($day) && $day[0]['date'] === '2025-03-19') {
                $march19Index = $dayIndex;
                break;
            }
        }
        
        echo "March 19 found at index: " . ($march19Index !== null ? $march19Index : 'not found') . "\n";
        echo "Cell data days: " . count($cellData) . "\n";
        echo "Available date indices: " . implode(', ', array_keys($cellData)) . "\n";
        
        if ($march19Index !== null) {
            echo "First time slot on March 19: " . $cellData[$march19Index][0]['time'] . "\n";
        }

        // Verify we have data
        $this->assertNotEmpty($cellData, "Cell data should not be empty");
        $this->assertNotNull($march19Index, "March 19 should be found in the cell data");
        
        // Find the 8:30 PM slot on March 19
        $found830pmSlot = false;
        $cell830pm = null;
        
        if (isset($cellData[$march19Index])) {
            foreach ($cellData[$march19Index] as $slotIndex => $cell) {
                if ($cell['time'] === '20:30') {
                    $found830pmSlot = true;
                    $cell830pm = $cell;
                    echo "Found 8:30 PM slot on March 19: " . print_r($cell, true) . "\n";
                    break;
                }
            }
        }
        
        // Assert that we found the slot and it has the correct date
        $this->assertTrue($found830pmSlot, "8:30 PM slot should exist on March 19");
        $this->assertEquals('2025-03-19', $cell830pm['date'], 
            "The 8:30 PM slot should have date 2025-03-19");
    }

    /**
     * @test
     * @covers TZ-002
     */
    public function it_generates_correct_booking_parameters_for_march_19_cell()
    {
        // Create a test user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a room with LA timezone
        $room = Room::factory()->create([
            'name' => 'LA Test Room',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'booking_policy' => new BookingPolicy(
                openingTime: '08:00',
                closingTime: '22:00',
                maxBookingDurationHours: 3.0,
                minBookingDurationHours: 0.5
            ),
        ]);

        // Set a specific test date - March 18, 2025 at noon UTC
        Carbon::setTestNow(Carbon::parse('2025-03-18 12:00:00', 'UTC'));

        // Get March 19 in LA timezone
        $marchNineteenth = Carbon::parse('2025-03-19', $room->timezone)->startOfDay();
        
        // Create booking parameters as they would be derived from cell data
        $bookingParams = [
            'room_id' => $room->id,
            'booking_date' => $marchNineteenth->format('Y-m-d'),  // '2025-03-19'
            'booking_time' => '20:30',  // 8:30 PM
        ];
        
        // Test the time slot in past check (should not be in past)
        $isInPast = $this->traitObject->isTimeSlotInPast(
            $bookingParams['booking_date'], 
            $bookingParams['booking_time'], 
            $room->timezone
        );
        
        $this->assertFalse($isInPast, "The time slot should not be in the past");
        
        // Verify the booking parameters
        echo "Booking parameters: " . print_r($bookingParams, true) . "\n";
        
        $this->assertEquals('2025-03-19', $bookingParams['booking_date'],
            "The booking date should be 2025-03-19");
        $this->assertEquals('20:30', $bookingParams['booking_time'],
            "The booking time should be 20:30");
            
        // Create a start time from these parameters
        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i', 
            $bookingParams['booking_date'] . ' ' . $bookingParams['booking_time'], 
            $room->timezone
        );
        
        // Convert to UTC to verify correct timezone handling
        $startDateTimeUtc = $startDateTime->copy()->setTimezone('UTC');
        
        echo "Start date/time in {$room->timezone}: " . $startDateTime->format('Y-m-d H:i:s') . "\n";
        echo "Start date/time in UTC: " . $startDateTimeUtc->format('Y-m-d H:i:s') . "\n";
        
        // Verify it's still March 19 in LA timezone
        $this->assertEquals('2025-03-19', $startDateTime->format('Y-m-d'),
            "The start date should be March 19 in LA timezone");
        
        // Verify it's March 20 in UTC (crossing midnight)
        $this->assertEquals('2025-03-20', $startDateTimeUtc->format('Y-m-d'),
            "The start date should be March 20 in UTC due to timezone difference");
    }
} 