<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Impersonate;

    public static string $factory = \Database\Factories\UserFactory::class;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return Str::endsWith( $this->email, '@corvmc.org');
    }

    public function getStripeCustomerIdAttribute(): string
    {
        return Cache::remember("user_{$this->id}_stripe_customer_id", 60, function () {
            $stripe = app(StripeClient::class);

            // Search for existing customer
            $searchResult = $stripe->customers->search(['query' => "email:'{$this->email}'", 'limit' => 1]);
            $id = $searchResult->data[0]?->id ?? null;
            if($id) return $id;

            // Create a new customer
            $customer = $stripe->customers->create([
                'email' => $this->email,
            ]);
            return $customer->id;
        });
    }

    public function getMembershipAttribute(): ?object
    {
        $customer_id = $this->stripe_customer_id;

        if(!$customer_id) {
            throw new \Exception('Stripe customer ID not found');
        }

        $calculateTTL = function ($subscription) {
            if(!$subscription) return 60;

            $expires_in = $subscription->current_period_end - time();
            return min($expires_in, 3600);
        };

        $cached = Cache::get("user_{$this->id}_membership");
        if($cached) return $cached;

        $stripe = app(StripeClient::class);
        $subscriptions = $stripe->subscriptions->all(['customer' => $customer_id, 'status' => 'active', 'limit' => 1]);
        $subscription = $subscriptions->data[0] ?? null;

        if(!$subscription) {
            Cache::put("user_{$this->id}_membership", null, 60);
            return null;
        }

        $ttl = $calculateTTL($subscription);
        Cache::put("user_{$this->id}_membership", $subscription, $ttl);
        return $subscription;
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }
}
