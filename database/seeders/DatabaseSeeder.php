<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        $role = Role::create(["name" => "super-admin"]);
        $role->givePermissionTo(Permission::all());
    }
}
