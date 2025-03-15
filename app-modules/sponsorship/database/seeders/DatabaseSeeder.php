<?php

namespace CorvMC\Sponsorship\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SponsorTierSeeder::class,
        ]);
    }
} 