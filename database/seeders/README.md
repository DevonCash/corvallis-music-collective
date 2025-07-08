# Database Seeding - Production Guide

This document explains the production-safe seeding structure for the Corvallis Music Collective application.

## Overview

The seeding system is designed to:
- **Always create essential roles and permissions** needed for the application
- **Create only one admin user** in production environments
- **Skip sample data creation** in production environments
- **Allow environment-specific configuration** for admin credentials

## Seeding Structure

### 1. RoleSeeder
**File:** `database/seeders/RoleSeeder.php`

Creates all essential roles and permissions:

#### System Roles
- `admin` - Full administrative access
- `production-manager` - Can manage productions and events
- `event-coordinator` - Can coordinate events and bookings
- `staff` - CMC staff with limited admin access
- `volunteer` - Very limited access for volunteers

#### Permissions
- User management (view, create, edit, delete users)
- Production management (view, create, edit, delete, manage states)
- Booking management (view, create, edit, delete, manage states)
- Band management (view, create, edit, delete bands)
- Sponsor management (view, create, edit, delete sponsors)
- System administration (admin panel, roles, permissions, logs)

### 2. DatabaseSeeder
**File:** `database/seeders/DatabaseSeeder.php`

Main seeder that:
- Always calls `RoleSeeder`
- Creates essential admin user (configurable via environment)
- Only creates sample data in `local` and `development` environments

### 3. Module Seeders
- **Sponsorship Seeder** - Always runs (creates sponsor tiers)
- **Practice Space Seeder** - Always runs (creates 1 essential room category, 1 practice room, and basic equipment; creates full sample data only in development)
- **Production Seeder** - Only runs in development (creates sample productions)

## Production Usage

### Environment Configuration

Add these variables to your production `.env` file:

```env
# Admin User Configuration
ADMIN_EMAIL=admin@corvmc.org
ADMIN_PASSWORD=your_secure_password_here
ADMIN_NAME=System Administrator

# Environment
APP_ENV=production
```

### Running Seeds in Production

```bash
# Run only essential seeds (roles + admin user)
php artisan db:seed

# The seeder will automatically:
# 1. Create all roles and permissions
# 2. Create sponsor tiers (essential data)
# 3. Create one practice room with category and equipment (essential for bookings)
# 4. Create one admin user
# 5. Skip all sample data
```

## Development Usage

### Running Seeds in Development

```bash
# Run full seeds (roles + admin user + sample data)
php artisan db:seed

# The seeder will automatically:
# 1. Create all roles and permissions
# 2. Create sponsor tiers (essential data)
# 3. Create essential practice room + comprehensive sample practice space data (multiple rooms, categories, equipment, bookings, favorites, waitlists)
# 4. Create admin user + sample users with various roles
# 5. Create sample bands, sponsors, and productions
```

### Available Sample Users (Development Only)

| Email | Role | Password |
|-------|------|----------|
| admin@corvmc.org | admin | password |
| staff@corvmc.org | staff | password |
| productions@corvmc.org | production-manager | password |
| events@corvmc.org | event-coordinator | password |
| volunteer@example.com | volunteer | password |
| rock@example.com | (band admin) | password |
| jazz@example.com | (band admin) | password |
| coffee@example.com | (sponsor rep) | password |
| store@example.com | (sponsor rep) | password |

## Role Capabilities

### Admin
- Full access to all features
- Can manage users, roles, permissions
- Can view system logs
- Access to admin panel

### Production Manager
- Can create and manage productions
- Can manage production states
- Can create and edit bookings
- Can view bands and sponsors

### Event Coordinator
- Can edit productions and manage states
- Can manage bookings and booking states
- Can view bands and sponsors

### Staff
- Can view most content
- Can create and edit bookings
- Limited administrative access

### Volunteer
- Can view productions and bookings
- Very limited access

## Context-Specific Roles

These roles are assigned in pivot tables, not as system roles:

### Band Roles (band_members table)
- `admin` - Can manage band settings and members
- `member` - Regular band member

### Sponsor Roles (sponsor_users table)
- `representative` - Can manage sponsor information

## Safety Features

1. **Environment Detection** - Sample data only created in local/development
2. **User Existence Check** - Admin user only created if it doesn't exist
3. **Safe Defaults** - All roles and permissions use `firstOrCreate()`
4. **Configurable Admin** - Admin credentials from environment variables
5. **Informative Output** - Seeder provides feedback on actions taken

## Troubleshooting

### "Role already exists" Error
This is normal - the seeder uses `firstOrCreate()` to safely handle existing roles.

### No Admin User Created
Check your environment configuration:
- Ensure `ADMIN_EMAIL` is set
- Verify the user doesn't already exist
- Check seeder output for error messages

### Sample Data in Production
Verify `APP_ENV=production` in your `.env` file. Sample data only creates in `local` and `development` environments.