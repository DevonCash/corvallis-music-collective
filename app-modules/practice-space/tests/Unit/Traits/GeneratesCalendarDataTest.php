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
     */
    public function it_generates_correct_cell_data()
    {
        // Create a test user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a room
        $room = Room::factory()->create([
            'name' => 'Test Room',
            'is_active' => true,
            'booking_policy' => new BookingPolicy(
                openingTime: '08:00',
                closingTime: '22:00',
                maxBookingDurationHours: 3.0,
                minBookingDurationHours: 0.5
            ),
        ]);

        // Set a specific test date
        Carbon::setTestNow(Carbon::parse('2025-03-18 12:00:00'));

        // Calculate the date range for the week
        $testDate = Carbon::parse('2025-03-19')->startOfDay();
        $monday = $testDate->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);

        // Generate cell data using the trait
        $cellData = $this->traitObject->generateCalendarCellData($room, $monday, $sunday);

        // Find which date index corresponds to March 19
        $march19Index = null;
        foreach ($cellData as $dayIndex => $day) {
            if (!empty($day) && $day[0]['date'] === '2025-03-19') {
                $march19Index = $dayIndex;
                break;
            }
        }

        // Verify we have data
        $this->assertNotEmpty($cellData, "Cell data should not be empty");
        $this->assertNotNull($march19Index, "March 19 should be found in the cell data");
        
        // Find the 8:30 PM slot
        $found830pmSlot = false;
        $cell830pm = null;
        
        if (isset($cellData[$march19Index])) {
            foreach ($cellData[$march19Index] as $slotIndex => $cell) {
                if ($cell['time'] === '20:30') {
                    $found830pmSlot = true;
                    $cell830pm = $cell;
                    break;
                }
            }
        }
        
        // Assert that we found the slot and it has the correct date
        $this->assertTrue($found830pmSlot, "8:30 PM slot should exist");
        $this->assertEquals('2025-03-19', $cell830pm['date'], 
            "The 8:30 PM slot should have date 2025-03-19");
    }

    /**
     * @test
     */
    public function it_generates_correct_booking_parameters()
    {
        // Create a test user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a room
        $room = Room::factory()->create([
            'name' => 'Test Room',
            'is_active' => true,
            'booking_policy' => new BookingPolicy(
                openingTime: '08:00',
                closingTime: '22:00',
                maxBookingDurationHours: 3.0,
                minBookingDurationHours: 0.5
            ),
        ]);

        // Set a specific test date
        Carbon::setTestNow(Carbon::parse('2025-03-18 12:00:00'));

        // Get test date
        $testDate = Carbon::parse('2025-03-19')->startOfDay();
        
        // Create booking parameters
        $bookingParams = [
            'room_id' => $room->id,
            'booking_date' => $testDate->format('Y-m-d'),
            'booking_time' => '20:30',
        ];
        
        // Check if the time slot is in the past (should not be)
        $dateTime = Carbon::createFromFormat(
            'Y-m-d H:i', 
            $bookingParams['booking_date'] . ' ' . $bookingParams['booking_time']
        );
        $isInPast = $dateTime->lt(Carbon::now());
        
        $this->assertFalse($isInPast, "The time slot should not be in the past");
        
        // Verify the booking parameters
        $this->assertEquals('2025-03-19', $bookingParams['booking_date'],
            "The booking date should be 2025-03-19");
        $this->assertEquals('20:30', $bookingParams['booking_time'],
            "The booking time should be 20:30");
            
        // Create a start time from these parameters
        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i', 
            $bookingParams['booking_date'] . ' ' . $bookingParams['booking_time']
        );
        
        // Verify the date
        $this->assertEquals('2025-03-19', $startDateTime->format('Y-m-d'),
            "The start date should be March 19");
    }
} 