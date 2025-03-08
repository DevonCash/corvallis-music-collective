<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user if none exists
        if (User::where('email', 'test@corvmc.org')->doesntExist()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@corvmc.org',
            ]);
        }

        // Run module seeders
        $this->call([
            \CorvMC\PracticeSpace\Database\Seeders\DatabaseSeeder::class,
        ]);
    }
}
