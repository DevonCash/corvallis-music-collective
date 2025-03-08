<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use CorvMC\Finance\Models\Payment;
use CorvMC\Finance\Models\Product;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser;
    protected $room;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip these tests if the Finance module is not available
        if (!class_exists(Payment::class) || !class_exists(Product::class)) {
            $this->markTestSkipped('Finance module is not available');
        }
        
        // Create a user that will be used throughout the tests
        $this->testUser = User::factory()->create([
            'email' => 'test-payment-integration@example.com',
            'name' => 'Test Payment Integration User',
        ]);
        
        // Create a room
        $this->room = Room::factory()->create();
        
        // Create a product for the room
        $this->product = Product::factory()->create([
            'name' => 'Practice Room Booking',
            'description' => 'Booking for practice room',
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        // Associate the product with the room
        $this->room->update(['product_id' => $this->product->id]);
    }

    /** @test */
    public function booking_uses_has_payments_trait()
    {
        // Check if the Booking model uses the HasPayments trait
        $booking = new Booking();
        $this->assertTrue(method_exists($booking, 'payments'), 'Booking model should have a payments method from HasPayments trait');
    }

    /** @test */
    public function booking_can_create_payment()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'total_price' => 50.00,
            'state' => 'scheduled',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
        ]);
        
        // Assert that the payment was created
        $this->assertNotNull($payment);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(50.00, $payment->amount);
        $this->assertEquals($booking->id, $payment->payable_id);
        $this->assertEquals(Booking::class, $payment->payable_type);
    }

    /** @test */
    public function booking_can_retrieve_payments()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'total_price' => 50.00,
            'state' => 'scheduled',
        ]);
        
        // Create multiple payments for the booking
        $payment1 = $booking->createPayment([
            'amount' => 25.00,
            'description' => 'Partial payment for booking #' . $booking->id,
        ]);
        
        $payment2 = $booking->createPayment([
            'amount' => 25.00,
            'description' => 'Final payment for booking #' . $booking->id,
        ]);
        
        // Retrieve payments
        $payments = $booking->payments;
        
        // Assert that the payments were retrieved
        $this->assertCount(2, $payments);
        $this->assertEquals(25.00, $payments[0]->amount);
        $this->assertEquals(25.00, $payments[1]->amount);
    }

    /** @test */
    public function booking_payment_status_is_updated_when_payment_is_made()
    {
        // Create a booking with pending payment status
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'total_price' => 50.00,
            'payment_status' => 'pending',
            'state' => 'scheduled',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
            'status' => 'completed',
        ]);
        
        // Refresh the booking
        $booking->refresh();
        
        // Assert that the payment status was updated
        $this->assertEquals('paid', $booking->payment_status);
    }

    /** @test */
    public function booking_state_is_updated_when_payment_is_completed()
    {
        // Create a booking with scheduled state
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'total_price' => 50.00,
            'payment_status' => 'pending',
            'state' => 'scheduled',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
        ]);
        
        // Complete the payment
        $payment->markAsCompleted();
        
        // Refresh the booking
        $booking->refresh();
        
        // Assert that the booking state was updated to confirmed
        $this->assertEquals('confirmed', $booking->state);
    }

    /** @test */
    public function booking_can_be_refunded()
    {
        // Create a booking with confirmed state and paid payment status
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12),
            'total_price' => 50.00,
            'payment_status' => 'paid',
            'state' => 'confirmed',
        ]);
        
        // Create a completed payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
            'status' => 'completed',
        ]);
        
        // Refund the payment
        $refund = $booking->refund([
            'amount' => 50.00,
            'reason' => 'Customer requested cancellation',
        ]);
        
        // Refresh the booking
        $booking->refresh();
        
        // Assert that the refund was created
        $this->assertNotNull($refund);
        $this->assertEquals(50.00, $refund->amount);
        
        // Assert that the booking state and payment status were updated
        $this->assertEquals('cancelled', $booking->state);
        $this->assertEquals('refunded', $booking->payment_status);
    }

    /** @test */
    public function booking_total_price_is_calculated_from_room_hourly_rate()
    {
        // Create a booking without specifying the total price
        $startTime = now()->addDay()->setHour(10);
        $endTime = now()->addDay()->setHour(13); // 3 hours
        
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'scheduled',
        ]);
        
        // Calculate expected price (hourly rate * hours)
        $expectedPrice = $this->room->hourly_rate * 3;
        
        // Assert that the total price was calculated correctly
        $this->assertEquals($expectedPrice, $booking->total_price);
    }

    /** @test */
    public function booking_can_apply_discount()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'user_id' => $this->testUser->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(13), // 3 hours
            'total_price' => 75.00, // Assuming $25/hour
            'state' => 'scheduled',
        ]);
        
        // Apply a discount
        $booking->applyDiscount(20, 'Member discount');
        
        // Refresh the booking
        $booking->refresh();
        
        // Assert that the discount was applied
        $this->assertEquals(60.00, $booking->total_price); // 75 - 20% = 60
    }
} 