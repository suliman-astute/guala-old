# Guala-App Project Overview

## Technology Stack

### Backend Framework
- **Framework**: Laravel 12.x
- **PHP Version**: ^8.2 (PHP 8.2 or higher)
- **Server**: Laravel built-in server or Apache/Nginx

### Frontend Framework
- **Build Tool**: Vite 6.x
- **CSS Framework**: TailwindCSS 4.x with Bootstrap 5.2.3
- **JavaScript**: Vanilla JS with Axios for HTTP requests
- **UI Grid**: ag-grid-community 33.x (advanced data tables)
- **CSS Preprocessor**: Sass 1.56.1

### Database
- **Default**: SQLite (lightweight development)
- **Alternative**: MySQL/MariaDB (configured but commented in .env.example)
- **Session Storage**: Database-driven
- **Cache**: Database-driven

### Key Libraries & Packages

**Backend (PHP)**
- `laravel/framework` (^12.0) - Core Laravel framework
- `laravel/ui` (^4.6) - UI scaffolding & authentication
- `laravel/tinker` (^2.10.1) - REPL for Laravel
- `laravel-adminlte` (^3.15) - Admin dashboard template
- `ldaprecord-laravel` (^3.4) - LDAP integration for Active Directory
- `spatie/laravel-activitylog` (^4.10) - Activity logging
- `picqer/php-barcode-generator` (^3.2) - Barcode generation

**Frontend (NPM)**
- `axios` (^1.7.4) - HTTP client for API calls
- `@tailwindcss/vite` (^4.0.0) - TailwindCSS Vite plugin
- `@popperjs/core` (^2.11.6) - Positioning library for dropdowns/tooltips
- `ag-grid-community` (^33.3.1) - Free version of ag-Grid data tables
- `concurrently` (^9.0.1) - Run multiple npm scripts simultaneously

**Development**
- `phpunit/phpunit` (^11.5.3) - PHP testing framework
- `laravel/pint` (^1.13) - Code style fixer
- `laravel/pail` (^1.2.2) - Log viewer
- `laravel/sail` (^1.41) - Docker environment
- `fakerphp/faker` (^1.23) - Fake data generation
- `mockery/mockery` (^1.6) - Mocking library
- `nunomaduro/collision` (^8.6) - Error handler

## Installation Requirements

### System Requirements
- **PHP**: 8.2 or higher
- **Composer**: Latest version (for PHP dependency management)
- **Node.js**: v16+ recommended (for npm packages)
- **npm**: v8+ (comes with Node.js)

### Initial Setup Steps
1. Install PHP dependencies: `composer install`
2. Install Node dependencies: `npm install`
3. Copy environment file: `cp .env.example .env`
4. Generate application key: `php artisan key:generate`
5. Run database migrations: `php artisan migrate`
6. Start development server: `composer run dev` (runs Laravel + Vite + Queue + Logs in parallel)

### Database
- SQLite is pre-configured for local development
- For production, configure MySQL in `.env`: `DB_CONNECTION=mysql`
- Session and cache both use the database for storage

## Folder Structure

### `/app` - Application Logic
- **Controllers/**: Handle incoming requests and return responses
- **Models/**: Database models representing entities (User, Turno, Aziende, Stamping, Macchine, Presse, etc.)
- **Listeners/**: Event handlers (login/logout logging, dictionary updates)
- **Console/Commands/**: Custom CLI commands
- **Http/Middleware/**: Request/response interceptors
- **Providers/**: Service container registration and bootstrapping

### `/resources` - Frontend Assets
- **views/**: Blade template files (Laravel's templating engine)
- **js/**: JavaScript files compiled by Vite
- **css/**: CSS and Sass stylesheets
- **sass/**: Sass files for styling

### `/routes` - URL Routing
- **web.php**: Web routes with Blade view rendering
- **console.php**: CLI command routing

### `/database` - Database Management
- **migrations/**: Version-controlled database schema changes
- **seeders/**: Database population scripts for development/testing
- **factories/**: Test data generation factories

### `/config` - Application Configuration
- **app.php**: Core application settings
- **auth.php**: Authentication configuration
- **database.php**: Database connection settings
- **ldap.php**: LDAP/Active Directory configuration
- **mail.php**: Email service configuration
- **cache.php**, **queue.php**, **session.php**: Service configurations
- **adminlte.php**: Admin dashboard theme configuration
- **logging.php**: Log channel setup
- **dizionario.php**: Custom dictionary/translation configuration

### `/public` - Web Root
- **index.php**: Laravel application entry point
- **build/**: Vite-compiled assets (CSS, JS)
- **images/**, **favicons/**: Static assets
- **vendor/**: Third-party frontend libraries

### `/vendor` - PHP Dependencies
- Composer-managed PHP libraries and frameworks

### `/node_modules` - JavaScript Dependencies
- npm-managed JavaScript libraries

### `/bootstrap` - Framework Bootstrapping
- **app.php**: Application instance bootstrap
- **providers.php**: Service provider loading
- **cache/**: Compiled configuration cache

### `/storage` - Application Storage
- **app/**: File uploads and user-generated files
- **framework/**: Framework-generated files (cache, sessions, views)
- **logs/**: Application logs

### `/tests` - Test Suite
- **Unit/**: Unit tests for individual components
- **Feature/**: Feature tests for application workflows
- **TestCase.php**: Base test class

### `/scripts` - Utility Scripts
- **assemblaggio_aligner.php**: Custom business logic script
- **db_aligner.php**: Database utility script

### `/api` - API Layer
- **BusinessCentralApi.php**: Integration with Business Central ERP system

### `/classes` - Custom PHP Classes
- **Database.class.php**: Custom database abstraction layer

### `/lang` - Localization
- Translation strings for multiple languages
- **vendor/**: Third-party language packs (AdminLTE)

## Notable Features
- **LDAP/Active Directory Integration**: User authentication via corporate directory
- **Activity Logging**: All user actions are logged for audit trails
- **Barcode Generation**: Built-in barcode/QR code generation
- **Admin Dashboard**: AdminLTE-based dashboard for data management
- **Data Tables**: ag-Grid integration for advanced filtering and sorting
- **Real-time Development**: Concurrent server, queue, logs, and Vite dev server

## Key Models (Business Logic)
The application appears to be a manufacturing/production management system with models for:
- **Turno** (Shifts), **GestioneTurni** (Shift Management)
- **Presse** (Presses), **PresseGualaFP** (Guala-specific press configuration)
- **Macchine** (Machines), **MacchineOperatori** (Machine Operators)
- **Stamping** (Stamping/Production stamps)
- **Ordini** (Orders)
- **Aziende** (Companies/Organizations)
- **CodiciOggetto** (Object Codes)
- **ProductionFP** (Production records)
- **ExtInfos** (Extended Information)

This suggests the application is designed for manufacturing facility management with shift scheduling, equipment management, and production tracking.
