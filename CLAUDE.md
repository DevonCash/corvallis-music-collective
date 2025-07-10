# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Commands

### Development
- `composer dev` - Start development environment (runs Laravel server, queue listener, logs, and Vite dev server concurrently)
- `npm run dev` - Start Vite development server for frontend assets
- `npm run build` - Build production frontend assets
- `php artisan serve` - Start Laravel development server
- `php artisan queue:listen --tries=1` - Start queue worker
- `php artisan pail --timeout=0` - Start log viewer

### Testing
- `php artisan test` - Run all tests (PHPUnit/Pest)
- `php artisan test --testsuite=Unit` - Run unit tests only
- `php artisan test --testsuite=Feature` - Run feature tests only
- `php artisan test --testsuite=Modules` - Run module tests only
- `php artisan spec:test-coverage` - Check test coverage against specifications

### Code Quality
- `php artisan pint` - Format code with Laravel Pint
- `php artisan tinker` - Laravel REPL for debugging

### Database
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Run database seeders
- `php artisan migrate:fresh --seed` - Fresh migration with seeding

## Architecture Overview

This is a Laravel 11 application for the Corvallis Music Collective, a community-driven non-profit focused on making music creation accessible. The application uses a **modular architecture** with multiple app-modules that handle specific business domains.

### Tech Stack
- **Backend**: Laravel 11 with PHP 8.2+
- **Frontend**: Tailwind CSS, DaisyUI, AlpineJS, Livewire
- **Admin Panel**: Filament 3
- **Database**: PostgreSQL
- **Assets**: Vite for build tooling
- **Testing**: Pest (PHPUnit wrapper)

### Modular Structure
The application is organized into modules located in `app-modules/`, each handling a specific business domain:

**Core Modules:**
- `practice-space` - Room booking system, equipment management, calendar integration
- `band-profiles` - Band profile management and membership
- `finance` - Payment processing, products, and financial tracking
- `commerce` - Membership plans, pricing, and Stripe integration
- `community-calendar` - Event management and calendar system

**Additional Modules:**
- `analytics-insights` - Analytics and reporting
- `gear-inventory` - Equipment tracking and management
- `member-directory` - Member profiles and directory
- `productions` - Production and venue management
- `professional-services` - Professional service offerings
- `publications` - Publications and content management
- `resource-lists` - Resource and reference lists
- `sponsorship` - Sponsorship management
- `state-management` - Application state management
- `volunteer-management` - Volunteer coordination

### Database Conventions
- Tables are prefixed with module names (e.g., `practice_space_rooms`, `practice_space_bookings`)
- Uses PostgreSQL with Laravel migrations
- Includes comprehensive seeding for development environments

### Key Features
- **Multi-panel Filament setup**: Admin, Member, Band, and Sponsor panels
- **Stripe integration**: Payment processing with Laravel Cashier
- **Role-based permissions**: Using Spatie Laravel Permission
- **Activity logging**: Comprehensive audit trail
- **Recurring bookings**: Advanced booking system with RRULE support
- **Email notifications**: Comprehensive notification system
- **File uploads**: S3 integration for file storage

## Development Guidelines

### Module Development
- Each module should be self-contained in `app-modules/[module-name]/`
- Follow the existing module structure with `src/`, `database/`, `tests/`, `resources/` directories
- Tables should be prefixed with the module name
- Use module-specific service providers for registration

### Test-Driven Development
- Follow TDD principles: write tests first, then implement features
- Use `@test` and `@covers` annotations in PHPDoc comments
- Write regression tests for bug fixes
- Tests should cover Unit, Feature, and Module levels

### Community-Focused Design
- Prioritize community access over paywalls
- Implement cost-justified tiering based on actual resource usage
- Support volunteer credit systems
- Design for transparency and community benefit

### Code Standards
- Use Laravel conventions and best practices
- Follow PSR standards with Laravel Pint formatting
- Implement proper error handling and logging
- Write comprehensive tests for all features

## Important Notes

- The application uses internachi/modular for module management
- Filament panels are registered through dedicated providers
- State management uses Spatie Laravel Model States
- Testing environment uses a separate test database
- All modules support independent testing and development