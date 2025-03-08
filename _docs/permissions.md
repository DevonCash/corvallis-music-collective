# Permissions System

The application uses a modular permissions system that allows each module to define and manage its own permissions. This system is built on top of the [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) package.

## Architecture

The permissions system consists of several key components:

### 1. ModuleServiceProvider

The base service provider (`App\Modules\Core\Providers\ModuleServiceProvider`) that all module service providers extend. It provides the foundation for permission registration.

### 2. RegistersPermissions Trait

A trait (`App\Modules\Core\Concerns\RegistersPermissions`) that handles the actual permission registration logic. It:
- Creates permissions if they don't exist
- Ensures the Super Admin role has all permissions
- Handles database transactions

### 3. Module-specific Service Providers

Each module defines its own service provider that extends `ModuleServiceProvider` and implements the `getPermissions()` method to define its permissions.

## Available Permissions

### User Module
```php
[
    'view_users' => 'View users in the system',
    'create_users' => 'Create new users',
    'edit_users' => 'Edit existing users',
    'delete_users' => 'Delete users from the system',
    'impersonate_users' => 'Impersonate other users',
    'manage_roles' => 'Manage user roles and permissions',
]
```

### Practice Space Module
```php
[
    // Room permissions
    'view_rooms' => 'View practice rooms',
    'create_rooms' => 'Create new practice rooms',
    'edit_rooms' => 'Edit practice room details',
    'delete_rooms' => 'Delete practice rooms',
    
    // Booking permissions
    'view_bookings' => 'View room bookings',
    'create_bookings' => 'Create new room bookings',
    'edit_bookings' => 'Edit existing bookings',
    'delete_bookings' => 'Delete bookings',
    'manage_other_bookings' => 'Manage bookings made by other users',
]
```

### Payments Module
```php
[
    // Payment permissions
    'view_payments' => 'View payment records',
    'create_payments' => 'Create new payments',
    'edit_payments' => 'Edit payment details',
    'delete_payments' => 'Delete payment records',
    'process_refunds' => 'Process payment refunds',
    'view_payment_reports' => 'View payment reports and analytics',
    
    // Product permissions
    'view_products' => 'View available products',
    'create_products' => 'Create new products',
    'edit_products' => 'Edit product details',
    'delete_products' => 'Delete products',
    'manage_product_pricing' => 'Manage product pricing',
    
    // Financial permissions
    'view_financial_reports' => 'View financial reports',
    'export_financial_data' => 'Export financial data',
    'manage_payment_settings' => 'Manage payment gateway settings',
]
```

## Roles

By default, the system creates a `Super Admin` role that has all permissions. Additional roles can be created through the admin interface or programmatically.

## Syncing Permissions

The system includes an Artisan command to sync all permissions:

```bash
php artisan permissions:sync
```

This command:
1. Reads permissions from all modules
2. Creates any missing permissions
3. Ensures the Super Admin role has all permissions

## Adding New Permissions

To add new permissions to a module:

1. Open the module's service provider (e.g., `PaymentsServiceProvider.php`)
2. Add the new permission to the `getPermissions()` method:
```php
protected function getPermissions(): array
{
    return [
        'new_permission' => 'Description of the permission',
        // ... existing permissions
    ];
}
```
3. Run `php artisan permissions:sync` to register the new permission

## Best Practices

1. Use descriptive permission names that clearly indicate their purpose
2. Prefix permissions with their action (e.g., `view_`, `create_`, `edit_`, `delete_`)
3. Group related permissions together in the service provider
4. Always provide clear descriptions for permissions
5. Run `permissions:sync` after adding new permissions
6. Use permission checks in your controllers and Filament resources

## Usage in Code

### Checking Permissions

```php
// Check if user has a permission
$user->can('view_payments');

// Check if user has any of these permissions
$user->hasAnyPermission(['view_payments', 'view_payment_reports']);

// Check if user has all of these permissions
$user->hasAllPermissions(['view_payments', 'create_payments']);
```

### Protecting Routes

```php
Route::middleware(['permission:view_payments'])->group(function () {
    // Routes that require 'view_payments' permission
});
```

### In Filament Resources

```php
public static function canViewAny(): bool
{
    return auth()->user()->can('view_payments');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create_payments');
}
``` 