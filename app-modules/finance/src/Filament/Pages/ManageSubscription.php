<?php

namespace CorvMC\Finance\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Stripe\StripeClient;
use CorvMC\Finance\Models\Product;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Filament\Notifications\Notification;

class ManageSubscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'finance::filament.pages.manage-subscription';
    
    protected static ?string $navigationGroup = 'Account';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $slug = 'membership';
    
    public function getTitle(): string
    {
        return 'Membership';
    }
    
    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $subscription = $user->membership;
        
        $actions = [];
        
        // If user has an active subscription, show manage button
        if ($subscription && $subscription->status === 'active') {
            $actions[] = Action::make('manage_subscription')
                ->label('Manage Subscription')
                ->url('https://billing.stripe.com/p/login/28oaFS9Xoahu1ygaEE?prefilled_email=' . urlencode($user->email))
                ->icon('heroicon-o-cog')
                ->openUrlInNewTab();
                
            // If not already cancelled, show cancel button
            if (!isset($subscription->cancel_at) && !isset($subscription->canceled_at)) {
                $actions[] = Action::make('cancel_subscription')
                    ->label('Cancel Subscription')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(function () use ($user, $subscription) {
                        $stripe = app(StripeClient::class);
                        $stripe->subscriptions->cancel($subscription->id, [
                            'prorate' => true,
                        ]);
                        
                        // Clear the membership cache
                        Cache::forget("user_{$user->id}_membership");
                        
                        // Show notification
                        Notification::make()
                            ->title('Success')
                            ->body('Your subscription has been cancelled.')
                            ->success()
                            ->send();
                        
                        // Refresh the page
                        $this->redirect(url()->current());
                    })
                    ->requiresConfirmation();
            }
        }
        
        return $actions;
    }
    
    public function getMembershipTier(): string
    {
        return Auth::user()->membership_tier;
    }
    
    public function getSubscription(): ?object
    {
        return Auth::user()->membership;
    }
    
    public function getProducts(): array
    {
        return Cache::remember('subscription_products', 3600, function () {
            $products = Product::where('is_active', true)->get();
            
            $result = [
                'cd' => null,
                'vinyl' => null,
            ];
            
            foreach ($products as $product) {
                if (stripos($product->name, 'CD') !== false) {
                    $result['cd'] = $product;
                } elseif (stripos($product->name, 'Vinyl') !== false) {
                    $result['vinyl'] = $product;
                }
            }
            
            return $result;
        });
    }
    
    public function getCDProduct()
    {
        return $this->getProducts()['cd'] ?? null;
    }
    
    public function getVinylProduct()
    {
        return $this->getProducts()['vinyl'] ?? null;
    }
    
    public function formatPrice($price): string
    {
        return '$' . number_format($price, 2);
    }
} 