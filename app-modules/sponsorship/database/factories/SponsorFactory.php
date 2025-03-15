<?php

namespace CorvMC\Sponsorship\Database\Factories;

use CorvMC\Sponsorship\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['business_sponsor', 'community_partner']),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'description' => fake()->paragraph(),
            'tier_id' => fake()->numberBetween(1, 3), // Bronze, Silver, Gold tiers from seeder
            'active_until' => fake()->dateTimeBetween('now', '+1 year'),
        ];
    }

    public function businessSponsor(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'business_sponsor',
            ];
        });
    }

    public function communityPartner(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'community_partner',
            ];
        });
    }
} 