<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use CorvMC\PracticeSpace\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Stripe\StripeClient;

class UserMembershipTierTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_returns_radio_tier_for_users_without_subscription()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Mock the getMembershipAttribute method to return null
        $this->partialMock(User::class, function ($mock) use ($user) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn($user);
            
            $user->shouldReceive('getMembershipAttribute')
                ->andReturn(null);
        });
        
        // Assert that the user has Radio tier
        $this->assertEquals('Radio', $user->membership_tier);
    }
    
    /** @test */
    public function it_returns_cd_tier_for_users_with_cd_subscription()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a mock Stripe subscription with CD tier product
        $subscription = $this->createMockSubscription('prod_cd_tier', 'price_cd_monthly');
        
        // Mock the getMembershipAttribute method to return the subscription
        $this->partialMock(User::class, function ($mock) use ($user, $subscription) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn($user);
            
            $user->shouldReceive('getMembershipAttribute')
                ->andReturn($subscription);
        });
        
        // Assert that the user has CD tier
        $this->assertEquals('CD', $user->membership_tier);
    }
    
    /** @test */
    public function it_returns_vinyl_tier_for_users_with_vinyl_subscription()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a mock Stripe subscription with Vinyl tier product
        $subscription = $this->createMockSubscription('prod_vinyl_tier', 'price_vinyl_monthly');
        
        // Mock the getMembershipAttribute method to return the subscription
        $this->partialMock(User::class, function ($mock) use ($user, $subscription) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn($user);
            
            $user->shouldReceive('getMembershipAttribute')
                ->andReturn($subscription);
        });
        
        // Assert that the user has Vinyl tier
        $this->assertEquals('Vinyl', $user->membership_tier);
    }
    
    /** @test */
    public function it_returns_radio_tier_for_users_with_unknown_product()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a mock Stripe subscription with unknown product
        $subscription = $this->createMockSubscription('prod_unknown', 'price_unknown');
        
        // Mock the getMembershipAttribute method to return the subscription
        $this->partialMock(User::class, function ($mock) use ($user, $subscription) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn($user);
            
            $user->shouldReceive('getMembershipAttribute')
                ->andReturn($subscription);
        });
        
        // Assert that the user has Radio tier (default)
        $this->assertEquals('Radio', $user->membership_tier);
    }
    
    /** @test */
    public function it_returns_radio_tier_for_users_with_empty_subscription_items()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a mock Stripe subscription with empty items
        $subscription = (object) [
            'id' => 'sub_123456',
            'status' => 'active',
            'items' => (object) [
                'data' => []
            ]
        ];
        
        // Mock the getMembershipAttribute method to return the subscription
        $this->partialMock(User::class, function ($mock) use ($user, $subscription) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn($user);
            
            $user->shouldReceive('getMembershipAttribute')
                ->andReturn($subscription);
        });
        
        // Assert that the user has Radio tier (default)
        $this->assertEquals('Radio', $user->membership_tier);
    }
    
    /** @test */
    public function it_prioritizes_vinyl_tier_over_cd_tier_when_both_are_present()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a mock Stripe subscription with both CD and Vinyl tier products
        $subscription = (object) [
            'id' => 'sub_123456',
            'status' => 'active',
            'items' => (object) [
                'data' => [
                    (object) [
                        'price' => (object) [
                            'id' => 'price_cd_monthly',
                            'product' => 'prod_cd_tier'
                        ]
                    ],
                    (object) [
                        'price' => (object) [
                            'id' => 'price_vinyl_monthly',
                            'product' => 'prod_vinyl_tier'
                        ]
                    ]
                ]
            ]
        ];
        
        // Mock the getMembershipAttribute method to return the subscription
        $this->partialMock(User::class, function ($mock) use ($user, $subscription) {
            $mock->shouldReceive('find')
                ->with($user->id)
                ->andReturn($user);
            
            $user->shouldReceive('getMembershipAttribute')
                ->andReturn($subscription);
        });
        
        // Assert that the user has Vinyl tier (higher tier takes precedence)
        $this->assertEquals('Vinyl', $user->membership_tier);
    }
    
    /**
     * Create a mock Stripe subscription with the given product and price IDs
     */
    private function createMockSubscription(string $productId, string $priceId): object
    {
        return (object) [
            'id' => 'sub_123456',
            'status' => 'active',
            'items' => (object) [
                'data' => [
                    (object) [
                        'price' => (object) [
                            'id' => $priceId,
                            'product' => $productId
                        ]
                    ]
                ]
            ]
        ];
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
 