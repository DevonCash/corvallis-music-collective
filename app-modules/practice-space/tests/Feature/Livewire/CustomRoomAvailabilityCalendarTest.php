<?php

namespace CorvMC\PracticeSpace\Tests\Feature\Livewire;

use App\Models\User;
use Carbon\Carbon;
use CorvMC\PracticeSpace\Livewire\CustomRoomAvailabilityCalendar;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * @test
 * @covers REQ-005
 */
class CustomRoomAvailabilityCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
            'capacity' => 5,
            'hourly_rate' => 25.00,
            'is_active' => true,
        ]);
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function it_displays_room_availability_calendar_with_hourly_resolution()
    {
        // Create a booking for the room
        $tomorrow = Carbon::tomorrow()->setHour(10)->setMinute(0);
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $tomorrow,
            'end_time' => $tomorrow->copy()->addHours(2),
            'state' => 'confirmed',
        ]);

        // Test the Livewire component
        $component = Livewire::test(CustomRoomAvailabilityCalendar::class)
            ->set('selectedRoom', $this->room->id);
        
        // Get the cell data from the component
        $cellData = $component->viewData('cellData');
        
        // Find the day index for tomorrow
        $dayIndex = Carbon::now()->startOfWeek(Carbon::MONDAY)->diffInDays($tomorrow->startOfDay());
        
        // Check that the booking is shown in the calendar
        // The booking should be from 10:00 to 12:00, so slots 10:00 and 11:00 should be booked
        $this->assertNotNull($cellData[$dayIndex][4]['booking_id'] ?? null); // Assuming 10:00 is the 5th slot (index 4)
        $this->assertNotNull($cellData[$dayIndex][5]['booking_id'] ?? null); // Assuming 11:00 is the 6th slot (index 5)
        
        // Check that slots before and after the booking are available
        $this->assertNull($cellData[$dayIndex][3]['booking_id'] ?? null); // Slot before booking
        $this->assertNull($cellData[$dayIndex][6]['booking_id'] ?? null); // Slot after booking
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function it_navigates_between_weeks_in_calendar()
    {
        $component = Livewire::test(CustomRoomAvailabilityCalendar::class)
            ->set('selectedRoom', $this->room->id);
        
        // Get the initial start date
        $initialStartDate = $component->get('startDate');
        
        // Navigate to next week
        $component->call('nextPeriod');
        
        // Check that the start date has moved forward by 7 days
        $this->assertEquals(
            Carbon::parse($initialStartDate)->addWeek()->format('Y-m-d'),
            Carbon::parse($component->get('startDate'))->format('Y-m-d')
        );
        
        // Navigate back to current week
        $component->call('previousPeriod');
        
        // Check that we're back to the initial start date
        $this->assertEquals(
            Carbon::parse($initialStartDate)->format('Y-m-d'),
            Carbon::parse($component->get('startDate'))->format('Y-m-d')
        );
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function it_shows_hourly_time_slots_in_calendar()
    {
        $component = Livewire::test(CustomRoomAvailabilityCalendar::class)
            ->set('selectedRoom', $this->room->id);
        
        // Get the cell data from the component
        $cellData = $component->viewData('cellData');
        
        // Check that we have data for 7 days (week view)
        $this->assertCount(7, $cellData);
        
        // Check that each day has hourly time slots
        foreach ($cellData as $dayIndex => $daySlots) {
            // Check that we have multiple time slots for the day
            $this->assertGreaterThan(5, count($daySlots));
            
            // Check that the time slots are in HH:MM format
            foreach ($daySlots as $slotIndex => $slot) {
                $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $slot['time']);
                
                // If this isn't the last slot of the day, check that the next slot is 30 minutes later
                if (isset($daySlots[$slotIndex + 1])) {
                    $currentTime = Carbon::createFromFormat('H:i', $slot['time']);
                    $nextTime = Carbon::createFromFormat('H:i', $daySlots[$slotIndex + 1]['time']);
                    
                    $this->assertEquals(30, $currentTime->diffInMinutes($nextTime));
                }
            }
        }
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function it_marks_past_time_slots_as_invalid()
    {
        $component = Livewire::test(CustomRoomAvailabilityCalendar::class)
            ->set('selectedRoom', $this->room->id);
        
        // Get the cell data from the component
        $cellData = $component->viewData('cellData');
        
        // Today's date
        $today = Carbon::today();
        
        // Find the day index for today
        $dayIndex = Carbon::now()->startOfWeek(Carbon::MONDAY)->diffInDays($today);
        
        // Current hour
        $currentHour = Carbon::now()->hour;
        
        // Check that time slots before the current hour are marked as invalid
        foreach ($cellData[$dayIndex] as $slot) {
            $slotHour = (int)substr($slot['time'], 0, 2);
            
            if ($slotHour < $currentHour) {
                $this->assertTrue($slot['invalid_duration'], "Past time slot {$slot['time']} should be marked as invalid");
            }
        }
    }

    /**
     * @test
     * @covers REQ-005
     */
    public function it_shows_room_details_in_calendar()
    {
        $component = Livewire::test(CustomRoomAvailabilityCalendar::class)
            ->set('selectedRoom', $this->room->id);
        
        // Get the room details from the component
        $roomDetails = $component->viewData('currentRoomDetails');
        
        // Check that the room details are correct
        $this->assertEquals($this->room->id, $roomDetails['id']);
        $this->assertEquals($this->room->name, $roomDetails['name']);
        $this->assertEquals($this->room->capacity, $roomDetails['capacity']);
        $this->assertEquals($this->room->hourly_rate, $roomDetails['hourly_rate']);
    }
} 