# Local Development Setup - PlagExpert (Portal)

This guide provides step-by-step instructions for setting up a local development environment for PlagExpert (Portal), a plagiarism checking and document processing service built on Laravel 12. It covers installation, configuration, and testing to ensure developers can contribute effectively.

## Prerequisites

Before starting, ensure you have the following installed on your local machine:

- **PHP**: Version 8.2 or higher (with extensions: `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`).
- **Composer**: Dependency manager for PHP (version 2.x recommended).
- **Node.js and npm**: For frontend asset compilation (Node.js 16+ and npm 8+ recommended).
- **MySQL**: Database server (version 8.0+ or equivalent like MariaDB).
- **Git**: For version control and cloning the repository (if applicable).
- **Text Editor/IDE**: Visual Studio Code, PHPStorm, or any editor with Laravel support.

Optional but recommended:
- **Docker**: For containerized development if you prefer not to install PHP/MySQL locally.
- **Laravel Sail**: A lightweight Docker environment for Laravel (included as a dev dependency).

## Step-by-Step Setup

### 1. Clone the Repository (if applicable)

If you are working from a Git repository, clone it to your local machine:

```bash
git clone <repository-url>
cd portal
```

### 2. Install Dependencies

Install both PHP and JavaScript dependencies required for the project:

```bash
# Install PHP dependencies via Composer
composer install

# Install frontend dependencies via npm
npm install
```

### 3. Configure Environment Variables

Copy the example environment file and customize it for your local setup:

```bash
cp .env.example .env
```

Open `.env` in your editor and update the following key sections:

- **Application Settings**:
  ```env
  APP_NAME="Portal"
  APP_ENV=local
  APP_DEBUG=true
  APP_URL=http://localhost:8000
  APP_TIMEZONE=Asia/Kolkata
  ```

- **Database Settings**: Configure for your local MySQL database.
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=plagexpert
  DB_USERNAME=root
  DB_PASSWORD=<your-mysql-password>
  ```

- **Session, Cache, Queue**: Use database drivers for development.
  ```env
  SESSION_DRIVER=database
  CACHE_STORE=database
  QUEUE_CONNECTION=database
  ```

- **Storage**: Use local storage for development (Cloudflare R2 is for production).
  ```env
  FILESYSTEM_DISK=local
  ```

- **Mail**: Use log driver for development to avoid sending real emails.
  ```env
  MAIL_MAILER=log
  ```

- **Optional Integrations**: Leave Telegram and Turnstile variables empty or with dummy values for local development unless testing these features.
  ```env
  TELEGRAM_BOT_TOKEN=
  TURNSTILE_SITE_KEY=
  TURNSTILE_SECRET_KEY=
  ```

### 4. Generate Application Key

Generate a unique application key for security:

```bash
php artisan key:generate
```

### 5. Set Up Database

Create the database in MySQL and run migrations to set up the schema:

```bash
# Create database (via MySQL CLI or tool like phpMyAdmin)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS plagexpert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations to create tables
php artisan migrate
```

Optionally, seed the database with test data:

```bash
php artisan db:seed
```

This will create default users with credentials:
- Admin: `admin@example.com` / `password`
- Vendor: `vendor@example.com` / `password`

### 6. Build Frontend Assets

Compile frontend assets (CSS, JS) using Vite:

```bash
npm run dev
```

For continuous development with hot module replacement, keep this running in a separate terminal. Alternatively, build once for production-like assets:

```bash
npm run build
```

### 7. Start Development Server

Start the Laravel development server:

```bash
php artisan serve
```

By default, the application will be available at `http://localhost:8000`. If port 8000 is in use, specify a different port:

```bash
php artisan serve --port=8080
```

### 8. Access the Application

Open your browser and navigate to `http://localhost:8000` (or the port you specified). You should see the login page. Use the default credentials to log in as admin or vendor, or register a new client account if seeding wasn't performed.

## Development Workflow

### Running Multiple Services

For a full development experience, run the following in separate terminal windows:

- **Web Server**: `php artisan serve` (serves the application).
- **Frontend Dev Server**: `npm run dev` (watches for asset changes with hot reload).
- **Queue Worker**: `php artisan queue:listen --tries=1 --timeout=0` (processes background jobs).
- **Logs Tail**: `php artisan pail --timeout=0` (tails application logs in real-time).

Alternatively, use the `dev` script defined in `composer.json` to run all concurrently:

```bash
composer dev
```

### Creating New Features

- **Controllers**: Place in `app/Http/Controllers/` with appropriate namespace (e.g., `Admin/` for admin controllers).
- **Models**: Add to `app/Models/` and define relationships and methods as needed.
- **Services**: Encapsulate business logic in `app/Services/` (e.g., `OrderWorkflowService`).
- **Policies**: Define authorization in `app/Policies/` and register in `AuthServiceProvider`.
- **Routes**: Add to `routes/web.php` with appropriate middleware (e.g., `role:admin`).
- **Views**: Create Blade templates in `resources/views/` following the existing structure.
- **Frontend**: Add Tailwind-styled components or Alpine.js interactivity in `resources/js/` or `resources/css/`.

### Debugging

- **Enable Debug Mode**: Ensure `APP_DEBUG=true` in `.env` to see detailed error messages.
- **Logs**: Check `storage/logs/laravel.log` for errors or use `php artisan pail` for real-time log tailing.
- **Tinker**: Use `php artisan tinker` for interactive debugging and testing Eloquent queries or services.
- **X-Request-Id**: Every request includes an `X-Request-Id` header for log correlation; inspect via browser dev tools or logs.

## Testing Strategy

PlagExpert includes a testing setup to ensure code quality and prevent regressions.

### Running Tests

Tests are located in the `tests/` directory, split into `Unit/` and `Feature/` categories.

1. **Prepare Test Environment**:
   - Ensure a separate test database is configured in `.env.testing` (or use SQLite in memory).
   - Run `php artisan migrate --env=testing` to set up the test database schema.

2. **Execute Tests**:
   ```bash
   php artisan test
   ```
   Or use PHPUnit directly:
   ```bash
   ./vendor/bin/phpunit
   ```

3. **Key Test Areas**:
   - **Unit Tests**: Test individual components like services (`app/Services/`).
   - **Feature Tests**: Test end-to-end workflows like order creation, status transitions, and report uploads.
   - **Policy Tests**: Verify authorization rules in `app/Policies/`.

4. **Writing Tests**:
   - Use `php artisan make:test` to generate test skeletons.
   - Follow existing test patterns for consistency (e.g., mocking services, faking file uploads).
   - Test critical paths: order lifecycle, credit accounting, user permissions.

### Smoke Test Checklist

Refer to `docs/smoke-test-checklist.md` for a manual testing checklist covering critical user flows (client uploads, vendor processing, admin actions). This is useful for verifying local changes before committing.

## Common Development Issues & Solutions

- **Database Connection Failed**: Verify MySQL is running and `.env` credentials match your local setup. Use `php artisan config:clear` if cached configs are stale.
- **Permission Denied on Storage/Logs**: Ensure `storage/` and `bootstrap/cache/` are writable:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```
- **Frontend Assets Not Loading**: Ensure `npm run dev` is running or assets are built with `npm run build`. Check browser console for 404 errors on JS/CSS.
- **Queue Jobs Not Processing**: Start a queue worker with `php artisan queue:listen` if background tasks (e.g., notifications) are delayed.
- **Cloudflare R2 Not Needed Locally**: Set `FILESYSTEM_DISK=local` in `.env` to store files locally in `storage/app/public/` instead of R2.
- **Session Expiring Unexpectedly**: Adjust `SESSION_TIMEOUT_MINUTES` in `.env` for longer sessions during debugging.

## Using Laravel Sail (Docker Alternative)

If you prefer a containerized environment, use Laravel Sail, which is pre-configured in the project:

1. **Install Sail** (if not already set up):
   ```bash
   composer require laravel/sail --dev
   php artisan sail:install
   ```

2. **Start Sail Containers**:
   ```bash
   ./vendor/bin/sail up -d
   ```

3. **Run Commands in Sail**:
   - Migrations: `./vendor/bin/sail artisan migrate`
   - Server: Access at `http://localhost` (Sail maps port 80 by default).
   - Other commands: Prefix with `./vendor/bin/sail`, e.g., `./vendor/bin/sail npm run dev`.

4. **Stop Containers**:
   ```bash
   ./vendor/bin/sail down
   ```

Sail provides a pre-configured MySQL, Redis, and PHP environment, eliminating local dependency issues.

## Best Practices for Development

- **Follow Laravel Conventions**: Adhere to PSR-12 coding standards (use `vendor/bin/pint` for linting if configured).
- **Commit `.env.example` Updates**: If you add new environment variables, update `.env.example` with placeholder values for other developers.
- **Use Services for Logic**: Place business logic in `app/Services/` rather than controllers for reusability and testability.
- **Test Before Commit**: Run `php artisan test` to ensure your changes don't break existing functionality.
- **Audit Logging Awareness**: Remember that actions are logged to `audit_logs`; test log entries for new critical actions.
- **Mock External Services**: When testing, fake or mock Telegram and Cloudflare integrations to avoid real API calls.

## Contributing

Contributions are welcome! Follow these steps:

1. Create a feature branch: `git checkout -b feature/your-feature-name`.
2. Make changes and test locally.
3. Commit with descriptive messages: `git commit -m "Add feature X with Y functionality"`.
4. Run tests: `php artisan test`.
5. Push to repository: `git push origin feature/your-feature-name`.
6. Open a pull request with detailed description of changes and reference any related issues.

Refer to the [Laravel contribution guide](https://laravel.com/docs/contributions) for coding standards.

## Support

For local setup issues, reach out to the development team with details of your environment (PHP version, OS, error messages). Include relevant logs from `storage/logs/` or test failures if applicable.
