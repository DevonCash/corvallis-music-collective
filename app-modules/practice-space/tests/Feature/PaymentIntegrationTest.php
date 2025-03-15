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

    /**
     * @test
     * @covers INT-001
     */
    public function booking_uses_has_payments_trait()
    {
        $booking = new Booking();
        $this->assertTrue(method_exists($booking, 'payments'));
        $this->assertTrue(method_exists($booking, 'createPayment'));
    }

    /**
     * @test
     * @covers INT-001
     */
    public function booking_can_create_payment()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'scheduled',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
            'user_id' => $this->testUser->id,
        ]);
        
        // Assert that the payment was created
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(50.00, $payment->amount);
        $this->assertEquals($this->testUser->id, $payment->user_id);
        $this->assertEquals('Payment for booking #' . $booking->id, $payment->description);
        $this->assertEquals($booking->id, $payment->payable_id);
        $this->assertEquals(Booking::class, $payment->payable_type);
    }

    /**
     * @test
     * @covers INT-001
     */
    public function booking_can_retrieve_payments()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'scheduled',
        ]);
        
        // Create multiple payments for the booking
        $payment1 = $booking->createPayment([
            'amount' => 25.00,
            'description' => 'Deposit for booking #' . $booking->id,
            'user_id' => $this->testUser->id,
        ]);
        
        $payment2 = $booking->createPayment([
            'amount' => 25.00,
            'description' => 'Balance for booking #' . $booking->id,
            'user_id' => $this->testUser->id,
        ]);
        
        // Retrieve payments for the booking
        $payments = $booking->payments;
        
        // Assert that the payments were retrieved
        $this->assertCount(2, $payments);
        $this->assertTrue($payments->contains($payment1));
        $this->assertTrue($payments->contains($payment2));
    }

    /**
     * @test
     * @covers INT-001
     */
    public function booking_payment_status_is_updated_when_payment_is_made()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'scheduled',
            'payment_status' => 'pending',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
            'user_id' => $this->testUser->id,
            'status' => 'pending',
        ]);
        
        // Assert that the booking payment status is still pending
        $this->assertEquals('pending', $booking->fresh()->payment_status);
        
        // Update the payment status to completed
        $payment->update(['status' => 'completed']);
        
        // Assert that the booking payment status is updated to paid
        $this->assertEquals('paid', $booking->fresh()->payment_status);
    }

    /**
     * @test
     * @covers INT-001
     * @covers REQ-007
     */
    public function booking_state_is_updated_when_payment_is_completed()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'scheduled',
            'payment_status' => 'pending',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
            'user_id' => $this->testUser->id,
            'status' => 'pending',
        ]);
        
        // Assert that the booking state is still scheduled
        $this->assertEquals('scheduled', $booking->fresh()->state);
        
        // Update the payment status to completed
        $payment->update(['status' => 'completed']);
        
        // Assert that the booking state is updated to confirmed
        $this->assertEquals('confirmed', $booking->fresh()->state);
    }

    /**
     * @test
     * @covers INT-001
     */
    public function booking_can_be_refunded()
    {
        // Create a booking
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        
        // Create a payment for the booking
        $payment = $booking->createPayment([
            'amount' => 50.00,
            'description' => 'Payment for booking #' . $booking->id,
            'user_id' => $this->testUser->id,
            'status' => 'completed',
        ]);
        
        // Cancel the booking with refund
        $booking->cancelWithRefund('Customer requested cancellation');
        
        // Assert that the booking state is updated to cancelled
        $this->assertEquals('cancelled', $booking->fresh()->state);
        
        // Assert that a refund payment was created
        $refund = $booking->payments()->where('type', 'refund')->first();
        $this->assertNotNull($refund);
        $this->assertEquals(-50.00, $refund->amount); // Negative amount for refund
        $this->assertEquals('completed', $refund->status);
        
        // Assert that the booking payment status is updated to refunded
        $this->assertEquals('refunded', $booking->fresh()->payment_status);
    }

    /**
     * @test
     * @covers REQ-017
     */
    public function booking_total_price_is_calculated_from_room_hourly_rate()
    {
        // Set the room hourly rate
        $this->room->update(['hourly_rate' => 25.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'scheduled',
        ]);
        
        // Assert that the booking total price is calculated correctly
        $this->assertEquals(50.00, $booking->calculateTotalPrice());
        
        // Create a booking for 3.5 hours
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(13),
            'end_time' => now()->addDay()->setHour(16)->addMinutes(30), // 3.5 hours
            'state' => 'scheduled',
        ]);
        
        // Assert that the booking total price is calculated correctly
        $this->assertEquals(87.50, $booking->calculateTotalPrice());
    }

    /**
     * @test
     * @covers REQ-017
     */
    public function booking_can_apply_discount()
    {
        // Set the room hourly rate
        $this->room->update(['hourly_rate' => 25.00]);
        
        // Create a booking for 2 hours
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(10),
            'end_time' => now()->addDay()->setHour(12), // 2 hours
            'state' => 'scheduled',
            'discount_percentage' => 10, // 10% discount
        ]);
        
        // Assert that the booking total price is calculated correctly with discount
        $this->assertEquals(45.00, $booking->calculateTotalPrice()); // 50.00 - 10% = 45.00
        
        // Create a booking with a different discount
        $booking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->testUser->id,
            'start_time' => now()->addDay()->setHour(13),
            'end_time' => now()->addDay()->setHour(16)->addMinutes(30), // 3.5 hours
            'state' => 'scheduled',
            'discount_percentage' => 20, // 20% discount
        ]);
        
        // Assert that the booking total price is calculated correctly with discount
        $this->assertEquals(70.00, $booking->calculateTotalPrice()); // 87.50 - 20% = 70.00
    }
} 