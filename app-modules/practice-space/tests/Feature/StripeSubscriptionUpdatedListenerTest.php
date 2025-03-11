<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Tests\TestCase;
use CorvMC\PracticeSpace\Listeners\StripeSubscriptionUpdatedListener;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Events\WebhookReceived;
use Mockery;

class StripeSubscriptionUpdatedListenerTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_recalculates_prices_when_subscription_is_updated()
    {
        // Create a user with a Stripe ID
        $user = User::factory()->create([
            'stripe_id' => 'cus_test123456'
        ]);
        
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
        
        // Mock the User::where->first method to return our user
        $this->partialMock(User::class, function ($mock) use ($user) {
            $mock->shouldReceive('where')
                ->with('stripe_id', 'cus_test123456')
                ->andReturnSelf();
            $mock->shouldReceive('first')
                ->andReturn($user);
        });
        
        // Mock the Cache::forget method to verify it's called
        Cache::shouldReceive('forget')
            ->once()
            ->with("user_{$user->id}_membership")
            ->andReturn(true);
        
        // Mock the Artisan::call method to verify it's called with the right parameters
        Artisan::shouldReceive('call')
            ->once()
            ->with('practice-space:recalculate-prices', ['user_id' => $user->id])
            ->andReturn(0);
        
        // Create a webhook event payload for subscription update
        $payload = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'customer' => 'cus_test123456',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_cd_monthly',
                                    'product' => 'prod_cd_tier'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Create the webhook event
        $event = new WebhookReceived($payload);
        
        // Create the listener
        $listener = new StripeSubscriptionUpdatedListener();
        
        // Handle the event
        $listener->handle($event);
        
        // Add an explicit assertion to avoid "risky" test warning
        $this->assertTrue(true, 'The listener processed the event without errors');
    }
    
    /** @test */
    public function it_ignores_non_subscription_update_events()
    {
        // Create a webhook event payload for a different event type
        $payload = [
            'type' => 'customer.created',
            'data' => [
                'object' => [
                    'customer' => 'cus_test123456'
                ]
            ]
        ];
        
        // Create the webhook event
        $event = new WebhookReceived($payload);
        
        // Mock the Artisan::call method to verify it's not called
        Artisan::shouldReceive('call')
            ->never();
        
        // Create the listener
        $listener = new StripeSubscriptionUpdatedListener();
        
        // Handle the event
        $listener->handle($event);
        
        // Add an explicit assertion to avoid "risky" test warning
        $this->assertTrue(true, 'The listener correctly ignored non-subscription events');
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 