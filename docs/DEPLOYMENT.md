# Deployment Guide

This guide describes how to deploy Portal in a production-like environment and what must be in place before the site is considered launch-ready.

## Deployment Model

Portal is designed to run with:

- portal-number + Telegram OTP authentication
- database-backed sessions
- database-backed queues
- scheduler definitions in [bootstrap/app.php](../bootstrap/app.php)
- Cloudflare R2 as the production file disk
- Telegram for login OTPs and operational notifications

## Production Requirements

Confirm the following before deploying:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` matches the real public URL
- database credentials are set and reachable
- `FILESYSTEM_DISK=r2`
- R2 credentials and bucket values are set
- Telegram bot token, bot username, admin chat ID, vendor chat ID, and webhook secret are set
- `QUEUE_CONNECTION=database`
- a queue worker is running
- the scheduler is running every minute
- migrations have been applied
- the health check command passes

## Required Environment Variables

Use the following as a production baseline:

```env
APP_NAME=Portal
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated-app-key>
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=<db-host>
DB_PORT=3306
DB_DATABASE=<db-name>
DB_USERNAME=<db-user>
DB_PASSWORD=<db-password>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_LEVEL=error

FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=<r2-access-key>
R2_SECRET_ACCESS_KEY=<r2-secret-key>
R2_BUCKET=<r2-bucket>
R2_ENDPOINT=<r2-endpoint>
R2_TOKEN=<r2-token>

TELEGRAM_BOT_TOKEN=<telegram-bot-token>
TELEGRAM_BOT_USERNAME=<telegram-bot-username>
TELEGRAM_VENDOR_CHAT_ID=<vendor-chat-id>
ADMIN_TELEGRAM_CHAT_ID=<admin-chat-id>
TELEGRAM_WEBHOOK_SECRET=<webhook-secret>
TELEGRAM_FAKE_IN_TESTS=false

TURNSTILE_SITE_KEY=<turnstile-site-key>
TURNSTILE_SECRET_KEY=<turnstile-secret-key>

MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=<smtp-port>
MAIL_USERNAME=<smtp-username>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME=Portal
```

## Fresh Bootstrap Behavior

On a fresh database:

- `php artisan migrate` creates the schema.
- `php artisan db:seed` runs [DatabaseSeeder](../database/seeders/DatabaseSeeder.php), which creates the bootstrap admin account with portal number `9001`.
- That bootstrap admin is the current production bootstrap path. It is not a password-based login.
- [ClientUserSeeder](../database/seeders/ClientUserSeeder.php) is demo-only. Do not use it for production bootstrap unless you intentionally want sample client data.

## Deployment Steps

1. Push the current code to the repository connected to your host.
2. Provision the application service, database, queue worker, and scheduler.
3. Set all required environment variables.
4. Run `php artisan migrate --force`.
5. Run `php artisan db:seed` only if you are intentionally bootstrapping a fresh environment.
6. Build frontend assets if your platform does not do it automatically.
7. Start or verify the queue worker process.
8. Start or verify the scheduler process.
9. Verify the health check and storage diagnostics.
10. Perform a real end-to-end upload, claim, report upload, and download flow in staging before production cutover.

## Queue And Scheduler Requirements

Portal has queued work and scheduled tasks. Production is not complete unless both are running.

- Queue worker:
  - use `php artisan queue:work` in production
  - keep the worker alive through your platform process manager
- Scheduler:
  - the schedule is defined in [bootstrap/app.php](../bootstrap/app.php)
  - on traditional servers, run `php artisan schedule:run` every minute from cron
  - on managed hosts, use the host scheduler or worker equivalent

Current scheduled tasks include:

- `orders:auto-release` every minute
- `app:cleanup-link-orders` hourly
- `app:close-day` daily at 23:59
- `app:purge-order-files` daily at 02:00
- database session pruning daily at 03:00

## Launch Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` matches the public URL
- [ ] database credentials are valid
- [ ] `FILESYSTEM_DISK=r2`
- [ ] R2 credentials and bucket values are set
- [ ] Telegram credentials are set
- [ ] `QUEUE_CONNECTION=database`
- [ ] queue worker is running
- [ ] scheduler is running
- [ ] migrations have been applied
- [ ] `php artisan app:health-check` passes
- [ ] `php artisan storage:test-r2` passes
- [ ] demo/test seeder data is not being loaded into production

## Day-1 Verification

After launch, verify these flows in the live environment:

- login with portal number and Telegram OTP
- client upload through dashboard and guest link
- vendor claim, report upload, and release flow
- client download and order tracking flow
- admin guest-link management
- admin cleanup and retention commands

## Troubleshooting

- Storage issues:
  - run `php artisan storage:test-r2`
  - confirm R2 env vars and the active filesystem disk
- Health issues:
  - run `php artisan app:health-check`
  - check database, storage, and Telegram connectivity
- Queue issues:
  - check the worker process and failed jobs table
- Scheduler issues:
  - confirm the minute-level scheduler trigger is running
- Telegram issues:
  - confirm bot token, bot username, chat IDs, and webhook secret
  - review application logs for Telegram exceptions and HTTP failures

## Non-Production Notes

- `APP_DEBUG=true` is acceptable only in local development.
- `ClientUserSeeder` is intended for local or demo use.
- If you need to test Telegram behavior locally, use the test fake setting rather than real network calls.
