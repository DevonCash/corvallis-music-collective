<?php

namespace CorvMC\PracticeSpace\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Laravel\Cashier\Events\WebhookReceived;

class StripeSubscriptionUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'customer.subscription.updated') {
            return;
        }
        
        // Get the customer ID from the event
        $customerId = $event->payload['data']['object']['customer'] ?? null;
        
        if (!$customerId) {
            return;
        }
        
        // Find the user with this Stripe customer ID
        $user = User::where('stripe_id', $customerId)->first();
        
        if (!$user) {
            return;
        }
        
        // Clear the membership cache to ensure we get fresh data
        \Illuminate\Support\Facades\Cache::forget("user_{$user->id}_membership");
        
        // Recalculate prices for this user's future bookings
        Artisan::call('practice-space:recalculate-prices', [
            'user_id' => $user->id
        ]);
    }
} 