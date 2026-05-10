# Security Model - PlagExpert (Portal)

This document details the security architecture of PlagExpert (Portal), a plagiarism checking and document processing service. Security is a core concern, and the system implements multiple layers of protection to ensure data integrity, user privacy, and operational safety.

## Overview of Security Approach

PlagExpert employs a multi-layered security model to protect against unauthorized access, data breaches, and operational disruptions. Key components include role-based access control (RBAC), comprehensive authorization policies, session management, OTP hardening, audit logging, and external integrations for bot protection.

## Roles and Permissions

The system defines three primary roles, each with distinct access levels and responsibilities:

- **Admin**: Full control over the platform, including user management, billing, pricing, and system configuration.
- **Vendor**: Can claim and process orders, upload reports, view earnings, and request payouts.
- **Client**: Can upload documents, track orders, manage credits, request top-ups, submit refund requests, and download reports.

### Role-Based Access Control (RBAC)

- **Route Middleware**: Routes are protected with middleware (`role:admin,vendor,client`) to restrict access based on user role. For example, `/admin/*` routes are accessible only to admins.
- **Role-Specific Dashboards**: Each role is redirected to its respective dashboard upon login (`/admin/dashboard`, `/dashboard`, `/client/dashboard`).
- **User Model Methods**: The `User` model includes methods like `isSuperAdmin()`, `canCreateAdmins()`, `canCreateVendors()`, and `canCreateClients()` to enforce role hierarchy.

## Authorization Policies

PlagExpert uses Laravel's policy system to define fine-grained access control rules for various entities. Policies are located in `app/Policies/` and govern actions at a granular level.

- **`OrderPolicy`**:
  - `claim()`: Vendors can claim unclaimed pending orders.
  - `unclaim()`: Only the claiming vendor can unclaim an order.
  - `process()`: Only the claiming vendor can start processing.
  - `uploadReport()`: Only the claiming vendor can upload reports for their orders.
  - `deliver()`: Only the claiming vendor can mark an order as delivered.
  - `delete()`: Clients can delete unclaimed pending orders; admins can delete any order.
- **`UserPolicy`**:
  - `create()`: Enforces role-specific creation gates. Vendor and client creation requires `role === 'admin'`. **Admin creation requires `isSuperAdmin()`** — regular admins cannot create other admins. This is enforced via `$this->authorize('create', [User::class, $role])` in `InviteController::store()` before any validation runs.
  - `freeze()`/`unfreeze()`: Admins can freeze or unfreeze accounts; only super-admins can freeze other admins.
  - `delete()`/`restore()`/`forceDelete()`: Admins can manage user deletion and restoration.
- **`ClientPolicy`**:
  - `viewAny()`/`view()`: Admins can view client details.
  - `updateSlots()`/`refill()`: Admins can adjust client credits.
- **`TopupRequestPolicy`** & **`RefundRequestPolicy`**:
  - `approve()`/`reject()`: Admins can approve or reject requests.
  - `create()`: Clients can create refund requests.
- **`VendorPayoutPolicy`**:
  - `viewAny()`/`create()`: Admins can manage vendor payouts.

### Policy Enforcement

Policies are enforced via Laravel's Gate and middleware, ensuring that users can only perform actions they are authorized for. Unauthorized attempts are rejected with HTTP 403 responses and logged for audit purposes.

## Authentication — OTP Login

PlagExpert uses a custom OTP-based login flow (`OtpLoginController`) backed by Telegram delivery.

### OTP Storage

OTPs are **never stored in plaintext**. Before writing to the database, the 6-digit code is hashed:

```php
$user->forceFill(['otp' => hash('sha256', $otp)])->save();
```

Verification hashes the submitted input and compares:

```php
User::where('otp', hash('sha256', $request->otp))->...
```

This means a database dump, backup exposure, or SQL injection cannot be used to replay a stolen OTP.

### OTP Brute-Force Protection

Failed OTP attempts are tracked per portal number (not per IP) using `RateLimiter`:

- **Limit**: 3 failures per portal number within a 10-minute window.
- **On exhaustion**: The OTP is immediately nulled in the database, so rotating IPs cannot continue guessing against the same code.
- **On fresh OTP**: The failure counter is cleared so a new code gives a clean slate.
- The existing `throttle:5,1` middleware (IP-based) remains as a secondary layer.

### Sensitive Fields Hidden from Serialisation

`otp` and `login_token` are included in `User::$hidden`, so they are never exposed in JSON responses even if a controller inadvertently returns a User model directly.

## Session Security

PlagExpert implements robust session management to prevent unauthorized access and session hijacking.

- **Database Sessions**: Sessions are stored in the database (`SESSION_DRIVER=database`), ensuring persistence and scalability across requests.
- **Midnight Session Expiry**: Sessions expire at midnight of the login day, enforced by `EnsureSessionIsFresh` middleware and a `session_expires_at` column on the user record.
- **CSRF Protection**: Laravel's built-in CSRF protection is active on all forms, with public endpoints providing token refresh (`/csrf-token-public`) under throttling (20 requests/minute). The Telegram webhook is excluded from CSRF.
- **Secure Cookies**: Session cookies can be configured as secure (`SESSION_SECURE_COOKIE=true` in production) to ensure transmission over HTTPS only.
- **No-Cache Middleware**: Applied to authenticated routes (`nocache` middleware) to prevent sensitive pages from being cached by browsers.

## Account Deletion Security

Account deletion is wrapped in a single `DB::transaction()` to prevent partial state on failure.

### Client Account Deletion

1. All active orders (pending/claimed/processing) are **cancelled**. Credits are **forfeited** — there is no slot restoration on account deletion.
2. All upload links are revoked (`is_active=false`, `revoked_at`, `revoked_by_user_id` recorded).
3. All pending refund requests are auto-rejected (`status=rejected`, `admin_note='Account deleted.'`).
4. All sessions are invalidated.
5. The user record is soft-deleted.

### Vendor Account Deletion

1. All claimed/processing orders are released back to pending (no orphaned work).
2. All sessions are invalidated.
3. The user record is soft-deleted.

### Financial Record Preservation

`vendor_payouts.user_id` and `vendor_payout_requests.user_id` use `nullOnDelete()` (not `cascadeOnDelete()`). A permanent `forceDelete()` sets these FKs to `NULL` rather than deleting the payout rows, preserving the financial audit trail.

## Portal Number Atomicity

Portal numbers are assigned via a dedicated `portal_number_sequences` table with a row per role. The sequence row is locked with `lockForUpdate()` inside the invite activation transaction, making concurrent activations serialise at the database level. A `UniqueConstraintViolationException` catch is also present as a last-resort safety net.

## Account Status Middleware

A custom middleware checks account status on every authenticated request:

- **Frozen Accounts**: Users with `isFrozen()` returning `true` are denied access and logged out with an appropriate error message.
- **Active Status**: `isActive()` ensures only active users can proceed, preventing access by unverified or suspended accounts.

## Cloudflare Turnstile Bot Protection

To protect public endpoints from bots and abuse:

- **Integration**: Cloudflare Turnstile is used for bot detection on public upload and tracking pages.
- **Validation**: The `TurnstileService` validates tokens (`TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` from environment) on form submissions.
- **Throttling**: Public routes are rate-limited (e.g., 30 requests/minute for upload endpoints) to further mitigate abuse.

## Audit Logging

Audit logging ensures traceability of critical actions for security and operational monitoring.

- **AuditLogger Service**: Logs events like order claims, status changes, account freezes, and deletion attempts to the `audit_logs` table.
- **Request Correlation**: Every request carries an `X-Request-Id` header (UUID v4), logged with each audit entry for end-to-end traceability.
- **Event Types**: Examples include `order.claimed`, `order.processing_started`, `account.frozen`, `account.deleted`, and denial events like `client_order.delete_denied`.
- **Sensitive Data Protection**: Logs are sanitized to prevent leakage of sensitive information (e.g., file contents, passwords). OTPs are never logged.

## Data Protection

- **File Storage**: Uploaded files and reports are stored on Cloudflare R2, with access restricted to authorized users only (via temporary signed URLs or direct download routes).
- **OTP Hashing**: OTPs are hashed with SHA-256 before storage. The plaintext code is only held in memory long enough to send via Telegram.
- **Input Validation**: Form requests and services validate input to prevent injection attacks; file uploads are sanitized for safe filenames.

## Best Practices Implemented

- **Least Privilege**: Policies ensure users have minimal necessary permissions, reducing the attack surface. Admin invite creation is super-admin gated.
- **OTP Hardening**: SHA-256 hashing, per-user brute-force lockout, and immediate nulling on exhaustion.
- **Transaction Atomicity**: Account deletion, refund approval, payout recording, and portal number assignment are all wrapped in database transactions.
- **Comprehensive Logging**: Audit logs cover all significant actions, with request IDs enabling correlation for debugging and incident response.
- **Session Hardening**: Midnight expiry and database storage prevent session fixation and improve user logout reliability.
- **Bot Mitigation**: Cloudflare Turnstile and throttling protect public endpoints from automated abuse.
- **Secure Defaults**: Environment variables encourage secure settings (e.g., `APP_DEBUG=false` in production).
- **Financial Record Integrity**: Vendor payout records survive account deletion via `nullOnDelete` FK behaviour.

## Known Security Considerations

- **Email Configuration**: Default mailer is set to `log` in development; production deployments must configure a proper mailer for OTP and notifications.
- **HTTPS Enforcement**: `SESSION_SECURE_COOKIE` should be enabled in production to ensure cookies are sent over secure connections.
- **Regular Key Rotation**: API keys and tokens (e.g., Telegram, Cloudflare) should be rotated periodically to minimize exposure risk.
- **OTP Migration Note**: Deployments upgrading from a version before OTP hashing was introduced will have any in-flight plaintext OTPs fail on first use. Users should simply request a new code — there is no data loss.
