<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create system-level roles that are required for the application
        $this->createSystemRoles();
        
        // Create permissions for granular access control
        $this->createPermissions();
        
        // Assign permissions to roles
        $this->assignPermissions();
    }

    /**
     * Create system-level roles required by the application.
     */
    private function createSystemRoles(): void
    {
        // Core administrative role
        Role::firstOrCreate(['name' => 'admin'], [
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Production management role
        Role::firstOrCreate(['name' => 'production-manager'], [
            'name' => 'production-manager',
            'guard_name' => 'web',
        ]);

        // Event coordination role
        Role::firstOrCreate(['name' => 'event-coordinator'], [
            'name' => 'event-coordinator',
            'guard_name' => 'web',
        ]);

        // Volunteer role for limited access
        Role::firstOrCreate(['name' => 'volunteer'], [
            'name' => 'volunteer',
            'guard_name' => 'web',
        ]);

        // Staff role for CMC staff members
        Role::firstOrCreate(['name' => 'staff'], [
            'name' => 'staff',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Create permissions for granular access control.
     */
    private function createPermissions(): void
    {
        // User management permissions
        Permission::firstOrCreate(['name' => 'view-users'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-users'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-users'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-users'], ['guard_name' => 'web']);

        // Production management permissions
        Permission::firstOrCreate(['name' => 'view-productions'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-productions'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-productions'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-productions'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage-production-states'], ['guard_name' => 'web']);

        // Booking management permissions
        Permission::firstOrCreate(['name' => 'view-bookings'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-bookings'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-bookings'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-bookings'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage-booking-states'], ['guard_name' => 'web']);

        // Band management permissions
        Permission::firstOrCreate(['name' => 'view-bands'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-bands'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-bands'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-bands'], ['guard_name' => 'web']);

        // Sponsor management permissions
        Permission::firstOrCreate(['name' => 'view-sponsors'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-sponsors'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-sponsors'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-sponsors'], ['guard_name' => 'web']);

        // System administration permissions
        Permission::firstOrCreate(['name' => 'access-admin-panel'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage-roles'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage-permissions'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-system-logs'], ['guard_name' => 'web']);
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissions(): void
    {
        // Admin role gets all permissions
        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());

        // Production manager permissions
        $productionManager = Role::findByName('production-manager');
        $productionManager->syncPermissions([
            'view-productions',
            'create-productions',
            'edit-productions',
            'manage-production-states',
            'view-bookings',
            'create-bookings',
            'edit-bookings',
            'view-bands',
            'view-sponsors',
        ]);

        // Event coordinator permissions
        $eventCoordinator = Role::findByName('event-coordinator');
        $eventCoordinator->syncPermissions([
            'view-productions',
            'edit-productions',
            'manage-production-states',
            'view-bookings',
            'edit-bookings',
            'manage-booking-states',
            'view-bands',
            'view-sponsors',
        ]);

        // Staff permissions
        $staff = Role::findByName('staff');
        $staff->syncPermissions([
            'view-productions',
            'view-bookings',
            'view-bands',
            'view-sponsors',
            'create-bookings',
            'edit-bookings',
        ]);

        // Volunteer permissions (very limited)
        $volunteer = Role::findByName('volunteer');
        $volunteer->syncPermissions([
            'view-productions',
            'view-bookings',
        ]);
    }
}