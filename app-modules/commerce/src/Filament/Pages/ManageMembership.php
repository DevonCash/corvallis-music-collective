<?php

namespace CorvMC\Commerce\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Components\{Grid, Tabs, Tabs\Tab, Section, View, ViewField};
use Illuminate\Support\Collection;
use Filament\Forms\Components\Actions\Action as FormAction;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class ManageMembership extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'commerce::filament.pages.manage-membership';
    protected static ?string $navigationGroup = 'Account';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'membership';

    protected Collection $tiers;
    
    // Cache settings
    protected const MEMBERSHIP_TIERS_CACHE_KEY = 'membership_tiers';
    protected const MEMBERSHIP_TIERS_CACHE_TTL = 3600; // 1 hour

    public array $data = [];

    /**
     * Mount the component and handle status parameters
     */
    public function mount(): void
    {
        $this->handleStatusNotifications();
        $this->data = ['interval' => 'month'];
        $this->form->fill($this->data);
        $this->tiers = $this->getMembershipTiers();
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
        if(!isset($this->tiers)) {
        $this->tiers = Cache::remember(self::MEMBERSHIP_TIERS_CACHE_KEY, self::MEMBERSHIP_TIERS_CACHE_TTL, function () {
            $stripe = app(StripeClient::class);
        
            // Fetch all products with their prices expanded in a single API call
            $products = collect($stripe->products->search([
                'query' => '-metadata[\'membership_tier\']:null',
                'limit' => 100,
            ])->data)->keyBy('id');

            $prices = $stripe->prices->search([
                'query' => $products->map(fn($pr) => "product:\"{$pr->id}\"")->join(' OR '),
                'limit' => 100,
            ]);
            
            $tiers = collect($prices->data)
            ->map(function($price) use ($products) {
                $product = $products->get($price->product);
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
            } )->keyBy('current_price.id');
            return $tiers;
        });
            
        }
        return $this->tiers;
    }

    /**
     * Build the form with membership options
     */
    public function form(Form $form): Form
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tiers = $this->getMembershipTiers();
        $currentSubscription = $user->subscription('default')->asStripeSubscription();
        $currentTier = $tiers->get($currentSubscription->plan->id);
        $currentInterval = $currentTier['current_price']['recurring']['interval'] ?? 'month';
        
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Membership')
                ->columns(3)
                ->visible(fn() => $currentSubscription?->stripe_status === 'active')
                ->headerActions([
                    $this->manageMembershipAction($currentSubscription->plan->id)
                        ->label('Manage Membership')
                ])
                ->schema([
                    Placeholder::make('tier')
                    ->content(fn() => $currentTier['name'])
                    ->extraAttributes([
                        'class' => 'text-2xl font-bold',
                    ]),
                    Placeholder::make('price')
                    ->content(fn() => '$' . number_format($currentTier['current_price']['unit_amount'] / 100, 2) . ' / ' . ($currentTier['current_price']['recurring']['interval'] === 'month' ? 'month' : 'year'))
                    ->extraAttributes([
                        'class' => 'text-2xl font-bold',
                    ]),
                    Placeholder::make('membership-period-ends')
                    ->label('Next Billing Date')
                    ->content(fn() => \Carbon\Carbon::parse($currentSubscription->current_period_end)->format('M d, Y'))
                    ->extraAttributes([
                        'class' => 'text-2xl font-bold',
                    ]),
                ]),
                Tabs::make('Billing Cycle')
                    ->contained(false)
                    ->default($currentInterval)
                    ->tabs([
                        Tab::make('Monthly')->schema([$this->getPlansGrid('month')]),
                        Tab::make('Yearly')
                            ->badge('2 months free!')
                            ->badgeColor('success')
                            ->schema([
                                $this->getPlansGrid('year')
                            ]),
                    ])
            ]);
    }
    
    /**
     * Get the grid of plans for a specific interval
     */
    protected function getPlansGrid(string $interval): Grid
    {
        return Grid::make()
            ->columns([
                'default' => 1,
                'lg' => 3
            ])
            ->schema($this->createPlanComponents($interval));
    }

    /**
     * Create plan components for the form
     */
    protected function createPlanComponents(string $interval): array
    {
        $tiers = $this->getMembershipTiers();
         // Get current user and subscription
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $currentSubscription = $user->subscription('default');
        

        
        // Filter tiers for the requested interval and sort by level
        return $tiers
            ->filter(fn($tier) => $tier['current_price']['recurring']['interval'] === $interval)
            ->sortBy('level')
            ->values()  
            ->map(function($tier) use ($currentSubscription) {
                $isFreePlan = isset($tier['current_price']['unit_amount']) && 
                              $tier['current_price']['unit_amount'] === 0;
                
                $isCurrentPlan = $currentSubscription?->stripe_status === 'active' && 
                                $currentSubscription->stripe_price === $tier['current_price']['id'];
                
                $priceId = $tier['current_price']['id'];
                $name = $tier['name'] ?? 'Unknown';
                
                // Create a Section with a View component instead of MembershipPlanCard
                return Section::make($name)
                    ->compact()
                    ->columnSpan(1)
                    ->columns(1)
                    ->extraAttributes([
                        'class' => $this->getPlanCardClasses($isCurrentPlan, $isFreePlan),
                    ])
                    ->headerActions([$this->subscribeAction($priceId)])
                    ->schema([
                        ViewField::make($priceId)
                        ->view('commerce::components.membership-plan-details')
                        ->viewData([
                            'tier' => $tier,
                            'isCurrentPlan' => $isCurrentPlan,
                            'isFreePlan' => $isFreePlan,
                        ])
                    ]);
            })
            ->toArray();
    }
    
    /**
     * Get CSS classes for the plan card based on its status
     */
    protected function getPlanCardClasses(bool $isCurrentPlan, bool $isFreePlan): string
    {
        $classes = 'h-full flex flex-col border-4 rounded-xl shadow-sm';
        
        if ($isCurrentPlan) {
            $classes .= ' border-primary bg-primary-50';
        } elseif ($isFreePlan) {
            $classes .= ' border-gray-300 bg-gray-50';
        } else {
            $classes .= ' border-gray-200';
        }
        
        return $classes;
    }

    /**
     * Action to switch between monthly and yearly billing
     */
    public function switchIntervalAction(string $priceId): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $currentSubscription = $user->subscription('default');
        $currentPriceId = $currentSubscription->stripe_price;
        
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
            
        // Get next billing date
        $nextBillingDate = \Carbon\Carbon::parse($currentSubscription->asStripeSubscription()->current_period_end);
        
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
                    ->content(function() use ($isUpgrade, $nextBillingDate) {
                        if ($isUpgrade) {
                            return 'This is an upgrade. You will be charged immediately for the prorated amount, and your subscription will be updated right away.';
                        } else {
                            return 'This change will take effect at the end of your current billing period on ' . $nextBillingDate->format('M d, Y') . '.';
                        }
                    })
                    ->extraAttributes([
                        'class' => $isUpgrade ? 'text-warning-600 p-2 bg-warning-50 rounded' : 'text-success-600 p-2 bg-success-50 rounded'
                    ]),
            ])
            ->action(function() use ($priceId, $isUpgrade) {
                if ($isUpgrade) {
                    // For upgrades, swap immediately
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    $user->subscription('default')->swap($priceId);
                } else {
                    // For downgrades, swap at end of cycle
                    $this->switchMembership($priceId);
                }
            });
    }

    /**
     * Action to manage existing membership
     */
    public function manageMembershipAction(string $priceId): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return FormAction::make('manage_membership_'.$priceId)
            ->label('Manage')
            ->icon('heroicon-o-cog')
            ->size('sm')
            ->url(fn() => $user->billingPortalUrl(route(self::getRouteName())));
    }

    /**
     * Action to cancel membership
     */
    public function cancelMembershipAction(): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $endOfBillingPeriod = \Carbon\Carbon::parse($user->subscription('default')->ends_at);
        return FormAction::make('cancel_membership')
            ->label('Switch to Free')
            ->icon('heroicon-o-arrow-down')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Switch to Free')
            ->size('sm')
            ->modalDescription("At the end of your billing period, your subscription will be cancelled and you will have access to the free tier. This goes into effect on " . $endOfBillingPeriod->format('M d, Y'). ".")
            ->action(function() use ($user) {
                $user->subscription('default')->cancel();
            });
    }

    /**
     * Action to switch tier
     */
    public function switchTierAction(string $priceId): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $currentSubscription = $user->subscription('default');
        $currentPriceId = $currentSubscription->stripe_price;
        
        $tiers = $this->getMembershipTiers();
        $newTier = $tiers->get($priceId);
        $currentTier = $tiers->get($currentPriceId);
        
        // Calculate price difference
        $currentAmount = $currentTier['current_price']['unit_amount'] / 100;
        $newAmount = $newTier['current_price']['unit_amount'] / 100;
        $isUpgrade = $newAmount > $currentAmount;
        
        // Format price strings
        $currentPriceFormatted = '$' . number_format($currentAmount, 2) . ' / ' . 
            ($currentTier['current_price']['recurring']['interval'] === 'month' ? 'month' : 'year');
        $newPriceFormatted = '$' . number_format($newAmount, 2) . ' / ' . 
            ($newTier['current_price']['recurring']['interval'] === 'month' ? 'month' : 'year');
            
        // Get next billing date
        $nextBillingDate = \Carbon\Carbon::parse($currentSubscription->asStripeSubscription()->current_period_end);
        
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
                    ->content(function() use ($isUpgrade, $nextBillingDate) {
                        if ($isUpgrade) {
                            return 'This is an upgrade. You will be charged immediately for the prorated amount, and your subscription will be updated right away with the new features.';
                        } else {
                            return 'This is a downgrade. The change will take effect at the end of your current billing period on ' . $nextBillingDate->format('M d, Y') . '.';
                        }
                    })
                    ->extraAttributes([
                        'class' => $isUpgrade ? 'text-warning-600 p-2 bg-warning-50 rounded' : 'text-success-600 p-2 bg-success-50 rounded'
                    ]),
            ])
            ->action(function() use ($priceId, $isUpgrade) {
                if ($isUpgrade) {
                    // For upgrades, swap immediately
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    $user->subscription('default')->swap($priceId);
                } else {
                    // For downgrades, swap at end of cycle
                    $this->switchMembership($priceId);
                }
            });
    }

    /**
     * Main subscribe action that determines the appropriate action based on context
     */
    public function subscribeAction(string $priceId): FormAction
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $currentSubscription = $user->subscription('default');
        $isCurrentSubscription = $currentSubscription && 
                                $currentSubscription->stripe_status === 'active' && 
                                $currentSubscription->stripe_price === $priceId;
        $tier = $this->getMembershipTiers()->get($priceId);

        if($currentSubscription?->stripe_status === 'active') {
        // Manage current subscription
        if($isCurrentSubscription) {
            return $this->manageMembershipAction($priceId);
        }

        // Downgrade to free plan
        if(!$isCurrentSubscription && $tier['current_price']['unit_amount'] === 0) {
            return $this->cancelMembershipAction();
        }

        // Product is the same, price is different
        if($tier['product_id'] === $currentSubscription->stripe_product) {
            return $this->switchIntervalAction($priceId);
        }

        // Product is different
        if($tier['product_id'] !== $currentSubscription->stripe_product) {
            return $this->switchTierAction($priceId);
        }
    }

        // Otherwise, show confirmation modal for subscription change
        return FormAction::make('subscribe_'.$priceId)
            ->label('Subscribe')
            ->icon('heroicon-o-cog')
            ->color('primary')
            ->size('sm')
            ->url($user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route(self::getRouteName(), ['status' => 'success']),
                'cancel_url' => route(self::getRouteName(), ['status' => 'cancelled']),
            ])->url);
    }

    /**
     * Clear the membership tiers cache
     */
    public function clearMembershipTiersCache(): void
    {
        Cache::forget(self::MEMBERSHIP_TIERS_CACHE_KEY);
    }
    
    /**
     * Get the page title
     */
    public function getTitle(): string
    {
        return 'Membership';
    }
    
    /**
     * Create a checkout session for a product
     */
    public function createCheckout(string $priceId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Clear the cache after checkout is created
        $this->clearMembershipTiersCache();
        
        return redirect($user->newSubscription('default', $priceId)
        ->checkout([
            'success_url' => route(self::getRouteName(), ['status' => 'success']),
            'cancel_url' => route(self::getRouteName(), ['status' => 'cancelled']),
        ])->url);
    }
    
    /**
     * Action to manage membership
     */
    public function manageMembership()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return Action::make('manage-membership')
            ->label('Manage')
            ->url(fn() => $user->billingPortalUrl(route(self::getRouteName())));
    }

    /**
     * Switch to a different membership plan
     */
    public function switchMembership(string $priceId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user->subscription('default')->swapNextCycle($priceId);
    }
}