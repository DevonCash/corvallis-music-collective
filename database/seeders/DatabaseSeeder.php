<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use CorvMC\BandProfiles\Models\Band;
use CorvMC\Sponsorship\Models\Sponsor;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call module seeders first to set up required data
        $this->call([
            \CorvMC\PracticeSpace\Database\Seeders\DatabaseSeeder::class,
            \CorvMC\Sponsorship\Database\Seeders\DatabaseSeeder::class,
            \CorvMC\Productions\Database\Seeders\ProductionSeeder::class,
        ]);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);

        // Create admin users
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@corvmc.org',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('admin');

        $staffAdmin = User::factory()->create([
            'name' => 'Staff Admin',
            'email' => 'staff@corvmc.org',
            'password' => Hash::make('password'),
        ]);
        $staffAdmin->assignRole('admin');

        // Create band managers and their bands
        $bandManager1 = User::factory()->create([
            'name' => 'Rock Band Manager',
            'email' => 'rock@example.com',
            'password' => Hash::make('password'),
        ]);
        
        $rockBand = Band::factory()->create([
            'name' => 'The Rock Stars',
            'genre' => 'Rock',
            'location' => 'Corvallis',
            'bio' => 'Local rock sensation',
        ]);
        $bandManager1->bands()->attach($rockBand, ['role' => 'admin']);

        $bandManager2 = User::factory()->create([
            'name' => 'Jazz Band Manager',
            'email' => 'jazz@example.com',
            'password' => Hash::make('password'),
        ]);

        $jazzBand = Band::factory()->create([
            'name' => 'Jazz Ensemble',
            'genre' => 'Jazz',
            'location' => 'Corvallis',
            'bio' => 'Smooth jazz collective',
        ]);
        $bandManager2->bands()->attach($jazzBand, ['role' => 'admin']);

        // Create band members
        $bandMember1 = User::factory()->create([
            'name' => 'Rock Band Member',
            'email' => 'rocker@example.com',
            'password' => Hash::make('password'),
        ]);
        $bandMember1->bands()->attach($rockBand, ['role' => 'member']);

        $bandMember2 = User::factory()->create([
            'name' => 'Multi-Band Member',
            'email' => 'musician@example.com',
            'password' => Hash::make('password'),
        ]);
        $bandMember2->bands()->attach($rockBand, ['role' => 'member']);
        $bandMember2->bands()->attach($jazzBand, ['role' => 'member']);

        // Create sponsor representatives
        $sponsorRep1 = User::factory()->create([
            'name' => 'Coffee Shop Owner',
            'email' => 'coffee@example.com',
            'password' => Hash::make('password'),
        ]);

        $coffeeSponsor = Sponsor::factory()->create([
            'name' => 'Local Coffee Co.',
            'type' => 'business_sponsor',
            'tier_id' => 2, // Silver tier
        ]);
        $sponsorRep1->sponsors()->attach($coffeeSponsor, ['role' => 'representative']);

        $sponsorRep2 = User::factory()->create([
            'name' => 'Music Store Owner',
            'email' => 'store@example.com',
            'password' => Hash::make('password'),
        ]);

        $storeSponsor = Sponsor::factory()->create([
            'name' => 'Downtown Music Shop',
            'type' => 'business_sponsor',
            'tier_id' => 3, // Gold tier
        ]);
        $sponsorRep2->sponsors()->attach($storeSponsor, ['role' => 'representative']);

        // Create regular members
        User::factory()->create([
            'name' => 'Regular Member',
            'email' => 'member@example.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'New Member',
            'email' => 'new@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
