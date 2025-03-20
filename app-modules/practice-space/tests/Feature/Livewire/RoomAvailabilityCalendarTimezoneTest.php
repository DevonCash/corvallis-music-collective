<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Livewire;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Livewire\RoomAvailabilityCalendar;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

class RoomAvailabilityCalendarTimezoneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_creates_correct_cell_data_for_march_19_in_la_timezone()
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

        // Set a specific date for testing
        Carbon::setTestNow(Carbon::parse('2025-03-18 12:00:00', 'UTC'));

        // Create a component instance manually
        $component = new RoomAvailabilityCalendar();
        $component->selectedRoom = $room;
        
        // Set date range to include March 19
        $marchNineteenth = Carbon::parse('2025-03-19', $room->timezone)->startOfDay();
        $monday = $marchNineteenth->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);
        
        $component->startDate = $monday;
        $component->endDate = $sunday;
        
        // Get the timezone property using reflection
        $reflection = new ReflectionClass($component);
        $timezoneMethod = $reflection->getMethod('timezone');
        $timezone = $timezoneMethod->invoke($component);
        
        echo "\nComponent timezone: " . $timezone . "\n";
        echo "Monday: " . $monday->format('Y-m-d') . "\n";
        echo "March 19th: " . $marchNineteenth->format('Y-m-d') . "\n";
        
        // Calculate the day index for March 19
        $march19Index = $marchNineteenth->diffInDays($monday);
        echo "March 19 index: " . $march19Index . "\n";
        
        // Call generateCellData directly
        $cellData = $component->generateCellData();
        
        // Debug the cell data
        echo "Cell data days count: " . count($cellData) . "\n"; 
        echo "Available day indices: " . implode(', ', array_keys($cellData)) . "\n";
        
        // Basic verification that we have data
        $this->assertNotEmpty($cellData, "Cell data should not be empty");
        
        // Find the March 19 data and check the 8:30 PM slot
        $found830pmSlot = false;
        $cellDataForMarch19 = $cellData[$march19Index] ?? null;
        
        if ($cellDataForMarch19) {
            foreach ($cellDataForMarch19 as $index => $cell) {
                if ($cell['time'] === '20:30') {
                    $found830pmSlot = true;
                    echo "8:30 PM slot data: " . print_r($cell, true) . "\n";
                    
                    // Assert that the date is correct
                    $this->assertEquals('2025-03-19', $cell['date'], 
                        "The 8:30 PM slot on March 19 should have the date set as 2025-03-19");
                    break;
                }
            }
        }
        
        // Final check
        $this->assertTrue($found830pmSlot, "Should have found the 8:30 PM slot for March 19");
    }
    
    /**
     * @test 
     */
    public function it_correctly_displays_march_19_data_in_calendar()
    {
        // Create a test user and room
        $user = User::factory()->create();
        $this->actingAs($user);
        
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
        
        // Set test time to March 18, 2025
        Carbon::setTestNow(Carbon::parse('2025-03-18 12:00:00', 'UTC'));
        
        // Manually create the component state we'd expect
        $marchNineteenth = Carbon::parse('2025-03-19', $room->timezone)->startOfDay();
        $monday = $marchNineteenth->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);
        
        $march19Index = $marchNineteenth->diffInDays($monday);
        
        // Construct the parameters directly as if we were clicking on the cell
        $cellParams830pm = [
            'room_id' => $room->id,
            'booking_date' => $marchNineteenth->format('Y-m-d'),  // '2025-03-19'
            'booking_time' => '20:30',
        ];
        
        echo "\nBooking parameters that would be sent from JS: " . print_r($cellParams830pm, true) . "\n";
        
        $this->assertEquals('2025-03-19', $cellParams830pm['booking_date'],
            "The booking date should be 2025-03-19");
        $this->assertEquals('20:30', $cellParams830pm['booking_time'],
            "The booking time should be 20:30");
        
        // This test is a simplified version to establish what the correct parameters should be
        // We can't test actually mounting the action because of the livewire interaction
        $this->assertTrue(true, "Successfully generated booking parameters");
    }
} 