# Portal

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)
[![License](https://img.shields.io/packagist/l/laravel/framework)](https://opensource.org/licenses/MIT)

Portal is a Laravel 12 application for client uploads, vendor processing, and admin operations. Authentication uses portal-number login with Telegram OTP. The product also uses guest links for client uploads, Cloudflare R2 for file storage, database-backed scheduling, and database queue workers for asynchronous jobs.

## What The App Does

- Clients upload documents through the dashboard or guest links, then track and download results.
- Vendors claim orders, upload plagiarism and AI reports, and manage earnings.
- Administrators manage users, guest links, billing, cleanup, retention, and system health.
- Telegram is used for login OTPs and operational notifications.

## Current Bootstrap And Auth Model

- Production login is portal-number plus Telegram OTP. Password login is not the primary production model.
- `php artisan db:seed` runs [DatabaseSeeder](database/seeders/DatabaseSeeder.php), which creates the bootstrap admin record with portal number `9001`.
- The bootstrap admin record is intended for fresh environments only. It does not represent a password-based production login flow.
- [ClientUserSeeder](database/seeders/ClientUserSeeder.php) is demo data. Do not run it in production unless you intentionally want sample client records.
- Scheduler configuration lives in [bootstrap/app.php](bootstrap/app.php), not in `app/Console/Kernel.php`.

## Local Development

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL

### Setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

### Optional Local Seeding

```bash
php artisan db:seed
```

This seeds the bootstrap admin record. If you need a demo client for local testing, run `ClientUserSeeder` separately and keep that data out of production.

### Useful Local Checks

```bash
php artisan app:health-check
php artisan storage:test-r2
```

## Production Requirements

Before launch, confirm the following are configured and running:

- `APP_ENV=production`
- `APP_DEBUG=false`
- correct `APP_URL`
- database credentials
- `FILESYSTEM_DISK=r2`
- R2 credentials and bucket settings
- Telegram bot token, bot username, chat IDs, and webhook secret
- `QUEUE_CONNECTION=database`
- queue worker process
- scheduler process or cron
- migrations applied
- health checks passing

For production setup and day-1 monitoring, use:

- [Deployment Guide](docs/DEPLOYMENT.md)
- [Maintenance & Operations](docs/MAINTENANCE.md)

## Documentation

- [Architecture Overview](docs/ARCHITECTURE.md)
- [Business Flows & Workflows](docs/BUSINESS-FLOWS.md)
- [Security Model](docs/SECURITY.md)
- [Deployment Guide](docs/DEPLOYMENT.md)
- [Local Development Setup](docs/LOCAL-DEVELOPMENT.md)
- [Maintenance & Operations](docs/MAINTENANCE.md)
- [Recent Changes & Known Issues](docs/RECENT-CHANGES.md)

## License

Portal is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
