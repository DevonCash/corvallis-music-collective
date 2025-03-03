<?php

namespace Tests\Unit;

use App\Modules\Payments\Contracts\Payable;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\Payments\Models\States\PaymentState\Paid;
use App\Modules\Payments\Models\States\PaymentState\Refunded;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_cash_payment()
    {
        // Mock dependencies
        $user = Mockery::mock(User::class);
        $user->id = 1;
        
        $mockProduct = Mockery::mock(\App\Modules\Payments\Models\Product::class);
        $mockProduct->id = 1;
        
        // Create an actual concrete class that implements Payable
        $payable = new TestPayable();
        $payable->id = 1;
        $payable->user_id = 1;
        $payable->product_id = 1;
        $payable->amount = 100.00;
        
        // Set up expectations
        $payable->shouldReceive('getUser')->andReturn($user);
        $payable->shouldReceive('getProduct')->andReturn($mockProduct);
        $payable->shouldReceive('getPayableDescription')->andReturn('Test payment');
        $payable->shouldReceive('getAmountOwed')->andReturn(100.00);
        
        // Mock logging
        Log::shouldReceive('info')->with(Mockery::pattern("/^Creating cash payment/"))->once();
        
        // Create the service
        $service = new PaymentService();
        
        // Act
        $payment = $service->createCashPayment($payable);
        
        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(1, $payment->user_id);
        $this->assertEquals(1, $payment->product_id);
        $this->assertEquals(100.00, $payment->amount);
        $this->assertEquals('cash', $payment->method);
        $this->assertEquals(Paid::$name, $payment->state);
    }
    
    /** @test */
    public function it_handles_exceptions_when_creating_cash_payment()
    {
        // Create a payable that will throw an exception
        $payable = new TestPayable();
        $payable->id = 2;
        
        // This will cause an exception when the service tries to access user
        $payable->shouldReceive('getUser')->andThrow(new \Exception('Test exception'));
        
        // Expect error logging
        Log::shouldReceive('error')->with(Mockery::pattern("/^Failed to create cash payment/"))->once();
        
        // Create the service
        $service = new PaymentService();
        
        // Act
        $payment = $service->createCashPayment($payable);
        
        // Assert null is returned on error
        $this->assertNull($payment);
    }
    
    /** @test */
    public function it_can_refund_a_payment()
    {
        // Create a test payable with some payments
        $payable = new TestPayable();
        $payable->id = 3;
        
        // Mock the payments relationship and query builder
        $mockQuery = Mockery::mock('query');
        $mockQuery->shouldReceive('where')->with('state', 'paid')->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(new Collection([
            $this->createMockPayment(1, Paid::$name),
            $this->createMockPayment(2, Paid::$name)
        ]));
        
        $mockRelation = Mockery::mock(MorphMany::class);
        $mockRelation->shouldReceive('__call')->with('where', ['state', 'paid'])->andReturn($mockQuery);
        
        // Setup the payments method to return our collection
        $payable->shouldReceive('payments')->andReturn($mockRelation);
        
        // Expect logging for each payment
        Log::shouldReceive('info')->with(Mockery::pattern("/^Payment 1 for/"))->once();
        Log::shouldReceive('info')->with(Mockery::pattern("/^Payment 2 for/"))->once();
        
        // Create a partial mock service that stubs the protected method
        $service = Mockery::mock(PaymentService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('processStripeRefund')->twice()->andReturn(true);
        
        // Act
        $refundedIds = $service->refundPayable($payable);
        
        // Assert
        $this->assertEquals([1, 2], $refundedIds);
    }
    
    /** @test */
    public function it_handles_stripe_refund_errors()
    {
        // Create a payment to refund
        $payment = $this->createMockPayment(3, Paid::$name);
        $payment->stripe_payment_intent_id = 'pi_test123';
        
        // Create a payable with the payment
        $payable = new TestPayable();
        $payable->id = 4;
        
        // Mock the payments relationship and query builder
        $mockQuery = Mockery::mock('query');
        $mockQuery->shouldReceive('where')->with('state', 'paid')->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(new Collection([$payment]));
        
        $mockRelation = Mockery::mock(MorphMany::class);
        $mockRelation->shouldReceive('__call')->with('where', ['state', 'paid'])->andReturn($mockQuery);
        
        // Setup the payments method to return our collection
        $payable->shouldReceive('payments')->andReturn($mockRelation);
        
        // Expect logging
        Log::shouldReceive('info')->with(Mockery::pattern("/^Payment 3 for/"))->once();
        Log::shouldReceive('error')->with(Mockery::pattern("/^Failed to process Stripe refund/"))->once();
        
        // Create a partial mock service that throws an exception during processStripeRefund
        $service = Mockery::mock(PaymentService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('processStripeRefund')
            ->with($payment)
            ->andThrow(new \Exception('Stripe API error'));
        
        // Act
        $refundedIds = $service->refundPayable($payable);
        
        // Assert - payment should still be marked as refunded even if Stripe fails
        $this->assertEquals([3], $refundedIds);
        $this->assertEquals(Refunded::$name, $payment->state);
    }
    
    /** @test */
    public function it_returns_empty_array_when_no_paid_payments_exist()
    {
        // Create a payable with no payments
        $payable = new TestPayable();
        $payable->id = 5;
        
        // Mock the payments relationship and query builder
        $mockQuery = Mockery::mock('query');
        $mockQuery->shouldReceive('where')->with('state', 'paid')->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(new Collection());
        
        $mockRelation = Mockery::mock(MorphMany::class);
        $mockRelation->shouldReceive('__call')->with('where', ['state', 'paid'])->andReturn($mockQuery);
        
        // Setup the payments method to return our collection
        $payable->shouldReceive('payments')->andReturn($mockRelation);
        
        // Create the service
        $service = new PaymentService();
        
        // Act
        $refundedIds = $service->refundPayable($payable);
        
        // Assert
        $this->assertEmpty($refundedIds);
    }
    
    /**
     * Helper to create a mock payment
     */
    private function createMockPayment($id, $state)
    {
        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = $id;
        $payment->state = $state;
        $payment->shouldReceive('save')->andReturn(true);
        
        return $payment;
    }
}
/**
 * A simple implementation of Payable for testing
 */
class TestPayable extends Mockery\Mock implements Payable
{
    public $id;
    public $user_id;
    public $product_id;
    public $amount;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function payments(): MorphMany
    {
        // This should be mocked in the test
        return Mockery::mock(MorphMany::class);
    }
    
    public function getTotalPaidAmount(): float
    {
        return 0;
    }
    
    public function getAmountOwed(): float
    {
        return $this->amount ?? 0;
    }
    
    public function isPaid(): bool
    {
        return false;
    }
    
    public function getPayableAmount(): float
    {
        return $this->amount ?? 0;
    }
    
    public function getPayableDescription(): string
    {
        return 'Test Payable Description';
    }
    
    public function getUser(): User
    {
        // This should be mocked in the test
        return Mockery::mock(User::class);
    }
    
    /**
     * Get the product associated with this payment
     * 
     * @return Product
     */
    public function getProduct(): \App\Modules\Payments\Models\Product
    {
        // This should be mocked in the test
        return Mockery::mock(\App\Modules\Payments\Models\Product::class);
    }
    
    public function createPayment(array $attributes): Payment
    {
        return new Payment($attributes);
    }
    
    public function getLineItems(): array
    {
        return [];
    }
    
    public function getCheckoutOptions(): array
    {
        return [];
    }
}

