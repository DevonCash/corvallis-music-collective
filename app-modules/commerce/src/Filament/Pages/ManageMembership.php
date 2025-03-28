<?php

namespace CorvMC\Commerce\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Actions\Action as FormAction;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;

class ManageMembership extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'commerce::filament.pages.manage-membership';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'membership';

    protected ?Collection $tiers = null;
    
    // Cache settings
    protected const MEMBERSHIP_TIERS_CACHE_KEY = 'membership_tiers';
    protected const MEMBERSHIP_TIERS_CACHE_TTL = 86400; // 24 hours
    protected const CHECKOUT_URL_CACHE_KEY = 'checkout_url_';
    protected const CHECKOUT_URL_CACHE_TTL = 3600; // 1 hour

    public array $data = [];
    
    public Collection $monthlyTiers;
    public Collection $yearlyTiers;

    // Store subscription in property to avoid repeated calls
    protected $currentSubscription = null;
    protected $currentTier;
    protected $checkoutUrls = [];

    /**
     * Property to store the Stripe subscription data (expensive to load)
     */
    protected $stripeSubscription = null;
    

    #[Computed]
    public function currentTier()
    {
        return $this->currentSubscription && $this->getMembershipTiers()->get($this->currentSubscription?->stripe_price);
    }

    /**
     * Get the current subscription (use only when needed)
     * 
     * @return mixed The subscription model or null if not found
     */
    protected function getCurrentSubscription()
    {
        if ($this->currentSubscription === null) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // First check if user has a subscription before trying to use with()
            $hasSubscription = $user->subscribed('default');
            
            if ($hasSubscription) {
                // Only try to eager load if subscription exists
                $this->currentSubscription = $user->subscription('default')
                    ->with('items') // Eager load subscription items to reduce queries
                    ->first();
            } else {
                // Mark as checked but not found
                $this->currentSubscription = null;
            }
        }
        
        return $this->currentSubscription;
    }
    
    /**
     * Get the current subscription from Stripe (lazy loaded)
     */
    protected function getStripeSubscription()
    {
        // First make sure current subscription is loaded
        $subscription = $this->getCurrentSubscription();
        
        // Only make the expensive Stripe API call when absolutely necessary
        if ($this->stripeSubscription === null && $subscription && $subscription->stripe_status === 'active') {
            // Cache the Stripe subscription at the user level to avoid repeated API calls
            $cacheKey = 'stripe_subscription_' . $subscription->id;
            $this->stripeSubscription = Cache::remember($cacheKey, 300, function() use ($subscription) {
                return $subscription->asStripeSubscription();
            });
            
            // Also lazily load the current tier if needed
            if ($this->currentTier === null) {
                $this->currentTier = $this->getMembershipTiers()->get($subscription->stripe_price);
            }
        }
        
        return $this->stripeSubscription;
    }

    /**
     * Mount the component and handle status parameters
     */
    public function mount(): void
    {
        $this->handleStatusNotifications();
        $this->data = ['interval' => 'month'];
        
        // Pre-load the tiers for display (needed regardless of subscription status)
        $this->monthlyTiers = $this->getFilteredTiers('month');
        $this->yearlyTiers = $this->getFilteredTiers('year');
        
        // Preload the subscription in the background
        // This initiates the loading but doesn't wait for the result
        // The data will be available when needed later
        $this->prefetchSubscriptionData();
    }
    
    /**
     * Prefetch subscription data in the background to improve perceived performance
     */
    protected function prefetchSubscriptionData(): void
    {
        // Just call the getter, which will cache the result for later use
        // but don't use the return value here
        $this->getCurrentSubscription();
    }

    /**
     * Handle status notifications from redirects
     */
    protected function handleStatusNotifications(): void
    {
        switch(request()->query('status')) {
            case 'success':
            Notification::make()
                ->title('Success')
                ->body('Your subscription has been processed successfully!')
                ->success()
                ->send();
                break;
            case 'cancelled':
                Notification::make()
                ->title('Checkout Cancelled')
                ->body('Your subscription checkout was cancelled. You can try again anytime.')
                ->warning()
                ->send();
                break;
        }
    }

    /**
     * Get membership tiers from Stripe with caching
     * 
     * @return Collection Collection of tier information indexed by price ID
     */
    public function getMembershipTiers(): Collection
    {
        // Return existing tiers if already loaded
        if($this->tiers !== null) {
            return $this->tiers;
        }
        
        // Try to get from cache first
        $this->tiers = Cache::get(self::MEMBERSHIP_TIERS_CACHE_KEY);
        if ($this->tiers !== null) {
            return $this->tiers;
        }
        
        // If not in cache, fetch from Stripe and cache the results
        $this->tiers = Cache::remember(self::MEMBERSHIP_TIERS_CACHE_KEY, self::MEMBERSHIP_TIERS_CACHE_TTL, function () {
            $stripe = app(StripeClient::class);
            
            // Get all membership products in a single API call with an efficient query
            $products = collect($stripe->products->search([
                'query' => '-metadata["membership_tier"]:null',
                'limit' => 100,
            ])->data);
            
            if($products->count() <= 0) {
                return collect([]);
            }

            // Get all relevant price IDs from the products
            $productIds = $products->pluck('id')->toArray();
            
            $query = implode(' OR ', array_map(fn($id) => "product:\"{$id}\"", $productIds));
            
            // Make a single price search query instead of multiple calls
            $prices = collect($stripe->prices->search([
                'query' => $query,
                'limit' => 100,
            ])->data);
            
            // Pre-index products for O(1) lookups instead of O(n) searches
            $productsById = $products->keyBy('id');
            
            // Use optimized data extraction with minimal processing
            $tiers = $prices->map(function($price) use ($productsById) {
                $product = $productsById->get($price->product);
                if (!$product) return null;
                
                // Extract only needed data to reduce memory usage
                return [
                    'product_id' => $product->id,
                    'level' => (int) ($product->metadata->membership_tier ?? 0),
                    'name' => $product->name,
                    'description' => $product->description,
                    'features' => array_column($product->marketing_features ?? [], 'name'),
                    'current_price' => [
                        'id' => $price->id,
                        'unit_amount' => $price->unit_amount,
                        'recurring' => [
                            'interval' => $price->recurring->interval,
                        ],
                    ],
                ];
            })->filter()->keyBy('current_price.id');
            
            return $tiers;
        });
            
        return $this->tiers;
    }

    protected $filteredTiersByInterval = [];

    /**
     * Get membership tiers filtered by interval with memoization
     * 
     * @param string $interval The interval to filter by ('month' or 'year')
     * @return \Illuminate\Support\Collection
     */
    protected function getFilteredTiers(string $interval): Collection
    {
        // Return already calculated results if available (memoization)
        if (isset($this->filteredTiersByInterval[$interval])) {
            return $this->filteredTiersByInterval[$interval];
        }
        
        // Calculate, store, and return the filtered tiers
        $this->filteredTiersByInterval[$interval] = $this->getMembershipTiers()
            ->filter(fn($tier) => $tier['current_price']['recurring']['interval'] === $interval)
            ->sortBy('level')
            ->values();
        
        return $this->filteredTiersByInterval[$interval];
    }

    /**
     * Action to switch between monthly and yearly billing
     */
    public function switchIntervalAction(string $priceId): FormAction
    {
        // Get the necessary tier information - ensure subscription is initialized
        $subscription = $this->getCurrentSubscription();
        
        // Safety check - redirect to subscribe if no active subscription
        if (!$subscription || $subscription->stripe_status !== 'active') {
            return $this->subscribeAction($this->getMembershipTiers()->get($priceId));
        }
        
        $currentPriceId = $subscription->stripe_price;
        
        $tiers = $this->getMembershipTiers();
        $newPrice = $tiers->get($priceId);
        $currentPrice = $tiers->get($currentPriceId);
        $newInterval = $newPrice['current_price']['recurring']['interval'];
        
        // Calculate price difference
        $currentAmount = $currentPrice['current_price']['unit_amount'] / 100;
        $newAmount = $newPrice['current_price']['unit_amount'] / 100;
        $isUpgrade = $newAmount > $currentAmount;
        
        // Format price strings
        $currentPriceFormatted = '$' . number_format($currentAmount, 2) . ' / ' . 
            ($currentPrice['current_price']['recurring']['interval'] === 'month' ? 'month' : 'year');
        $newPriceFormatted = '$' . number_format($newAmount, 2) . ' / ' . 
            ($newInterval === 'month' ? 'month' : 'year');
            
        // Get next billing date if available
        $nextBillingDate = null;
        $stripeSubscription = $this->getStripeSubscription();
        if ($stripeSubscription) {
            $nextBillingDate = \Carbon\Carbon::parse($stripeSubscription->current_period_end);
        }
        
        // If we couldn't get the exact date, use a fallback message
        $billingDateMessage = $nextBillingDate 
            ? 'on ' . $nextBillingDate->format('M d, Y')
            : 'at the end of your current billing period';
        
        // Format intervals
        $currentIntervalFormatted = ucfirst($currentPrice['current_price']['recurring']['interval'] . 'ly');
        $newIntervalFormatted = ucfirst($newInterval . 'ly');
        
        return FormAction::make('switch_interval_'.$priceId)
            ->label('Switch to ' . ($newInterval === 'month' ? 'Monthly' : 'Yearly'))
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Switch to ' . ($newInterval === 'month' ? 'Monthly' : 'Yearly'))
            ->form([
                Placeholder::make('comparison_table')
                    ->label('Price Comparison')
                    ->content(<<<HTML
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="text-left py-2 px-4 border-b-2 border-gray-200"></th>
                                    <th class="text-left py-2 px-4 border-b-2 border-gray-200">Current Plan</th>
                                    <th class="text-left py-2 px-4 border-b-2 border-gray-200">New Plan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-2 px-4 border-b border-gray-100 font-medium">Price</td>
                                    <td class="py-2 px-4 border-b border-gray-100 font-bold">{$currentPriceFormatted}</td>
                                    <td class="py-2 px-4 border-b border-gray-100 font-bold text-primary-600">{$newPriceFormatted}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-4 border-b border-gray-100 font-medium">Billing Cycle</td>
                                    <td class="py-2 px-4 border-b border-gray-100 text-gray-600">{$currentIntervalFormatted}</td>
                                    <td class="py-2 px-4 border-b border-gray-100 text-gray-600">{$newIntervalFormatted}</td>
                                </tr>
                            </tbody>
                        </table>
                    HTML),
                
                Placeholder::make('billing_info_title')
                    ->content('Billing Information')
                    ->extraAttributes(['class' => 'text-lg font-medium mt-6 mb-2']),
                
                Placeholder::make('billing_info')
                    ->content(function() use ($isUpgrade, $billingDateMessage) {
                        if ($isUpgrade) {
                            return 'This is an upgrade. You will be charged immediately for the prorated amount, and your subscription will be updated right away.';
                        } else {
                            return 'This change will take effect ' . $billingDateMessage . '.';
                        }
                    })
                    ->extraAttributes([
                        'class' => $isUpgrade ? 'text-warning-600 p-2 bg-warning-50 rounded' : 'text-success-600 p-2 bg-success-50 rounded'
                    ]),
            ])
            ->action(function() use ($priceId, $isUpgrade) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                
                if ($isUpgrade) {
                    // For upgrades, swap immediately
                    $user->subscription('default')->swap($priceId);
                } else {
                    // For downgrades, swap at end of cycle
                    $user->subscription('default')->swapNextCycle($priceId);
                }
                
                // Clear caches after changing subscription
                $this->clearAllCaches();
            });
    }

    /**
     * Action to manage existing membership
     */
    public function manageMembershipAction(string $priceId): FormAction
    {
        return FormAction::make('manage_membership_'.$priceId)
            ->label('Manage')
            ->icon('heroicon-o-cog')
            ->size('sm')
            ->url(fn() => $this->getBillingPortalUrl());
    }

    /**
     * Action to cancel membership
     */
    public function cancelMembershipAction(): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $subscription = $this->getCurrentSubscription();
        
        // Safety check - redirect to home if no active subscription
        if (!$subscription || $subscription->stripe_status !== 'active') {
            return FormAction::make('no_subscription')
                ->label('No Subscription')
                ->disabled()
                ->tooltip('You don\'t have an active subscription to cancel');
        }
        
        // If we have an end date from the subscription, use that
        $endDate = $subscription->ends_at;
        
        // Otherwise, get from Stripe subscription if available
        if (!$endDate) {
            $stripeSubscription = $this->getStripeSubscription();
            if ($stripeSubscription) {
                $endDate = \Carbon\Carbon::parse($stripeSubscription->current_period_end);
            }
        }
        
        // Format the date for display
        $endDateMessage = $endDate 
            ? 'on ' . \Carbon\Carbon::parse($endDate)->format('M d, Y')
            : 'at the end of your billing period';
        
        return FormAction::make('cancel_membership')
            ->label('Switch to Free')
            ->icon('heroicon-o-arrow-down')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Switch to Free')
            ->size('sm')
            ->modalDescription("At the end of your billing period, your subscription will be cancelled and you will have access to the free tier. This goes into effect {$endDateMessage}.")
            ->action(function() use ($user) {
                $user->subscription('default')->cancel();
                
                // Clear caches after cancelling subscription
                $this->clearAllCaches();
            });
    }

    /**
     * Action to switch tier
     */
    public function switchTierAction(array $currentTier, array $newTier): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Safety check - redirect to subscribe if not valid tier change
        if (!$currentTier || !$newTier) {
            return FormAction::make('invalid_tier_change')
                ->label('Error')
                ->color('danger')
                ->disabled()
                ->tooltip('Cannot change tiers: invalid tier information');
        }
        
        // Calculate price difference
        $currentAmount = $currentTier['current_price']['unit_amount'] / 100;
        $newAmount = $newTier['current_price']['unit_amount'] / 100;
        $isUpgrade = $newAmount > $currentAmount;
        
        // Format price strings
        $currentPriceFormatted = '$' . number_format($currentAmount, 2) . ' / ' . 
            ($currentTier['current_price']['recurring']['interval'] === 'month' ? 'month' : 'year');
        $newPriceFormatted = '$' . number_format($newAmount, 2) . ' / ' . 
            ($newTier['current_price']['recurring']['interval'] === 'month' ? 'month' : 'year');
        $priceId = $newTier['current_price']['id'];

        // Get next billing date if available
        $nextBillingDate = null;
        $subscription = $this->getCurrentSubscription();
        
        // Only fetch stripe subscription if we have an active subscription
        if ($subscription && $subscription->stripe_status === 'active') {
            $stripeSubscription = $this->getStripeSubscription();
            if ($stripeSubscription) {
                $nextBillingDate = \Carbon\Carbon::parse($stripeSubscription->current_period_end);
            }
        }
        
        // If we couldn't get the exact date, use a fallback message
        $billingDateMessage = $nextBillingDate 
            ? 'on ' . $nextBillingDate->format('M d, Y')
            : 'at the end of your current billing period';

        // Format intervals
        $currentIntervalFormatted = ucfirst($currentTier['current_price']['recurring']['interval'] . 'ly');
        $newIntervalFormatted = ucfirst($newTier['current_price']['recurring']['interval'] . 'ly');
        
        return FormAction::make('switch_tier_'.$priceId)
            ->size('sm')
            ->label('Switch')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Switch to ' . $newTier['name'])
            ->form([
                Placeholder::make('comparison_table')
                    ->hiddenLabel()
                    ->content(new HtmlString(<<<HTML
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="text-left py-2 px-4 border-b-2 border-gray-200"></th>
                                    <th class="text-left py-2 px-4 border-b-2 border-gray-200">Current Plan</th>
                                    <th class="text-left py-2 px-4 border-b-2 border-gray-200">New Plan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-2 px-4 border-b border-gray-100 font-medium">Tier</td>
                                    <td class="py-2 px-4 border-b border-gray-100 font-bold">{$currentTier['name']}</td>
                                    <td class="py-2 px-4 border-b border-gray-100 font-bold text-primary-600">{$newTier['name']}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-4 border-b border-gray-100 font-medium">Price</td>
                                    <td class="py-2 px-4 border-b border-gray-100 text-gray-600">{$currentPriceFormatted}</td>
                                    <td class="py-2 px-4 border-b border-gray-100 text-gray-600">{$newPriceFormatted}</td>
                                </tr>
                            </tbody>
                        </table>
                    HTML)),
                
                Placeholder::make('billing_info')
                    ->content(function() use ($isUpgrade, $billingDateMessage) {
                        if ($isUpgrade) {
                            return 'This is an upgrade. You will be charged immediately for the prorated amount, and your subscription will be updated right away with the new features.';
                        } else {
                            return 'This is a downgrade. The change will take effect ' . $billingDateMessage . '.';
                        }
                    })
                    ->extraAttributes([
                        'class' => $isUpgrade ? 'text-warning-600 p-2 bg-warning-50 rounded' : 'text-success-600 p-2 bg-success-50 rounded'
                    ]),
            ])
            ->action(function() use ($priceId, $isUpgrade, $user) {
                if ($isUpgrade) {
                    // For upgrades, swap immediately
                    $user->subscription('default')->swap($priceId);
                } else {
                    // For downgrades, swap at end of cycle
                    $user->subscription('default')->swapNextCycle($priceId);
                }
                
                // Clear caches after changing subscription
                $this->clearAllCaches();
            });
    }

    /**
     * Main subscribe action that determines the appropriate action based on context
     */
    public function subscribeAction(array $tier): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $priceId = $tier['current_price']['id'];
        
        // Get subscription status - use method call to ensure proper initialization
        $subscription = $this->getCurrentSubscription();
        
        // Default to checkout for new subscriptions
        if (!$subscription || $subscription->stripe_status !== 'active') {
            return FormAction::make('subscribe_'.$priceId)
                ->label('Subscribe')
                ->icon('heroicon-o-cog')
                ->color('primary')
                ->size('sm')
                ->url($this->getCheckoutUrl($priceId));
        }
        
        // Check for existing subscription matches
        $isCurrentSubscription = $subscription->stripe_price === $priceId;
        $isSameProduct = $tier['product_id'] === $subscription->stripe_product;
        
        // Path 1: Already subscribed to this exact plan - show manage action
        if ($isCurrentSubscription) {
            return $this->manageMembershipAction($priceId);
        }
        
        // Path 2: Downgrading to free plan
        if ($tier['current_price']['unit_amount'] === 0) {
            return $this->cancelMembershipAction();
        }
        
        // Path 3: Same product but different price (interval change)
        if ($isSameProduct) {
            return $this->switchIntervalAction($priceId);
        }
        
        // Path 4: Different product (tier change)
        // Get current tier details only when needed (lazy loading)
        $currentTier = $this->getMembershipTiers()->get($subscription->stripe_price);
        return $this->switchTierAction($currentTier, $tier);
    }

    /**
     * Clear the membership tiers cache
     */
    public function clearMembershipTiersCache(): void
    {
        Cache::forget(self::MEMBERSHIP_TIERS_CACHE_KEY);
        // Also clear filtered tiers
        $this->filteredTiersByInterval = [];
    }
    
    /**
     * Clear all related caches for a user
     */
    public function clearAllCaches(): void
    {
        // Clear membership tiers
        $this->clearMembershipTiersCache();
        
        // Clear checkout URLs
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user) {
            // Clear billing portal cache
            Cache::forget('billing_portal_' . $user->id);
            
            // Clear checkout URL caches - only if we have already loaded tiers
            if ($this->tiers) {
                foreach ($this->tiers as $tier) {
                    $priceId = $tier['current_price']['id'];
                    Cache::forget(self::CHECKOUT_URL_CACHE_KEY . $user->id . '_' . $priceId);
                }
            }
            
            // Clear subscription cache - only if we have already loaded subscription
            if ($this->currentSubscription) {
                Cache::forget('stripe_subscription_' . $this->currentSubscription->id);
            }
        }
        
        // Reset instances
        $this->tiers = null;
        $this->currentSubscription = null;
        $this->stripeSubscription = null;
        $this->currentTier = null;
        $this->checkoutUrls = [];
    }
    
    /**
     * Get the page title
     */
    public function getTitle(): string
    {
        return 'Membership';
    }
    
    /**
     * Create or retrieve a cached checkout URL
     */
    protected function getCheckoutUrl(string $priceId): string
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cacheKey = self::CHECKOUT_URL_CACHE_KEY . $user->id . '_' . $priceId;
        
        // Check if we already generated this URL in this request
        if (isset($this->checkoutUrls[$priceId])) {
            return $this->checkoutUrls[$priceId];
        }
        
        // Check if URL is in the cache
        $url = Cache::get($cacheKey);
        if ($url) {
            $this->checkoutUrls[$priceId] = $url;
            return $url;
        }
        
        // Generate and cache the URL
        $url = $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route(self::getRouteName(), ['status' => 'success']),
                'cancel_url' => route(self::getRouteName(), ['status' => 'cancelled']),
            ])->url;
            
        Cache::put($cacheKey, $url, self::CHECKOUT_URL_CACHE_TTL);
        $this->checkoutUrls[$priceId] = $url;
        
        return $url;
    }
    
    /**
     * Action to manage membership
     */
    public function manageMembership()
    {
        return Action::make('manage-membership')
            ->label('Manage')
            ->url(fn() => $this->getBillingPortalUrl());
    }

    /**
     * Switch to a different membership plan
     */
    public function switchMembership(string $priceId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Clear caches after changing subscription
        $this->clearAllCaches();
        
        return $user->subscription('default')->swapNextCycle($priceId);
    }

    /**
     * Create a checkout session for a product
     */
    public function createCheckout(string $priceId)
    {
        // Clear the cache after checkout is created
        $this->clearMembershipTiersCache();
        
        return redirect($this->getCheckoutUrl($priceId));
    }
    
    /**
     * Get a cached billing portal URL
     */
    protected function getBillingPortalUrl(): string
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cacheKey = 'billing_portal_' . $user->id;
        
        // Check if URL is in the cache 
        $url = Cache::get($cacheKey);
        if ($url) {
            return $url;
        }
        
        // Generate and cache the URL
        $url = $user->billingPortalUrl(route(self::getRouteName()));
        Cache::put($cacheKey, $url, 300); // Cache for 5 minutes
        
        return $url;
    }
}