# Recent Changes & Known Edge Cases - PlagExpert (Portal)

This document summarises recent updates to PlagExpert (Portal) and known edge cases for ongoing development and operations.

## Latest Batch — Security Hardening & Business Logic Fixes (2026-05-10)

Commit `ca310da` on `main`. 22 files changed, 552 insertions, 180 deletions.

### Security Fixes

**OTP plaintext eliminated**
- OTPs are now hashed with `hash('sha256', $otp)` before being written to `users.otp`.
- `OtpLoginController::verifyOtp()` compares `hash('sha256', $request->otp)` against the stored hash.
- A database dump, backup exposure, or SQL injection can no longer be used to replay a stolen OTP.
- `otp` and `login_token` added to `User::$hidden` — they no longer appear in JSON-serialised User responses.

**OTP brute-force lockout**
- Failed OTP attempts are counted per portal number (not just per IP) using Laravel's `RateLimiter`.
- After 3 failures the OTP is immediately nulled in the database, preventing IP-rotation attacks.
- The per-portal counter is cleared when a fresh OTP is issued.

**Portal number race condition fixed**
- Concurrent Telegram `/start invite_xxx` activations could previously race on `MAX(portal_number) + 1`, producing duplicate portal numbers and a 500 error.
- Now uses a `portal_number_sequences` table (one row per role) locked with `lockForUpdate()` inside the activation transaction.
- A `UniqueConstraintViolationException` catch is also present as a final safety net — the user receives a friendly Telegram message instead of an unhandled 500.

**InviteController admin-role gate**
- Any admin could previously create another admin-level account.
- `InviteController::store()` now calls `$this->authorize('create', [User::class, $role])` before validation, delegating to `UserPolicy::create()` which requires `isSuperAdmin()` for the admin role.

**Account deletion hardened**
- The entire `AccountManagerController::destroy()` body is wrapped in a `DB::transaction()`.
- Credit/slot restoration on deletion has been **removed** — credits are forfeited when an account is deleted. This eliminates a class of abuse where clients could recover credits by having their account deleted.
- Client deletions now also: revoke all upload links (`is_active=false`, `revoked_at`, `revoked_by_user_id`); auto-reject pending refund requests (`status=rejected`, `admin_note='Account deleted.'`).
- Vendor payout records survive permanent deletion: `vendor_payouts.user_id` and `vendor_payout_requests.user_id` FKs changed from `cascadeOnDelete` to `nullOnDelete` via a new migration.

### Business Logic Fixes

**G1 — Client refund submission**
- Clients can now submit refund requests directly from the portal.
- `RefundController::store()` validates: order belongs to the client, order is in a refundable status (claimed/processing/delivered), no duplicate pending refund for the same order.
- Route: `POST /client/refunds`.

**G2 — Topup rupee amount field**
- `topup_requests` table gains an `amount_paid decimal(10,2) nullable` column.
- `TopupRequestController::store()` validates and saves `amount_paid` alongside the slot count.
- `TopupRequest::$fillable` updated.

**G3 — CloseDayCommand per-vendor payout rate**
- The daily ledger previously calculated vendor payouts as `order count × global rate`, ignoring individual vendor rates.
- Now sums each order's vendor's actual `payout_rate ?? $defaultPayoutRate`.
- `vendor_breakdown` ledger entries now use the per-vendor rate and include a `rate` key recording which rate was applied.

**G4 — Payout overpayment guard**
- `VendorPayoutController::store()` calculates the vendor's current balance (`earned - paid`) before creating the record.
- Returns an error with the vendor's name and actual balance if the requested amount exceeds it.

**G5 — Vendor self-service payout requests**
- New `vendor_payout_requests` table (`id`, `user_id FK nullOnDelete`, `amount_requested`, `status enum`, `notes`, `fulfilled_at`, `timestamps`).
- New `VendorPayoutRequest` model.
- `VendorPayoutController::requestPayout()`: prevents duplicate pending requests, calculates balance, creates request, fires `PortalTelegramAlertService::notifyVendorPayoutRequested()` to admin.
- `VendorPayoutController::store()`: if `payout_request_id` is supplied, marks that request as `fulfilled` with `fulfilled_at=now()`.
- `VendorPayoutController::index()` passes `pendingPayoutRequests` to the admin view.
- Route: `POST /earnings/request-payout`.

**G6 — RefundController::approve() atomicity**
- Slot decrement, client status update, and refund request status update are now wrapped in `DB::transaction()`.
- The `Log::warning` for the zero-slots edge case remains inside the transaction.
- `reject()` is unchanged (single write, no transaction needed).

### Reliability Fixes

**PurgeOrderFilesCommand**
- Added `->where('is_downloaded', true)` — delivered orders whose reports have not been downloaded are no longer purged. Clients who open the portal the morning after delivery now find their files intact.

**CleanupLinkOrdersCommand**
- Slot credit restoration moved inside each order's `DB::transaction()` with `lockForUpdate()`. A mid-loop crash can no longer leave an order deleted but its slot unreturned.

**OrderWorkflowService — delivery deduplication**
- Extracted `private markDelivered(Order, User)` shared by both `uploadReport()` and `deliver()`. Counter increments (`delivered_orders_count`, `daily_delivered_count`) now have a single code path, making double-increment structurally impossible.

**AutoReleaseOrdersCommand overlap prevention**
- Added `->withoutOverlapping()` to the scheduler definition. Prevents concurrent runs on multi-worker Railway deployments.

**bundleReports() — no more disk I/O**
- ZIP is now assembled via `tmpfile()` (OS-managed temp file) instead of `storage_path('app/tmp/...')`. Safe on ephemeral filesystems. Temp file is deleted immediately after the response bytes are read, regardless of whether the HTTP response completes.

**guestVisibleOrders / assertGuestOrderScope — undownloaded orders stay visible**
- Orders are now shown if they are recent (< 24h) **or** not yet downloaded, whichever is later.
- Download access also follows the same rule, preventing a 404 on the morning after a late delivery.

---

## Previous Batch — Dashboard Polling & Queue Notifications

- Guest-link upload and track pages poll token-scoped pulse endpoints instead of relying on manual refresh.
- Vendor and logged-in client dashboards use signature-based polling with server-rendered fragment replacement.
- Vendor order-completed Telegram notifications now run through the queue instead of blocking the upload request.

---

## Known Edge Cases

### OTP upgrade path
Any user mid-login at the moment of deployment will have a plaintext OTP in the database that the new hashed comparison cannot match. They see "Invalid or expired code" and must request a fresh code. No data is lost, no account is locked.

### Credits are forfeited on account deletion
This is now intentional. When an admin deletes a client account, any unconsumed slots are not returned. This prevents a pattern where clients could game the credit system by triggering account deletion. Operators should communicate this policy to clients before deletion.

### Pending refund auto-rejection on deletion
When a client account is deleted, pending refund requests are rejected with `admin_note='Account deleted.'` rather than being left in limbo. The admin panel will reflect this immediately via the sidebar badge bust.

### vendor_payouts and vendor_payout_requests — nullable user_id after forceDelete
After a vendor account is permanently deleted (`forceDelete()`), the `user_id` column in `vendor_payouts` and `vendor_payout_requests` becomes `NULL`. Queries that join these tables must handle `NULL` user_id gracefully (e.g., use `LEFT JOIN` and display "Deleted vendor" in the UI).

### Guest link orders — extended visibility window
Orders submitted via guest upload links are now visible until downloaded (no hard 24h cutoff). This means a client who never downloads their reports will see old orders accumulating in the list indefinitely. Consider adding a UI note or a longer-term archival grace period if this becomes noisy.

### Vendor payout request balance snapshot
When a vendor submits a payout request, the requested amount is snapshotted from their balance at that moment. If they deliver more orders before the admin processes the request, the outstanding amount will be higher than what was requested. The admin sees the live balance in `VendorPayoutController::index()` — the request amount is informational, not a ceiling.
