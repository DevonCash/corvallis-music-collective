<?php

namespace CorvMC\PracticeSpace\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the practice space module's database.
     */
    public function run(): void
    {
        $this->call([
            PracticeSpaceSeeder::class,
        ]);
    }
} 