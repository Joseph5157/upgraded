# Architecture Overview - PlagExpert (Portal)

This document provides a detailed overview of the technical architecture and structure of PlagExpert (Portal), a plagiarism checking and document processing service built on Laravel 12.

## Tech Stack

- **Backend Framework**: Laravel 12
- **Programming Language**: PHP 8.4
- **Database**: MySQL with database-driven sessions
- **Storage**: Cloudflare R2 (default filesystem for file uploads and reports)
- **Cache / Queue**: Redis (database fallback supported)
- **Frontend**: Laravel Blade templating, Tailwind CSS for styling, Alpine.js for interactivity, bundled with Vite
- **Key Dependencies**:
  - `laravel/framework`: ^12.0
  - `league/flysystem-aws-s3-v3`: For Cloudflare R2 integration
  - `predis/predis`: Pure-PHP Redis client (used when phpredis extension is unavailable)
  - Frontend tools: `@tailwindcss/forms`, `axios`, `alpinejs`

## System Architecture

PlagExpert follows a typical Laravel MVC architecture enhanced with a service layer for business logic, comprehensive policies for authorization, and enums for state management. The system is designed for scalability and security, leveraging Cloudflare services and Telegram integrations.

### High-Level Flow

1. **Client Interaction**: Clients upload PDF/DOC files via a dashboard or public links, consuming slot-based credits.
2. **Vendor Processing**: Vendors claim orders, process documents, and upload AI and plagiarism reports.
3. **Admin Oversight**: Admins manage users, pricing, billing, and system health through a dedicated dashboard.
4. **External Integrations**:
   - **Cloudflare R2**: Stores uploaded files and reports.
   - **Cloudflare Turnstile**: Provides bot protection for public endpoints.
   - **Telegram**: Facilitates OTP login, invite activation, and notifications.

### Key Components

- **Controllers**: Handle HTTP requests and user input (e.g., `DashboardController`, `ClientDashboardController`, `AdminDashboardController`).
- **Models**: Represent data entities with Eloquent ORM (e.g., `Order`, `Client`, `User`, `OrderReport`).
- **Services**: Encapsulate business logic (e.g., `CreateClientOrderService`, `OrderWorkflowService`, `PortalTelegramAlertService`).
- **Policies**: Define authorization rules (e.g., `OrderPolicy`, `UserPolicy`).
- **Enums**: Manage predefined states (e.g., `OrderStatus` with states `pending`, `claimed`, `processing`, `delivered`, `cancelled`).
- **Artisan Commands**: Handle maintenance and operational tasks (e.g., `AutoReleaseOrdersCommand`, `CloseDayCommand`).
- **Middleware**: Enforce security and session management (e.g., `EnsureSessionIsFresh`, `CheckAccountStatus`).

## Folder Structure

The project adheres to Laravel's conventional directory structure with additional organisation for clarity:

- **`app/Console/Commands/`**: Custom Artisan commands for system maintenance.
- **`app/Enums/`**: Enumerations for state management. Key file: `OrderStatus.php`.
- **`app/Http/Controllers/`**: Request handling logic segmented by user role.
- **`app/Models/`**: Eloquent models representing database entities.
- **`app/Policies/`**: Authorization logic for various entities.
- **`app/Services/`**: Business logic abstracted from controllers.
- **`config/`**: Configuration files for Laravel components and integrations.
- **`database/migrations/`**: Ordered schema migrations. All `2026_05_10_*` migrations must run before the system is usable on a fresh install.
- **`routes/web.php`**: Main route definitions. Route groups by role: `role:vendor,admin`, `role:client`, `role:admin`.
- **`routes/console.php`**: Scheduler definitions (Laravel 11+ style — no `Kernel.php`).

## Key Models & Relationships

### Core Models

- **`User`**: Represents all system users (admins, vendors, clients).
  - `client()` belongsTo `Client` (if role is client).
  - `orders()` hasMany `Order` (as vendor/creator).
  - Key fields: `portal_number` (unique, assigned via sequence table), `otp` (SHA-256 hash, hidden from serialisation), `login_token` (hidden), `payout_rate`, `delivered_orders_count`, `daily_delivered_count`.

- **`Client`**: Client accounts with slot-based credits.
  - `user()` hasOne `User`.
  - `orders()` hasMany `Order`.
  - `links()` hasMany `ClientLink`.
  - `topupRequests()` hasMany `TopupRequest`.
  - `refundRequests()` hasMany `RefundRequest`.
  - Key fields: `slots` (credit ceiling), `slots_consumed`, `status`.

- **`Order`**: Core entity for a document processing request.
  - `client()` belongsTo `Client`.
  - `vendor()` belongsTo `User` (as `claimed_by`).
  - `files()` hasMany `OrderFile`.
  - `report()` hasOne `OrderReport`.
  - `refundRequest()` hasOne `RefundRequest`.
  - Key fields: `status` (OrderStatus enum), `is_downloaded`, `delivered_at`, `files_count`.

- **`OrderReport`**: Stores AI and plagiarism reports.
  - `order()` belongsTo `Order`.

- **`ClientLink`**: Public upload links for clients.
  - `client()` belongsTo `Client`.
  - `orders()` hasMany `Order`.
  - Key fields: `is_active`, `revoked_at`, `revoked_by_user_id`.

### Financial Models

- **`TopupRequest`**: Client credit top-up requests.
  - `client()` belongsTo `Client`.
  - Key fields: `amount_requested` (slots), `amount_paid` (rupees, nullable), `transaction_id`, `status`.

- **`RefundRequest`**: Client refund requests for specific orders.
  - `order()` belongsTo `Order`.
  - `client()` belongsTo `Client`.
  - Key fields: `reason`, `status`, `admin_note`, `resolved_at`.

- **`VendorPayout`**: Records of payments made to vendors.
  - `vendor()` belongsTo `User` (FK is `nullOnDelete` — record survives account deletion).
  - Key fields: `amount`, `reference_id`, `paid_at`.

- **`VendorPayoutRequest`**: Vendor-initiated payout requests.
  - `vendor()` belongsTo `User` (FK is `nullOnDelete`).
  - Key fields: `amount_requested`, `status` (pending/fulfilled/rejected), `fulfilled_at`.

### Ledger Models

- **`DailyLedger`**: Nightly financial snapshot.
  - Key fields: `total_revenue`, `vendor_payouts`, `net_profit`, `client_breakdown` (JSON), `vendor_breakdown` (JSON with per-vendor `rate` key).

- **`VendorDailySnapshot`**: Per-vendor nightly snapshot used for earnings history.
  - Key fields: `orders_delivered`, `amount_earned`, `date`.

### System Models

- **`AuditLog`**: System event records for traceability.
- **`PendingInvite`**: Invite tokens for Telegram-based account activation.
- **`portal_number_sequences`** (table, no model): One row per role (`client`, `vendor`, `admin`) holding the next portal number. Locked with `lockForUpdate()` during invite activation to prevent race conditions.

## Important Services

- **`AuditLogger`**: Logs system events with request correlation (`X-Request-Id`) to `audit_logs`.
- **`CreateClientOrderService`**: Order creation with file upload, credit deduction, and slot validation.
- **`OrderWorkflowService`**: Order state transitions (claim, unclaim, startProcessing, uploadReport, deliver). Delivery counter increments go through a shared private `markDelivered()` method — double-incrementing is structurally impossible.
- **`PortalTelegramAlertService`**: Sends Telegram alerts for key events:
  - `notifyOrderAccepted()` — vendor group + client direct.
  - `notifyOrderCompleted()` — client direct.
  - `notifyTopupSubmitted()` / `notifyTopupApproved()` — admin / client.
  - `notifyVendorPayoutRequested()` — admin (vendor self-service payout request).
  - All methods follow the same routing pattern: env-configured `admin_chat_id` → DB admin fallback.
- **`TelegramService`**: Raw Telegram Bot API wrapper.
- **`TurnstileService`**: Validates Cloudflare Turnstile tokens.
- **`NotificationService`**: Order status-change notifications.
- **`StorageLifecycle`**: Centralized file deletion helpers for R2 and local disk.

## Artisan Commands

| Command | When to run |
|---|---|
| `app:health-check` | After every deploy and during incident triage |
| `storage:test-r2` | When uploads or downloads look suspicious |
| `orders:auto-release` | Scheduler-only (every minute, `withoutOverlapping`) |
| `app:cleanup-link-orders` | Scheduler-only (hourly) |
| `app:close-day` | Scheduler-only (23:59 daily) — financial, do not skip |
| `app:purge-order-files` | Scheduler-only (02:00 daily) — only purges downloaded orders |
| `app:repair-missing-reports` | Manual recovery, not routine |
| `app:delete-orders` | Controlled manual cleanup only |
| `admin:promote-super` | Emergency access or initial bootstrap |
| `app:smoke-test` | Post-deploy sanity check |

## Middleware Stack

Applied to the `web` group globally (via `bootstrap/app.php`):

- `RequestCorrelation` — attaches `X-Request-Id` UUID to every request.
- `EnsureSessionIsFresh` — logs out sessions past midnight expiry. Passes through unauthenticated requests.
- `CheckAccountStatus` — logs out frozen users. Passes through unauthenticated requests.

Route-group specific:
- `nocache` — sets `Cache-Control: no-store` on authenticated pages.
- `role:X` — enforces user role.
- `account.status` — explicit freeze check (applied per group; also in global stack).

## Database Transaction Boundaries

The following operations are fully atomic:

| Operation | Scope |
|---|---|
| Invite activation (portal number + user creation) | Single `DB::transaction()` + sequence lock |
| Order claim | Per-order `lockForUpdate()` in transaction |
| Report upload + delivery | Per-order `lockForUpdate()` in transaction |
| Refund approval | Slot decrement + status update in transaction |
| Account deletion | All side effects in one transaction (orders, links, refunds, sessions, delete) |
| Link order cleanup | Per-order transaction including slot restoration |
| Topup approval | Client slot update + request status in transaction |
| Payout recording | Balance check then insert (no transaction currently — single write is atomic) |

## Best Practices Implemented

- **Service Layer**: Business logic in services, controllers stay thin.
- **Comprehensive Policies**: Fine-grained access control via `app/Policies/`.
- **OTP Hardening**: SHA-256 hashing, per-user brute-force lockout, `otp` hidden from JSON.
- **Atomic Operations**: All multi-step mutations wrapped in transactions.
- **Enum Usage**: `OrderStatus` prevents invalid state strings.
- **Database Sessions + Redis Cache**: Persistence and scalability.
- **Financial Integrity**: `nullOnDelete` FKs preserve payout records after account deletion.
- **No Disk Write for ZIP**: `bundleReports()` uses `tmpfile()` — safe on ephemeral filesystems.
