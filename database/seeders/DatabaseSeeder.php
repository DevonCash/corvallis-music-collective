<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Band;
use App\Models\Event;
use App\Models\Post;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);

        User::factory()->create([
            "name" => "Devon Cash",
            "email" => "devon@corvmc.org",
        ]);

        Event::factory(120)->hasBands(fake()->numberBetween(1, 5))->create();
        Post::factory(50)->create();
    }
}
