<?php

namespace CorvMC\BandProfiles\Database\Seeders;

use Illuminate\Database\Seeder;
use CorvMC\BandProfiles\Models\Band;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create some example bands
        $bands = Band::factory()->count(3)->create([
            'formation_date' => now(),
            'location' => 'Corvallis',
        ]);

        // Get some random users to assign as band members
        $users = User::whereNot('email', 'admin@corvmc.org')->inRandomOrder()->limit(5)->get();

        // Assign users to bands with different roles
        foreach ($bands as $band) {
            // Assign 2-3 random users to each band
            $bandUsers = $users->random(rand(2, 3));
            foreach ($bandUsers as $user) {
                $band->members()->attach($user, [
                    'role' => rand(0, 1) ? 'admin' : 'member',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
} 