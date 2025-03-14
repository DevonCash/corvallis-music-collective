<?php

namespace CorvMC\Commerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;
use App\Models\User;

class SyncStripeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:sync-stripe {--user= : Sync a specific user by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually sync Cashier subscriptions with Stripe';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Stripe subscription sync...');
        
        $userId = $this->option('user');
        
        if ($userId) {
            $this->syncUser($userId);
        } else {
            $this->syncAllUsers();
        }
        
        $this->info('Stripe subscription sync completed!');
    }
    
    /**
     * Sync a specific user's subscription data
     */
    protected function syncUser(int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }
        
        $this->info("Syncing user: {$user->name} (ID: {$user->id})");
        
        try {
            $this->syncUserSubscriptions($user);
            $this->info("Successfully synced user {$user->id}");
        } catch (\Exception $e) {
            $this->error("Error syncing user {$user->id}: {$e->getMessage()}");
            Log::error("Stripe sync error for user {$user->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        }
    }
    
    /**
     * Sync all users' subscription data
     */
    protected function syncAllUsers(): void
    {
        $users = User::whereNotNull('stripe_id')->get();
        
        $this->info("Found {$users->count()} users with Stripe IDs");
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            try {
                $this->syncUserSubscriptions($user);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Stripe sync error for user {$user->id}: {$e->getMessage()}", [
                    'exception' => $e,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Sync completed: {$successCount} successful, {$errorCount} failed");
    }
    
    /**
     * Sync an individual user's subscriptions with Stripe
     */
    protected function syncUserSubscriptions(User $user): void
    {
        $stripe = app(StripeClient::class);
        
        // Get the user's subscriptions from Stripe
        $stripeSubscriptions = $stripe->subscriptions->all([
            'customer' => $user->stripe_id,
            'status' => 'all',
        ]);
        
        foreach ($stripeSubscriptions->data as $stripeSubscription) {
            // Find or create the local subscription record
            $subscription = $user->subscriptions()
                ->firstOrNew(['stripe_id' => $stripeSubscription->id]);
            
            // Update subscription details
            $subscription->type = 'default'; // Or determine from metadata if needed
            $subscription->stripe_status = $stripeSubscription->status;
            
            // Get the first price ID (main subscription price)
            if (!empty($stripeSubscription->items->data)) {
                $subscription->stripe_price = $stripeSubscription->items->data[0]->price->id;
                $subscription->quantity = $stripeSubscription->items->data[0]->quantity ?? 1;
            }
            
            $subscription->trial_ends_at = $stripeSubscription->trial_end 
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) 
                : null;
            $subscription->ends_at = $stripeSubscription->cancel_at_period_end
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)
                : null;
            
            $subscription->save();
            
            // Sync subscription items
            $this->syncSubscriptionItems($subscription, $stripeSubscription);
            
            // Verbose output if not in progress bar mode
            if (!$this->output->isDecorated()) {
                $this->line("Updated subscription {$subscription->stripe_id} for user {$user->id}");
            }
        }
    }
    
    /**
     * Sync subscription items for a subscription
     */
    protected function syncSubscriptionItems($subscription, $stripeSubscription): void
    {
        // Get existing subscription items to track which ones to keep
        $existingItemIds = $subscription->items()->pluck('stripe_id')->toArray();
        $updatedItemIds = [];
        
        foreach ($stripeSubscription->items->data as $stripeItem) {
            // Find or create the subscription item
            $item = $subscription->items()->firstOrNew(['stripe_id' => $stripeItem->id]);
            
            // Update item details
            $item->stripe_product = $stripeItem->price->product;
            $item->stripe_price = $stripeItem->price->id;
            $item->quantity = $stripeItem->quantity ?? 1;
            
            $item->save();
            
            $updatedItemIds[] = $stripeItem->id;
        }
        
        // Remove any items that no longer exist in Stripe
        $itemsToRemove = array_diff($existingItemIds, $updatedItemIds);
        if (!empty($itemsToRemove)) {
            $subscription->items()->whereIn('stripe_id', $itemsToRemove)->delete();
        }
    }
} 