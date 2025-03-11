<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Facades\Log;

class BookingMembershipDiscountTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_applies_no_discount_for_radio_tier_members()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 60.00 // Set initial price
        ]);
        
        // Create a mock user with Radio tier
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('Radio');
        
        // Apply discount with mocked user
        $booking->applyMembershipDiscount($mockUser);
        
        // Assert that no discount was applied
        $this->assertEquals(60.00, $booking->total_price);
    }
    
    /** @test */
    public function it_applies_25_percent_discount_for_cd_tier_members()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 60.00 // Set initial price
        ]);
        
        // Create a mock user with CD tier
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('CD');
        
        // Apply discount with mocked user
        $booking->applyMembershipDiscount($mockUser);
        
        // Assert that 25% discount was applied (60 - 25% = 45)
        $this->assertEquals(45.00, $booking->total_price);
    }
    
    /** @test */
    public function it_applies_50_percent_discount_for_vinyl_tier_members()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 60.00 // Set initial price
        ]);
        
        // Create a mock user with Vinyl tier
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('Vinyl');
        
        // Apply discount with mocked user
        $booking->applyMembershipDiscount($mockUser);
        
        // Assert that 50% discount was applied (60 - 50% = 30)
        $this->assertEquals(30.00, $booking->total_price);
    }
    
    /** @test */
    public function it_recalculates_price_when_membership_changes()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Create a booking for 2 hours with no discount initially
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 60.00 // No discount initially
        ]);
        
        // Create a mock user with CD tier
        $mockUserCD = Mockery::mock(User::class);
        $mockUserCD->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('CD');
        
        // Recalculate the price with CD tier user
        $booking->recalculatePrice($mockUserCD);
        
        // Assert that 25% discount was applied (60 - 25% = 45)
        $this->assertEquals(45.00, $booking->total_price);
        
        // Create a mock user with Vinyl tier
        $mockUserVinyl = Mockery::mock(User::class);
        $mockUserVinyl->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('Vinyl');
        
        // Recalculate the price with Vinyl tier user
        $booking->recalculatePrice($mockUserVinyl);
        
        // Assert that 50% discount was applied (60 - 50% = 30)
        $this->assertEquals(30.00, $booking->total_price);
    }
    
    /** @test */
    public function it_automatically_applies_discount_during_booking_creation()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Mock User::find to return a user with CD tier
        $this->partialMock(User::class, function ($mock) use ($user) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn(tap(Mockery::mock(User::class), function ($mockUser) {
                    $mockUser->shouldReceive('getAttribute')
                        ->with('membership_tier')
                        ->andReturn('CD');
                }));
        });
        
        // Create a new booking - this should trigger the boot method
        $booking = new Booking([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
        ]);
        
        // Save the booking to trigger the creating event
        $booking->save();
        
        // Refresh the model from the database
        $booking->refresh();
        
        // Assert that 25% discount was automatically applied (60 - 25% = 45)
        $this->assertEquals(45.00, $booking->total_price);
    }
    
    /** @test */
    public function it_handles_errors_gracefully_when_applying_discount()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 60.00 // Set initial price
        ]);
        
        // Create a mock user that throws an exception when accessing membership_tier
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andThrow(new \Exception('Test exception'));
        
        // Mock the Log facade to verify it's called
        Log::shouldReceive('error')
            ->once()
            ->with(Mockery::pattern('/Error applying membership discount: .*/'));
        
        // Apply discount with mocked user - this should catch the exception
        $result = $booking->applyMembershipDiscount($mockUser);
        
        // Assert that the method returns $this even when an exception occurs
        $this->assertSame($booking, $result);
        
        // Assert that the price remains unchanged
        $this->assertEquals(60.00, $booking->total_price);
    }
    
    /** @test */
    public function it_handles_edge_cases_with_zero_price()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 0.00]);
        
        // Create a booking for 2 hours with zero price
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 0.00
        ]);
        
        // Create a mock user with CD tier
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('CD');
        
        // Apply discount with mocked user
        $booking->applyMembershipDiscount($mockUser);
        
        // Assert that the price remains zero
        $this->assertEquals(0.00, $booking->total_price);
    }
    
    /** @test */
    public function it_handles_invalid_membership_tier()
    {
        // Create a real user
        $user = User::factory()->create();
        
        // Create a room with hourly rate
        $room = Room::factory()->create(['hourly_rate' => 30.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHours(2),
            'total_price' => 60.00 // Set initial price
        ]);
        
        // Create a mock user with an invalid tier
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('getAttribute')
            ->with('membership_tier')
            ->andReturn('InvalidTier');
        
        // Apply discount with mocked user
        $booking->applyMembershipDiscount($mockUser);
        
        // Assert that no discount was applied for invalid tier
        $this->assertEquals(60.00, $booking->total_price);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 