<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test admin user if none exists
        if (User::where('email', 'test@corvmc.org')->doesntExist()) {
            User::factory()->create([
                'name' => 'Test Admin',
                'email' => 'test@corvmc.org',
            ]);
        }
        
        // Create non-admin users if they don't exist
        if (User::where('email', 'student@example.com')->doesntExist()) {
            User::factory()->create([
                'name' => 'Student User',
                'email' => 'student@example.com',
                'password' => Hash::make('password'),
            ]);
        }
        
        if (User::where('email', 'musician@example.com')->doesntExist()) {
            User::factory()->create([
                'name' => 'Musician User',
                'email' => 'musician@example.com',
                'password' => Hash::make('password'),
            ]);
        }
        
        if (User::where('email', 'band@example.com')->doesntExist()) {
            User::factory()->create([
                'name' => 'Band Member',
                'email' => 'band@example.com',
                'password' => Hash::make('password'),
            ]);
        }

        // Run module seeders
        $this->call([
            \CorvMC\PracticeSpace\Database\Seeders\DatabaseSeeder::class,
        ]);
    }
}
