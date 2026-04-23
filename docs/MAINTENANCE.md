# Maintenance & Operations

This document covers the current operational model for Portal. It is written for operators who need to run the app in staging or production without guessing at command names or scheduler wiring.

## Where Scheduling Lives

Laravel scheduling is configured in [bootstrap/app.php](../bootstrap/app.php), not in `app/Console/Kernel.php`.

The current scheduled tasks are:

- `orders:auto-release` every minute
- `app:cleanup-link-orders` hourly
- `app:close-day` daily at 23:59
- `app:purge-order-files` daily at 02:00
- database session pruning daily at 03:00

## Current Artisan Commands

Use these exact command names:

- `php artisan app:health-check`
- `php artisan storage:test-r2`
- `php artisan orders:auto-release`
- `php artisan app:cleanup-link-orders`
- `php artisan app:close-day`
- `php artisan app:purge-order-files`
- `php artisan app:repair-missing-reports`
- `php artisan app:delete-orders`
- `php artisan admin:promote-super`

## What Each Command Is For

- `app:health-check`
  - checks application health, database connectivity, and storage reachability
  - use it after deploys and during incident triage
- `storage:test-r2`
  - performs a real R2 read/write/delete diagnostic
  - use it when uploads, downloads, or report cleanup look suspicious
- `orders:auto-release`
  - releases stalled orders back into the queue
  - this is a scheduler-driven safety net for stuck work
- `app:cleanup-link-orders`
  - cleans up outdated guest-link orders
  - keeps guest-link lifecycle data from accumulating forever
- `app:close-day`
  - closes the daily ledger
  - this is part of financial ops and should not be skipped
- `app:purge-order-files`
  - removes old stored files according to retention rules
  - use this to control storage growth and retention compliance
- `app:repair-missing-reports`
  - scans delivered orders for missing report rows and can rebuild them from storage
  - manual recovery tool, not a routine daily task
- `app:delete-orders`
  - deletes specific orders using the application cleanup service
  - use only for controlled manual cleanup
- `admin:promote-super`
  - promotes an admin account to system root
  - use only for emergency access or initial bootstrap

## Production Runbook

### Before Launch

- confirm `APP_ENV=production`
- confirm `APP_DEBUG=false`
- confirm `APP_URL` is correct
- confirm database credentials are valid
- confirm `FILESYSTEM_DISK=r2`
- confirm R2 credentials are valid
- confirm Telegram env vars are populated
- confirm `QUEUE_CONNECTION=database`
- confirm the queue worker is running
- confirm the scheduler is running every minute
- confirm migrations have been applied
- confirm `php artisan app:health-check` passes
- confirm `php artisan storage:test-r2` passes
- confirm demo/test seeders are not being applied to production

### Day-1 Monitoring Checklist

- Telegram failures
  - review logs for send failures, HTTP errors, and webhook problems
- Storage errors
  - watch for upload, download, and file deletion failures
- Queue failures
  - review failed jobs and confirm the worker is processing jobs
- Scheduler failures
  - confirm scheduled tasks are still executing on time
- Health checks
  - run `php artisan app:health-check` after deploy and again after traffic starts
- First real workflow
  - verify client upload, vendor claim, report upload, and client download
- Audit logs
  - review admin and system audit entries for unexpected failures or denied actions

## Operational Checks

### Queue

Portal uses a database queue. Keep a worker process alive in production.

Recommended production pattern:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=60
```

If you restart the app or deploy new code, restart the worker as well.

### Scheduler

On traditional servers, run:

```bash
* * * * * cd /path/to/portal && php artisan schedule:run >> /dev/null 2>&1
```

On managed platforms, use the host scheduler or worker equivalent, but keep the execution cadence at one minute.

### Storage

When storage is suspected:

1. Run `php artisan storage:test-r2`
2. Verify the R2 environment variables
3. Confirm `FILESYSTEM_DISK=r2`
4. Check application logs for the exact storage exception

### Telegram

When Telegram is suspected:

1. Verify `TELEGRAM_BOT_TOKEN`
2. Verify `TELEGRAM_BOT_USERNAME`
3. Verify `TELEGRAM_VENDOR_CHAT_ID`
4. Verify `ADMIN_TELEGRAM_CHAT_ID`
5. Verify `TELEGRAM_WEBHOOK_SECRET`
6. Check logs for Telegram HTTP failures or exception messages

## Demo Data And Bootstrap Data

- `DatabaseSeeder` creates the bootstrap admin record for a fresh environment.
- `ClientUserSeeder` is demo-only and should not be treated as production seed data.
- Do not import local demo data into production unless there is a deliberate operational reason.

## Troubleshooting

- If uploads fail:
  - check storage config and run `storage:test-r2`
- If login fails:
  - check Telegram settings, portal-number entry, and OTP delivery logs
- If reports stop appearing:
  - check the queue worker, scheduler, and report upload flow
- If ledger close does not run:
  - check the scheduler and the `app:close-day` command output
- If cleanup does not run:
  - check scheduler execution and the `app:cleanup-link-orders` / `app:purge-order-files` commands

## Notes For Operators

- Keep production logs at error level or equivalent.
- Keep `APP_DEBUG` disabled in production.
- Review the audit log regularly during the first few days after launch.
- If you need to verify Telegram behavior in local testing, use the test fake setting instead of real network calls.
