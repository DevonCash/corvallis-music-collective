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
        // Always create essential roles and permissions
        $this->call([
            RoleSeeder::class,
        ]);

        // Call module seeders for essential data structure
        $this->call([
            \CorvMC\Sponsorship\Database\Seeders\DatabaseSeeder::class, // Creates sponsor tiers
            \CorvMC\PracticeSpace\Database\Seeders\DatabaseSeeder::class, // Creates essential practice space data
        ]);

        // Create essential admin user if environment allows
        $this->createEssentialAdminUser();

        // Only create sample data in local/development environment
        if (app()->environment(['local', 'development'])) {
            $this->createSampleData();
        }
    }

    /**
     * Create essential admin user for the application.
     */
    private function createEssentialAdminUser(): void
    {
        // Create primary admin user using environment variables
        $adminEmail = env('ADMIN_EMAIL', 'admin@corvmc.org');
        $adminPassword = env('ADMIN_PASSWORD', 'password');
        $adminName = env('ADMIN_NAME', 'System Administrator');

        // Only create if user doesn't exist
        if (!User::where('email', $adminEmail)->exists()) {
            $admin = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]);
            
            $admin->assignRole('admin');
            
            $this->command->info("Created admin user: {$adminEmail}");
        }
    }

    /**
     * Create sample data for development/testing environments.
     */
    private function createSampleData(): void
    {
        $this->command->info('Creating sample data for development environment...');

        // Call remaining module seeders that create sample data
        $this->call([
            \CorvMC\Productions\Database\Seeders\ProductionSeeder::class,
        ]);

        // Create sample staff user
        $staffAdmin = User::factory()->create([
            'name' => 'Staff Admin',
            'email' => 'staff@corvmc.org',
            'password' => Hash::make('password'),
        ]);
        $staffAdmin->assignRole('staff');

        // Create sample production manager
        $productionManager = User::factory()->create([
            'name' => 'Production Manager',
            'email' => 'productions@corvmc.org',
            'password' => Hash::make('password'),
        ]);
        $productionManager->assignRole('production-manager');

        // Create sample event coordinator
        $eventCoordinator = User::factory()->create([
            'name' => 'Event Coordinator',
            'email' => 'events@corvmc.org',
            'password' => Hash::make('password'),
        ]);
        $eventCoordinator->assignRole('event-coordinator');

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

        // Create sample volunteer
        $volunteer = User::factory()->create([
            'name' => 'Volunteer Helper',
            'email' => 'volunteer@example.com',
            'password' => Hash::make('password'),
        ]);
        $volunteer->assignRole('volunteer');
    }
}
