<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset Cache
        app()[
            \Spatie\Permission\PermissionRegistrar::class
        ]->forgetCachedPermissions();

        // Create Permissions
        Permission::create(["name" => "view admin panel"]);
        Permission::create(["name" => "manage events"]);
        Permission::create(["name" => "manage posts"]);
        Permission::create(["name" => "manage users"]);
        Permission::create(["name" => "manage bands"]);

        // Create Roles
        $admin = Role::create(["name" => "super-admin"]);
        $admin->givePermissionTo(Permission::all());

        User::create([
            "name" => "Devon Cash",
            "email" => "devon.p.cash@gmail.com",
        ])->assignRole($admin);
    }
}
