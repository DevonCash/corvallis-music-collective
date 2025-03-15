<?php

namespace CorvMC\Sponsorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SponsorTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'amount' => 500.00,
                'benefits' => 'Basic sponsorship benefits including logo on website',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver',
                'amount' => 1000.00,
                'benefits' => 'Enhanced sponsorship benefits including logo on website and event materials',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'amount' => 2500.00,
                'benefits' => 'Premium sponsorship benefits including prominent logo placement and VIP event access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('sponsor_tiers')->insert($tiers);
    }
} 