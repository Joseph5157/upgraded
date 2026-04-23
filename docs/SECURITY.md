# Security Model - PlagExpert (Portal)

This document details the security architecture of PlagExpert (Portal), a plagiarism checking and document processing service. Security is a core concern, and the system implements multiple layers of protection to ensure data integrity, user privacy, and operational safety.

## Overview of Security Approach

PlagExpert employs a multi-layered security model to protect against unauthorized access, data breaches, and operational disruptions. Key components include role-based access control (RBAC), comprehensive authorization policies, session management, audit logging, and external integrations for bot protection.

## Roles and Permissions

The system defines three primary roles, each with distinct access levels and responsibilities:

- **Admin**: Full control over the platform, including user management, billing, pricing, and system configuration.
- **Vendor**: Can claim and process orders, upload reports, and view earnings.
- **Client**: Can upload documents, track orders, manage credits, and download reports.

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
  - `create()`: Admins with specific permissions can create users of certain roles.
  - `freeze()`/`unfreeze()`: Admins can freeze or unfreeze accounts.
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

## Session Security

PlagExpert implements robust session management to prevent unauthorized access and session hijacking.

- **Database Sessions**: Sessions are stored in the database (`SESSION_DRIVER=database`), ensuring persistence and scalability across requests.
- **Custom Session Timeout**: Configurable absolute session timeout (`SESSION_TIMEOUT_MINUTES=480` by default, equating to 8 hours) logs users out after inactivity or a fixed duration.
- **CSRF Protection**: Laravel's built-in CSRF protection is active on all forms, with public endpoints providing token refresh (`/csrf-token-public`) under throttling (20 requests/minute).
- **Secure Cookies**: Session cookies can be configured as secure (`SESSION_SECURE_COOKIE=true` in production) to ensure transmission over HTTPS only.
- **No-Cache Middleware**: Applied to authenticated routes (`nocache` middleware) to prevent sensitive pages from being cached by browsers.

## Account Status Middleware

A custom middleware checks account status on every authenticated request:

- **Frozen Accounts**: Users with `isFrozen()` returning true are denied access and logged out with an appropriate error message.
- **Active Status**: `isActive()` ensures only active users can proceed, preventing access by unverified or suspended accounts.
- **Audit Logging**: Access denials due to account status are logged for traceability.

## Cloudflare Turnstile Bot Protection

To protect public endpoints from bots and abuse:

- **Integration**: Cloudflare Turnstile is used for bot detection on public upload and tracking pages.
- **Validation**: The `TurnstileService` validates tokens (`TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` from environment) on form submissions.
- **Throttling**: Public routes are rate-limited (e.g., 30 requests/minute for upload endpoints) to further mitigate abuse.

## Audit Logging

Audit logging ensures traceability of critical actions for security and operational monitoring.

- **AuditLogger Service**: Logs events like order claims, status changes, account freezes, and deletion attempts to the `audit_logs` table.
- **Request Correlation**: Every request carries an `X-Request-Id` header (UUID v4), logged with each audit entry for end-to-end traceability.
- **Event Types**: Examples include `order.claimed`, `order.processing_started`, `account.frozen`, `credits.restored`, and denial events like `client_order.delete_denied`.
- **Sensitive Data Protection**: Logs are sanitized to prevent leakage of sensitive information (e.g., file contents, passwords).

### Key Audit Log Features

- **Structured Data**: Logs include `user_id`, `subject_type`, `subject_id`, and a `meta` field for contextual data (e.g., status transitions).
- **Denial Logging**: Failed authorization attempts (e.g., attempting to delete a claimed order) are logged with reasons for forensic analysis.
- **Observability**: Application logs (non-audit) capture operational warnings like `order.create_failed` without sensitive data.

## Data Protection

- **File Storage**: Uploaded files and reports are stored on Cloudflare R2, with access restricted to authorized users only (via temporary signed URLs or direct download routes).
- **Encryption**: Database fields are not encrypted at rest by default, but sensitive configuration (e.g., API keys) in `.env` should be protected at the environment level.
- **Input Validation**: Form requests and services validate input to prevent injection attacks; file uploads are sanitized for safe filenames.

## Authentication

- **OTP Login**: Custom OTP-based login (`OtpLoginController`) often integrated with Telegram for seamless authentication.
- **Email Verification**: Configurable per user role (`requiresEmailVerification()` on `User` model).
- **Password Security**: Bcrypt hashing with configurable rounds (`BCRYPT_ROUNDS=12`) for stored passwords.

## Best Practices Implemented

- **Least Privilege**: Policies ensure users have minimal necessary permissions, reducing the attack surface.
- **Comprehensive Logging**: Audit logs cover all significant actions, with request IDs enabling correlation for debugging and incident response.
- **Session Hardening**: Custom timeouts and database storage prevent session fixation and improve user logout reliability.
- **Bot Mitigation**: Cloudflare Turnstile and throttling protect public endpoints from automated abuse.
- **Secure Defaults**: Environment variables encourage secure settings (e.g., `APP_DEBUG=false` in production).
- **Middleware Protections**: Custom middleware for account status and no-cache headers adds additional security layers.

## Known Security Considerations

- **Email Configuration**: Default mailer is set to `log` in development; production deployments must configure a proper mailer for OTP and notifications.
- **HTTPS Enforcement**: `SESSION_SECURE_COOKIE` should be enabled in production to ensure cookies are sent over secure connections.
- **Regular Key Rotation**: API keys and tokens (e.g., Telegram, Cloudflare) should be rotated periodically to minimize exposure risk.

This security model ensures that PlagExpert remains a robust, secure platform for document processing, protecting both user data and system integrity.
