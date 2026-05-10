# Maintenance & Operations

This document covers the current operational model for Portal. It is written for operators who need to run the app in staging or production without guessing at command names or scheduler wiring.

## Where Scheduling Lives

Laravel scheduling is configured in `routes/console.php` (Laravel 11+ style — no `app/Console/Kernel.php`).

The current scheduled tasks are:

| Task | Cadence | Notes |
|---|---|---|
| `orders:auto-release` | Every minute | `withoutOverlapping()` — safe on multi-worker deployments |
| `app:cleanup-link-orders` | Hourly | Slot restoration is inside each order's transaction |
| `app:close-day` | Daily at 23:59 | Financial ledger — do not skip |
| `app:purge-order-files` | Daily at 02:00 | Only purges orders where `is_downloaded=true` |
| Database session pruning | Daily at 03:00 | Only runs when `session.driver=database` |

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
- `php artisan app:smoke-test`

## What Each Command Is For

- `app:health-check`
  - checks application health, database connectivity, and storage reachability
  - use it after deploys and during incident triage
- `storage:test-r2`
  - performs a real R2 read/write/delete diagnostic
  - use it when uploads, downloads, or report cleanup look suspicious
- `orders:auto-release`
  - releases stalled orders back into the queue
  - scheduler-driven safety net for stuck work
  - protected with `withoutOverlapping()` to prevent concurrent runs
- `app:cleanup-link-orders`
  - cleans up expired guest-link orders and restores slot credits atomically
  - keeps guest-link lifecycle data from accumulating forever
- `app:close-day`
  - closes the daily ledger with per-vendor payout rates
  - part of financial ops and should not be skipped
- `app:purge-order-files`
  - removes stored files for delivered orders that have been downloaded
  - orders where `is_downloaded=false` are skipped to prevent premature deletion
- `app:repair-missing-reports`
  - scans delivered orders for missing report rows and can rebuild them from storage
  - manual recovery tool, not a routine daily task
- `app:delete-orders`
  - deletes specific orders using the application cleanup service
  - use only for controlled manual cleanup
- `admin:promote-super`
  - promotes an admin account to system root
  - use only for emergency access or initial bootstrap
- `app:smoke-test`
  - post-deploy sanity check for queue, cache, storage, and Telegram config
  - run after every production deploy before sending traffic

## Production Runbook

### Before Launch

- confirm `APP_ENV=production`
- confirm `APP_DEBUG=false`
- confirm `APP_URL` is correct
- confirm database credentials are valid
- confirm `FILESYSTEM_DISK=r2`
- confirm R2 credentials are valid
- confirm Telegram env vars are populated (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, `TELEGRAM_VENDOR_CHAT_ID`, `ADMIN_TELEGRAM_CHAT_ID`, `TELEGRAM_WEBHOOK_SECRET`)
- confirm `QUEUE_CONNECTION=redis` (or `database` as fallback)
- confirm `CACHE_STORE=redis` (or `database` as fallback)
- confirm the queue worker is running
- confirm the scheduler is running every minute
- confirm migrations have been applied (`php artisan migrate --force`)
- confirm `php artisan app:health-check` passes
- confirm `php artisan storage:test-r2` passes
- confirm `php artisan app:smoke-test` passes
- confirm demo/test seeders are not being applied to production

### Day-1 Monitoring Checklist

- Telegram failures
  - review logs for send failures, HTTP errors, and webhook problems
- Storage errors
  - watch for upload, download, and file deletion failures
- Queue failures
  - review failed jobs and confirm the worker is processing jobs
- Scheduler failures
  - confirm scheduled tasks are executing on time (especially `app:close-day`)
- Health checks
  - run `php artisan app:health-check` after deploy and again after traffic starts
- First real workflow
  - verify client upload, vendor claim, report upload, and client download
- Audit logs
  - review admin and system audit entries for unexpected failures or denied actions
- OTP delivery
  - verify a test OTP is delivered and the hash-based comparison works end-to-end

## Operational Checks

### Queue

Portal uses a database or Redis queue. Keep a worker process alive in production.

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

On managed platforms (Railway, Fly.io), use the host scheduler or worker equivalent at one-minute cadence.

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

### OTP Login Issues

If users cannot log in:

1. Confirm `TELEGRAM_BOT_TOKEN` is valid and the bot is not blocked.
2. Check logs for `auth.otp.delivery_failed` — this means Telegram rejected the send.
3. Check logs for `auth.otp.verify_failed` — this means the submitted code did not match.
4. If a user is locked out after 3 failed attempts, they must request a new OTP. The lock decays after 10 minutes or is cleared when a fresh OTP is issued.
5. If upgrading from a pre-hashing deployment: any in-flight plaintext OTPs will fail on first verify. Users should request a new code.

### Account Deletion

Account deletion is atomic and irreversible for the following side effects:

- **Client**: active orders are cancelled (credits forfeited), upload links are revoked, pending refund requests are auto-rejected.
- **Vendor**: claimed/processing orders are released back to pending.

There is no credit restoration on deletion by design. If an operator needs to credit a client before deleting their account, do so manually via the admin panel first.

## Demo Data and Bootstrap Data

- `DatabaseSeeder` creates the bootstrap admin record for a fresh environment.
- `ClientUserSeeder` is demo-only and should not be treated as production seed data.
- Do not import local demo data into production unless there is a deliberate operational reason.

## Troubleshooting

- If uploads fail:
  - check storage config and run `storage:test-r2`
- If login fails:
  - check Telegram settings, portal-number entry, and OTP delivery logs; see OTP section above
- If reports stop appearing:
  - check the queue worker, scheduler, and report upload flow
- If ledger close does not run:
  - check the scheduler and the `app:close-day` command output
- If cleanup does not run:
  - check scheduler execution and `app:cleanup-link-orders` / `app:purge-order-files` commands
- If vendor payout requests pile up:
  - check the Finance → Payouts admin page; pending requests are surfaced there
- If portal number assignment fails with "activation conflict":
  - this is the `UniqueConstraintViolationException` safety net firing; the user should retry the invite link; investigate `portal_number_sequences` table for corruption if it recurs

## Notes for Operators

- Keep production logs at error level or equivalent.
- Keep `APP_DEBUG` disabled in production.
- Review the audit log regularly during the first few days after launch.
- If you need to verify Telegram behavior in local testing, use the test fake setting instead of real network calls.
- The `portal_number_sequences` table is the source of truth for next portal numbers. Do not manually edit it unless correcting a data issue, and only do so with the application stopped.
