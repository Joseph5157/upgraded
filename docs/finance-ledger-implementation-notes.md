# Finance Ledger — Implementation Notes

Generated as part of Phase 0 audit before coding Phase 1.

---

## Project structure summary

- **Backend**: Laravel 12
- **Auth**: Telegram OTP / token-based login
- **Roles**: admin, vendor, client (stored in `users.role`)
- **Deployment**: Railway
- **Storage**: Cloudflare R2

---

## Existing table inventory

### `clients` table

| Column          | Type             | Notes                                              |
|-----------------|------------------|----------------------------------------------------|
| id              | bigint PK        |                                                    |
| name            | string           |                                                    |
| slots           | integer          | Total allocated slots (topup adds to this)         |
| slots_consumed  | integer          | Incremented on each order upload                   |
| price_per_file  | decimal(8,2)     | Per-file charge rate for this client               |
| plan_expiry     | timestamp/null   | Upload blocked if past expiry                      |
| status          | string           | active / suspended / frozen                        |
| created_at      | timestamp        |                                                    |
| updated_at      | timestamp        |                                                    |

**Remaining credits (current formula):** `slots - slots_consumed`

**No `credit_balance` column** — balance is always computed. Phase 1 adds `credit_balance` as a denormalised cached field.

---

### `users` table (relevant columns)

| Column                  | Type           | Notes                                        |
|-------------------------|----------------|----------------------------------------------|
| id                      | bigint PK      |                                              |
| name                    | string         |                                              |
| email                   | string/null    |                                              |
| role                    | string         | admin / vendor / client                      |
| client_id               | bigint FK/null | Links user to a `clients` row                |
| payout_rate             | decimal(8,2)   | Per-file payout rate for this vendor         |
| delivered_orders_count  | integer        | Permanent delivered tally (not deleted)      |
| slots                   | integer/null   | Legacy user-level slots (not primary source) |
| status                  | string         | active / frozen                              |
| is_super_admin          | boolean        |                                              |
| soft deletes            | yes            | `deleted_at` via SoftDeletes                 |

**Vendor earnings (current formula):** `delivered_orders_count * payout_rate` computed on-the-fly  
**No payable balance columns** — Phase 1 adds `approved_payable_balance` and `pending_earning_balance`.

---

### `orders` table

| Column              | Type           | Notes                                              |
|---------------------|----------------|----------------------------------------------------|
| id                  | bigint PK      |                                                    |
| client_id           | bigint FK      | → `clients.id` (cascade delete)                   |
| token_view          | string unique  | Public token for order tracking                    |
| files_count         | integer        | Number of files in the order                       |
| status              | string enum    | pending / claimed / processing / delivered / cancelled |
| claimed_by          | bigint FK/null | → `users.id` (set null on delete) — vendor        |
| due_at              | timestamp      |                                                    |
| delivered_at        | timestamp/null |                                                    |
| is_downloaded       | boolean        |                                                    |
| source              | enum           | account / link                                     |
| created_by_user_id  | bigint FK/null | → `users.id` (set null on delete)                 |
| client_link_id      | bigint FK/null | → `client_links.id` (set null on delete)           |
| release_count       | integer        |                                                    |
| claimed_at          | timestamp/null |                                                    |
| notes               | text/null      |                                                    |

**Missing financial snapshot columns** — Phase 1 adds them:
- `credits_consumed`, `client_rate_per_file`, `client_amount`
- `vendor_rate_per_file`, `vendor_amount`, `gross_profit`
- `financial_locked_at`, `vendor_submitted_at`, `vendor_approved_at`, `vendor_rejected_at`
- `credits_refunded_at` (for idempotent cancellation refunds)

---

### `vendor_payouts` table

| Column       | Type           | Notes                                        |
|--------------|----------------|----------------------------------------------|
| id           | bigint PK      |                                              |
| user_id      | bigint FK/null | → `users.id` (null on delete — vendor)       |
| amount       | decimal(10,2)  |                                              |
| reference_id | string/null    | UPI Ref / Transaction ID                     |
| paid_at      | timestamp      | defaults to current                          |
| notes        | text/null      |                                              |

**Missing columns** — Phase 1 adds: `payment_mode`, `paid_by` (FK users), `status`

---

### `topup_requests` table

| Column           | Type      | Notes                                          |
|------------------|-----------|------------------------------------------------|
| id               | bigint PK |                                                |
| client_id        | bigint FK | → `clients.id` (cascade delete)               |
| amount_requested | integer   | Number of slots requested                      |
| amount_paid      | numeric   | Amount paid by client (added later)            |
| transaction_id   | string    | UTR / UPI reference (unique)                   |
| status           | enum      | pending / approved / rejected                  |
| notes            | text/null |                                                |
| reviewed_at      | timestamp |                                                |

TopupRequest approval currently calls `clients.slots += amount_requested`.  
Phase 3 will also create a `client_payments` row and `client_credit_transactions` row.

---

### `vendor_payout_requests` table

| Column           | Type      | Notes                                          |
|------------------|-----------|------------------------------------------------|
| id               | bigint PK |                                                |
| user_id          | bigint FK | → `users.id` (null on delete)                 |
| amount_requested | decimal   |                                                |
| status           | enum      | pending / fulfilled / rejected                 |
| notes            | text/null |                                                |
| fulfilled_at     | timestamp |                                                |

---

## Existing services

| Service                      | Purpose                                                   |
|------------------------------|-----------------------------------------------------------|
| CreateClientOrderService     | Creates order, uploads files, increments `slots_consumed` |
| UploadVendorReportService    | Stores AI + plag report files to R2                       |
| OrderWorkflowService         | Handles order status transitions                          |
| PortalTelegramAlertService   | Sends Telegram notifications                              |
| AuditLogger                  | Records audit log entries                                 |

---

## Existing controllers

| Controller              | Current responsibility                                     |
|-------------------------|------------------------------------------------------------|
| TopupRequestController  | Client submits topup; admin approves/rejects               |
| VendorPayoutController  | Admin records payout; vendor requests payout               |
| OrderController         | Guest link upload, track, download                         |
| BillingController       | Admin billing/ledger view                                  |
| VendorEarningsController| Vendor earnings dashboard                                  |
| RefundController        | Client refund requests                                     |

---

## Key FK chain for finance tables

```
clients.id
  ↑ client_payments.client_id
  ↑ client_credit_transactions.client_id
  ↑ orders.client_id

users.id (role=vendor)
  ↑ vendor_earning_transactions.vendor_id
  ↑ vendor_payouts.user_id

orders.id
  ↑ client_credit_transactions.order_id
  ↑ vendor_earning_transactions.order_id

client_payments.id
  ↑ client_credit_transactions.client_payment_id

vendor_payouts.id
  ↑ vendor_earning_transactions.vendor_payout_id
```

---

## Column name decisions

| Concept                  | Existing column           | New column (Phase 1)              |
|--------------------------|---------------------------|-----------------------------------|
| Client slot total        | clients.slots             | keep (still used)                 |
| Client slot used         | clients.slots_consumed    | keep (still used)                 |
| Client credit balance    | (computed)                | clients.credit_balance (integer)  |
| Client migration flag    | (none)                    | clients.credits_migrated_at       |
| Vendor payout rate       | users.payout_rate         | reuse — do NOT add duplicate      |
| Vendor pending balance   | (none)                    | users.pending_earning_balance     |
| Vendor approved balance  | (none)                    | users.approved_payable_balance    |

---

## Migration safety rules for Railway

1. All migrations are additive only — no column drops, no data truncation.
2. New columns on existing tables use `->nullable()` or `->default(0)` to be safe on existing rows.
3. No data backfill is done inside migrations — that belongs in artisan commands.
4. `vendor_payouts.user_id` is already `nullable` (changed in prior migration) — new FK `paid_by` must also be nullable.
5. All new finance tables have `timestamps()`.

---

## Phase 2B Legacy Slot Deprecation Audit

**Updated business decision (Phase 2B):**
- Project is not live; existing slot/topup/order data is not important.
- No slot-to-credit migration will run. `finance:migrate-opening-balances` is disabled by default.
- `clients.credit_balance` is the only source of truth for credits going forward.
- Old `slots` and `slots_consumed` are frozen — not updated by new business logic.
- Admin manually records money received → creates credits via new `ClientPaymentService` (Phase 3).

---

### Slot usage inventory

#### app/Services/CreateClientOrderService.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 55–56 | Reads `total_slots` and `slots_consumed` to compute remaining | Phase 4 — switch to `credit_balance` |
| 60 | Throws "No upload slots remaining" using slot math | Phase 4 — use `credit_balance` check |
| 113 | `$client->increment('slots_consumed', $fileCount)` | Phase 4 — use `ClientCreditService::debitForOrder()` |
| 116–118 | Suspends client when `slots_consumed >= totalSlots` | Phase 4 — use `credit_balance <= 0` |

#### app/Services/DeleteClientOrderService.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 97–99 | Decrements `slots_consumed` on order delete | Phase 4/5 — use `ClientCreditService::refundForOrder()` |
| 111 | Logs `slots_consumed_after` | Phase 4/5 |
| 114 | Un-suspends client when `slots_consumed < slots` | Phase 4/5 |

#### app/Http/Controllers/TopupRequestController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 84–90 | Approve topup: `client->slots += amount_requested` | Phase 3 — replaced entirely by `ClientPaymentService::record()` |
| 106 | Returns "Added N slots" success message | Phase 3 |
| Whole controller | Old topup request flow (submit/approve/reject) | Phase 3 — superseded by admin ClientPayment form |

#### app/Http/Controllers/ClientMatrixController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 36 | Sets `slots` directly on client | Phase 3 — admin credit management via new UI |
| 57 | Refills `slots += additional_slots` | Phase 3 — replaced by `ClientCreditService::creditClient()` |
| 20, 22 | Shows pending topups in matrix view | Phase 3 |

#### app/Http/Controllers/MatrixController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 29 | Sets `slots` on update | Phase 3 |
| 47 | Refills `slots` | Phase 3 |

#### app/Http/Controllers/ClientDashboardController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 87–88, 127–128 | `remaining = total_slots - slots_consumed` | Phase 4 — use `credit_balance` |
| 235 | Flash message says "credit slots have been restored" | Phase 4/5 |

#### app/Http/Controllers/OrderController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 129 | `guestCreditsRemaining()` uses `slots - slots_consumed` | Phase 4 — use `credit_balance` |

#### app/Http/Controllers/RefundController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 81–82 | Decrements `slots_consumed` on refund approval | Phase 5 — use `ClientCreditService::refundForOrder()` |
| 90 | Un-suspends client after refund | Phase 5 |

#### app/Http/Controllers/ClientSubscriptionController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 20–21 | `slotsUsed`, `slotsRemaining` from old slot math | Phase 4 — use `credit_balance` |
| 23–40 | Shows `topupHistory` to client | Phase 3 — replace with `client_payments` history |

#### app/Http/Controllers/BotController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 190, 208–220 | Bot shows "Used / Total slots" | Phase 4 |
| 342–344, 359–378 | Bot shows pending topup requests | Phase 3 |
| 453, 467 | Invite flow uses `slots` | Later (Razorpay / signup path) |

#### app/Http/Controllers/Admin/ClientLinkController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 186–187 | Creates client link with `slots` and `slots_consumed = 0` | Phase 4 — set `credit_balance` instead |

#### app/Http/Controllers/AdminDashboardController.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 39 | `out_of_credit_clients` uses `slots_consumed >= slots` | Phase 4 — use `credit_balance = 0` |

#### app/Services/PortalTelegramAlertService.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 181–182, 187–188 | Topup approved alert uses `slots - slots_consumed` and says "slots" | Phase 3 — update after new payment flow |

#### app/Console/Commands/CleanupLinkOrdersCommand.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 61 | `decrement('slots_consumed', $fileCount)` | Phase 4/5 — use refund service |

#### app/Jobs/ProvisionGuestLinkJob.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 51–52 | Sets `slots` and `slots_consumed = 0` on new client from Razorpay | Later (Razorpay flow) |

#### app/Support/LogContext.php
| Lines | What it does | Replace when |
|-------|-------------|--------------|
| 67–68 | Logs `slots` and `slots_consumed` | Phase 4 — add `credit_balance` to log context |

#### Models (User, Client, RazorpayOrder, PendingInvite)
- `clients.slots`, `clients.slots_consumed` — keep columns in DB; stop updating them in new code from Phase 3 onwards
- `users.slots` — user-level slot concept; unrelated to client credits; review later

---

### Views with slot/topup references

| File | Key slot/topup reference | Replace when |
|------|--------------------------|--------------|
| `resources/views/admin/topups.blade.php` | Entire old topup approval page | Phase 3 — replace with client payment UI |
| `resources/views/admin/matrix/index.blade.php` | Slots column, refill modal, topup badge | Phase 3 |
| `resources/views/admin/finance/matrix.blade.php` | Slots column, topup approval inline | Phase 3 |
| `resources/views/admin/dashboard.blade.php` | Pending topups card, "Initial slots" field | Phase 3 |
| `resources/views/admin/pricing/index.blade.php` | Slots column display | Phase 4 |
| `resources/views/admin/accounts/index.blade.php` | `$client->client?->slots` | Phase 4 |
| `resources/views/admin/client-links/index.blade.php` | Slots input on new client creation | Phase 4 |
| `resources/views/components/admin-layout.blade.php` | Pending topup badge in sidebar | Phase 3 |
| `resources/views/client/upload.blade.php` | `slots - slots_consumed` for remaining | Phase 4 |
| `resources/views/client/upload/partials/live.blade.php` | Same, in polling fragment | Phase 4 |
| `resources/views/client/dashboard.blade.php` | Topup modal | Phase 3 |
| `resources/views/client/dashboard/partials/live.blade.php` | Topup button | Phase 3 |
| `resources/views/client/subscription.blade.php` | Slots used/remaining, topup history | Phase 3/4 |
| `resources/views/signup/show.blade.php` | Razorpay plan slots | Later |

---

### Routes with slot/topup references

| Route name | Controller method | Replace when |
|------------|------------------|--------------|
| `client.topup.store` | `TopupRequestController::store` | Phase 3 |
| `admin.topup.index` | `TopupRequestController::index` | Phase 3 |
| `admin.topup.approve` | `TopupRequestController::approve` | Phase 3 |
| `admin.topup.reject` | `TopupRequestController::reject` | Phase 3 |

---

### Phase 2B decisions summary

| Item | Decision |
|------|----------|
| `finance:migrate-opening-balances` | Disabled by default; requires `--legacy-force=yes` |
| Old `slots`/`slots_consumed` columns | Frozen — not written by new code; kept in DB for safety |
| Old topup flow | Left in place until Phase 3 builds replacement |
| `CreateClientOrderService` | Still uses `slots_consumed` until Phase 4 switches it |
| New credit_balance | Written only by `ClientCreditService`; starts at 0 for all clients |
| Production reset approach | Option B: `finance:reset-clean-slate` (keeps users/clients, clears finance ledger) |

---

### Phase 3 preparation notes

Phase 3 will implement:
- `AdminFinanceController` (new): admin records money received → calls `ClientPaymentService::record()`
- `ClientPaymentService::record()` (implement stub): creates `client_payments` row + calls `ClientCreditService::creditClient()`
- Only `clients.credit_balance` is updated — `slots` and `slots_consumed` are NOT touched
- Old `TopupRequestController::approve` is NOT called — new payment flow is independent
- New routes: `POST /admin/finance/client-payments` (store), `GET /admin/finance/client-payments` (index)
- Old topup routes remain active but are not the source of truth for credit balance

---

## Phase 4 — Upload flow switched to credit_balance ledger (2026-06-17)

### Audit findings

**Files modified:**

| File | Change |
|------|--------|
| `app/Services/CreateClientOrderService.php` | Replaced slot check with `credit_balance`; added snapshot fields to Order; calls `ClientCreditService::debitForOrder()` instead of `increment('slots_consumed')` |
| `app/Services/DeleteClientOrderService.php` | Added guard: only refunds credits if ORDER_DEBIT tx exists (pre-Phase-4 orders skipped); removed `slots_consumed` decrement; reactivation now based on `credit_balance > 0` |
| `app/Http\Controllers\ClientDashboardController.php` | `$remaining` = `credit_balance`; `$consumed` kept at 0 for view compatibility |
| `app/Http/Controllers/OrderController.php` | `guestCreditsRemaining()` returns `$client->credit_balance` |
| `app/Http/Controllers/TopupRequestController.php` | `store()` disabled — returns error redirect |
| `resources/views/client/dashboard.blade.php` | "Top Up" button replaced with "Contact Admin" label; topup modal removed |
| `resources/views/client/dashboard/partials/live.blade.php` | Same "Contact Admin" change in the live partial |

### Design decisions

**Pre-Phase-4 order guard**: `DeleteClientOrderService` checks for a `TYPE_ORDER_DEBIT` transaction before calling `refundForOrder()`. Orders created before Phase 4 never debited `credit_balance`, so no credit is returned when they are deleted. This prevents phantom credit refunds.

**`slots_consumed` frozen**: Phase 4 never writes to `slots` or `slots_consumed`. Both columns remain at their last-written values and are effectively frozen. They are not used for any credit decisions.

**Auto-suspend logic changed**: Was `slots_consumed >= total_slots`. Now `credit_balance <= 0`. Reactivation on deletion was `slots_consumed < slots`. Now `credit_balance > 0`.

**`order_id` nullOnDelete**: The `client_credit_transactions.order_id` foreign key uses `nullOnDelete`. When an order is deleted, all linked transaction rows have `order_id` set to `null` — the rows themselves are preserved for ledger auditability. Tests querying refund txs after deletion must use `client_id + type`, not `order_id`.

**Topup disabled at controller level**: `TopupRequestController::store()` now returns an error redirect immediately. The client route `client.topup.store` still exists but does nothing useful. The self-service modal has been removed from the dashboard view.

### What was NOT changed

- `RefundController::approve()` — still decrements `slots_consumed` (Phase 4B — fixed in 4B)
- Admin topup management pages — still functional (admin-side flows read `TopupRequest` records but don't affect `credit_balance`)
- `slots` and `slots_consumed` columns — frozen, not dropped
- Vendor flows — untouched

---

## Phase 4B Refund and Legacy Slot Cleanup Audit

**Date:** 2026-06-18

### Audit findings — slot usages in refund/cancel paths

| File | Line(s) | Old behaviour | Fixed |
|------|---------|--------------|-------|
| `app/Http/Controllers/RefundController.php` | 81–90 | Decremented `slots_consumed`; reactivated via `slots_consumed < slots` | Yes — Phase 4B |
| `app/Console/Commands/CleanupLinkOrdersCommand.php` | 57–61 | Decremented `slots_consumed` per file count on link-order cleanup | Yes — Phase 4B |
| `app/Services/DeleteClientOrderService.php` | — | Already fixed in Phase 4; uses `refundOrderIfDebited` guard | n/a |

No other refund or cancellation code paths were found to touch `slots` or `slots_consumed`.

---

### New helper: `ClientCreditService::refundOrderIfDebited()`

Added to `app/Services/Finance/ClientCreditService.php`.

**Signature:**
```php
public function refundOrderIfDebited(
    Client $client,
    Order $order,
    ?\App\Models\User $createdBy = null,
    ?string $reason = null
): bool
```

**Rules:**
- Returns `false` immediately if no `TYPE_ORDER_DEBIT` tx exists for the order (pre-Phase-4 orders).
- Returns `false` if credits were already refunded (idempotent via `refundForOrder()`).
- Delegates to `refundForOrder()` if a debit tx exists.
- Returns `true` only when a new `refund_credit` tx was created.
- Never touches `slots` or `slots_consumed`.
- Caller must provide a locked client inside an active `DB::transaction()`.

---

### RefundController::approve() — changes

**Old behaviour:**
- Decremented `client->slots_consumed` (guarded against going below 0).
- Reactivated suspended client if `slots_consumed < slots`.
- Success message: "Refund approved. Credit slot has been returned to the client."

**New behaviour:**
- Locks both `clients` and `orders` rows inside a DB transaction.
- Calls `ClientCreditService::refundOrderIfDebited()`.
- Reactivates suspended client if `credit_balance > 0` (new ledger check).
- Success message is conditional:
  - Phase-4 order: *"Refund approved. Credits have been restored to the client."*
  - Legacy order: *"Refund approved. No credit refund was created because this order did not consume credits from the new ledger."*

---

### CleanupLinkOrdersCommand — changes

**Old behaviour:**
- Called `Client::decrement('slots_consumed', $fileCount)` inside the deletion transaction.

**New behaviour:**
- Locks the client row.
- Calls `ClientCreditService::refundOrderIfDebited($lockedClient, $order)` before `$order->delete()`.
- Credit is only refunded if a `TYPE_ORDER_DEBIT` tx exists (Phase-4 orders).
- Pre-Phase-4 link orders: no credit refund, no slot change.

---

### What was NOT changed in Phase 4B

- Vendor earning/payout flows — deferred to Phase 5.
- Finance dashboard — deferred.
- Old top-up flow — left in place (superseded but not removed).
- `slots` and `slots_consumed` columns — frozen, not dropped.
- Admin topup management pages — still display `TopupRequest` records but do not affect `credit_balance`.

---

## Phase 5 Vendor Pending Earning Audit

**Date:** 2026-06-18

### Vendor report upload flow (before Phase 5)

```
DashboardController::uploadReport()
  → UploadVendorReportService::execute()
      → OrderWorkflowService::uploadReport()       ← saves PDFs, checks complete
          → markDelivered()                        ← status=Delivered, increments counters
```

After Phase 5, `markDelivered()` also calls `VendorEarningService::createPendingForOrder()`.

---

### Vendor payout rate source

| Field | Table | Type | Notes |
|-------|-------|------|-------|
| `payout_rate` | `users` | `decimal` | Per-file vendor payout rate; already existed before Phase 5 |
| `pending_earning_balance` | `users` | `decimal:2` | Added in Phase 1 migrations; now written in Phase 5 |
| `approved_payable_balance` | `users` | `decimal:2` | Added in Phase 1 migrations; NOT written in Phase 5 |

---

### Files audited

| File | Method | Current behaviour | Changed in Phase 5 |
|------|--------|------------------|--------------------|
| `app/Services/OrderWorkflowService.php` | `markDelivered()` | Delivers order, increments counters | Yes — calls `VendorEarningService::createPendingForOrder()` |
| `app/Services/Finance/VendorEarningService.php` | `createPendingForOrder()` | Stub throwing LogicException | Yes — fully implemented |
| `app/Services/Finance/VendorEarningService.php` | `approveEarning()` | Stub | No — Phase 6 |
| `app/Services/Finance/VendorEarningService.php` | `reverseEarning()` | Stub | No — Phase 6 |
| `app/Services/UploadVendorReportService.php` | `execute()` | Delegates to `uploadReport()` | No — integration is inside `markDelivered()` |
| `app/Http/Controllers/DashboardController.php` | `uploadReport()` | Delegates to service | No |
| `app/Http/Controllers/VendorEarningsController.php` | `index()` | Shows `VendorDailySnapshot` + old payouts | No — Phase 6 (will switch to new ledger) |

---

### VendorEarningService::createPendingForOrder() — design

**Signature:** `createPendingForOrder(Order $order, ?User $createdBy = null): ?VendorEarningTransaction`

**Returns:**
- `VendorEarningTransaction` if a new pending earning was created.
- `null` if skipped (no vendor assigned, or already earned — idempotent).

**Transaction boundary:** Wraps its own `DB::transaction()`. Safe to call from within an existing transaction (nested = savepoint in MySQL/SQLite).

**Idempotency:** Checks for an existing `pending_order_earning` with `status=posted` for the order. If found, returns null without writing anything.

**Rate:** Snapshots `vendor->payout_rate` at delivery time (immutable rate-at-time-of-delivery).

**Files count:** `order->files_count` → fallback `credits_consumed` → fallback `1`.

**Balance update:** Only `pending_earning_balance` is incremented. `approved_payable_balance` is left unchanged.

**Snapshot fields stored on order:** `vendor_rate_per_file`, `vendor_amount`.

---

### Order status flow — Phase 5

No new order status was introduced. The flow remains:

```
Pending → Claimed → Processing → Delivered
```

`Delivered` is the terminal state after report upload. The "pending" in Phase 5 refers to the **earning transaction status**, not the order status. Admin approval/rejection of the earning is Phase 6.

---

### What was NOT changed in Phase 5

- `VendorEarningController` — still uses `VendorDailySnapshot` (legacy view); Phase 6 will migrate to new ledger.
- `approveEarning()` / `reverseEarning()` — stubs, Phase 6.
- Vendor payout flow — untouched.
- Finance dashboard — deferred.
- Client credit logic — untouched.
- `slots` and `slots_consumed` — frozen, never written.

---

## Phase 6 — Admin Approval / Rejection of Vendor Pending Earnings

### What Phase 6 implemented

**`VendorEarningService::approveEarning(Order $order, ?User $approvedBy, ?string $notes)`**
- Finds the `pending_order_earning` (posted) tx for the order; returns null if none.
- Idempotent: returns null if an `approve_earning` tx already exists.
- Moves `amount_delta` from `pending_earning_balance` → `approved_payable_balance`.
- Creates a `TYPE_APPROVE_EARNING` / `STATUS_POSTED` tx row.
- Sets `orders.vendor_approved_at`, `orders.gross_profit` (`client_amount - vendor_amount`), `orders.financial_locked_at`.

**`VendorEarningService::reverseEarning(Order $order, ?User $reversedBy, ?string $reason)`**
- Finds the `pending_order_earning` (posted) tx for the order; returns null if none.
- Throws `LogicException` if an `approve_earning` tx already exists (post-approval reversal not supported in Phase 6).
- Idempotent: returns null if a `reversal` tx already exists.
- Decreases `pending_earning_balance` by the original `amount_delta`.
- Does NOT touch `approved_payable_balance`.
- Creates a `TYPE_REVERSAL` / `STATUS_POSTED` tx row with a negative `amount_delta`.
- Sets `orders.vendor_rejected_at`.

**`app/Http/Controllers/Admin/VendorEarningController`** (new)
- `index()` — lists delivered orders with a posted `pending_order_earning` but no `approve_earning` or `reversal` tx.
- `approve(Order)` — delegates to `approveEarning()`, busts `admin_nav_pending_vendor_earnings` cache key.
- `reject(Order)` — delegates to `reverseEarning()`, catches `LogicException` and returns `error` flash.

**Routes added** (inside `admin.finance` prefix group):
```
GET  /admin/finance/vendor-earnings              → admin.finance.vendor-earnings.index
POST /admin/finance/vendor-earnings/{order}/approve → admin.finance.vendor-earnings.approve
POST /admin/finance/vendor-earnings/{order}/reject  → admin.finance.vendor-earnings.reject
```

**Admin sidebar** (`resources/views/components/admin-layout.blade.php`)
- Added "Earnings" link in the Vendors section with a badge showing pending count.
- Cache key: `admin_nav_pending_vendor_earnings` (60-second TTL).

**Smoke test fixes** (`tests/Feature/SmokeTest.php`)
- `makeClient()` helper now accepts `creditBalance: int = 0` and sets `credit_balance` on the client.
- `pendingOrder()` helper now sets `credits_consumed` so `debitForOrder()` debits the correct amount.
- A-2: changed assertion from `slots_consumed == 1` → `credit_balance == 9`.
- A-3: changed assertion from `slots_consumed == 0` → `credit_balance == 10`.
- A-4: changed assertion from `slots_consumed == 5` → `credit_balance == 0`.
- A-5: creates client with `creditBalance: 7`, creates debit tx manually, checks `credit_balance` restored (7) and audit meta keys `credits_refunded: true` and `credit_balance_after`.
- C-3: removed `slots_consumed` and `credits.restored` assertions — `AccountManagerController::destroy()` forfeits credits on account deletion by design; test now only verifies orders are cancelled and `account.deleted` audit log is written.
- E-5: creates debit tx, checks `credits_refunded: true` and `credit_balance_after`.

### No new migrations in Phase 6

All required order columns (`vendor_approved_at`, `vendor_rejected_at`, `gross_profit`, `financial_locked_at`) and `vendor_earning_transactions` columns were added in Phase 1. No DB changes needed.

### Gross profit formula

`gross_profit = client_amount - vendor_amount`

Set on order at approval time (not at delivery) because the vendor amount can be revised up to the moment of approval.

### What is NOT in Phase 6

- Post-approval reversal (would require moving amount from `approved_payable_balance` back to pending; deferred).
- Bulk approve / bulk reject.
- Email / Telegram notification to vendor on approval or rejection.
- Vendor-facing view of approval status (vendor earnings view not updated in Phase 6).

---

## Phase 7 — Vendor Payout System

### What Phase 7 implemented

**`VendorPayoutService::recordPayout(User $vendor, array $data, ?User $paidBy)`** (fully implemented, was a stub)
- Only touches `approved_payable_balance` — never `pending_earning_balance`.
- Validates: vendor role, `amount > 0`, `amount <= approved_payable_balance`.
- Duplicate transaction-ID guard: rejects same `(payment_mode, transaction_id)` pair for any non-cash mode.
- Creates a `vendor_payouts` row with `status = 'paid'`.
- Creates a `VendorEarningTransaction` row: `type = payout`, negative `amount_delta`, snapshot `approved_balance_after`.
- Decrements `users.approved_payable_balance` atomically inside a `DB::transaction()` + `lockForUpdate()`.
- Fires `vendor.payout_recorded` structured log entry.

**`App\Http\Controllers\Admin\Finance\VendorPayoutController`** (new — separate from legacy controller)
- `index()` — maps all vendors with `pending_earning_balance`, `approved_payable_balance`, `total_paid` (sum of paid payouts), `last_payout_at`.
- `store(StoreVendorPayoutRequest)` — calls `payoutService->recordPayout()`, busts `admin_nav_pending_vendor_earnings` cache key.
- `show(VendorPayout)` — loads `vendor`, `paidBy`, `earningTransactions` relationships for detail view.

**`App\Http\Requests\Finance\StoreVendorPayoutRequest`** (new)
- `vendor_id`: required, `exists:users`.
- `amount`: required, numeric, `min:0.01`.
- `payment_mode`: required, `in:upi,bank_transfer,cash`.
- `transaction_id`: nullable string.
- `paid_at`: nullable date.
- `notes`: nullable string.

**Routes added** (inside `admin.finance` prefix group):
```
GET  /admin/finance/payouts           → admin.finance.payouts.index
POST /admin/finance/payouts           → admin.finance.payouts.store
GET  /admin/finance/payouts/{payout}  → admin.finance.payouts.show
```

**Legacy controller kept:** `App\Http\Controllers\VendorPayoutController` (non-Admin namespace) remains for the vendor-facing `requestPayout` route (`earnings.request-payout`). It is not touched in Phase 7. The admin routes now use `Admin\Finance\VendorPayoutController` aliased as `AdminVendorPayoutController` in `routes/web.php` to avoid collision.

**Views:**
- `resources/views/admin/finance/payouts.blade.php` — rewritten; shows `approved_payable_balance` and `pending_earning_balance` per vendor; warning banner: "Only approved payable balance can be paid out"; pay modal with `payment_mode`, `transaction_id`, `paid_at` fields; JS `openPayModal(vendorId, name, approvedBalance)`.
- `resources/views/admin/finance/payouts-show.blade.php` — new detail view; shows payout metadata, vendor balances (current), and linked `vendor_earning_transactions` ledger rows.

### Safety constraints enforced

| Rule | How it is enforced |
|------|--------------------|
| Cannot pay more than `approved_payable_balance` | `InvalidArgumentException` thrown in service; HTTP store returns `back()->with('error', ...)` |
| Payout never touches `pending_earning_balance` | Not referenced anywhere in `recordPayout()` |
| Payout never touches client credits or slots | No `ClientCreditService` calls; no `slots`/`slots_consumed` writes |
| Duplicate transaction-ID rejected (non-cash) | DB query on `vendor_payouts` keyed by `(payment_mode, reference_id)` |
| Cash without transaction-ID allowed | Duplicate check skipped when `payment_mode = cash` |

### No new migrations in Phase 7

All required columns (`vendor_payouts.payment_mode`, `vendor_payouts.paid_by`, `vendor_payouts.status`, `vendor_earning_transactions.*`) were added in Phase 1 migrations.

### Legacy VendorEarningsController note

`App\Http\Controllers\VendorEarningsController::index()` still uses `VendorDailySnapshot` for the vendor-facing earnings dashboard. It is NOT updated in Phase 7. That controller is separate from the admin payout flow and is deferred to a future phase.

### What is NOT in Phase 7

- Vendor-initiated payout requests (legacy `requestPayout` flow exists but is not integrated with new ledger).
- Partial payout tracking or payout batches.
- Email / Telegram notification to vendor on payout.
- Business expense recording.
- Finance profit/loss dashboard.

---

## Phase 8 — Business Expense Tracking

### Audit findings

| Item | State before Phase 8 |
|------|----------------------|
| `business_expenses` table | Created in Phase 1 migration — columns: `category` (string), `amount` (decimal 12,2), `payment_mode` (nullable string), `reference_id` (nullable string), `expense_date` (date), `created_by` (FK users, nullOnDelete), `notes` (text nullable) |
| `BusinessExpense` model | Existed with 6 category constants; missing `internet`, `domain`, `office`, `refund_loss` |
| `BusinessExpenseService` | Stub — `record()` threw `LogicException('Implement in Phase 9')` |
| `FinanceDashboardService` | Phase 10 stub — not touched |
| `FinanceResetCleanSlateCommand` | Already clears `business_expenses` rows — no change needed |
| No payee/description column | Only `notes` is available for free-text context |

### Category constants added

The model had 6 categories; Phase 8 adds 4 more to match the user's specification:

| Constant | Value | Label |
|----------|-------|-------|
| `CATEGORY_STAFF_SALARY` | `staff_salary` | Staff Salary |
| `CATEGORY_SOFTWARE` | `software` | Software |
| `CATEGORY_RAZORPAY_CHARGES` | `razorpay_charges` | Razorpay Charges |
| `CATEGORY_HOSTING` | `hosting` | Hosting |
| `CATEGORY_INTERNET` *(new)* | `internet` | Internet |
| `CATEGORY_DOMAIN` *(new)* | `domain` | Domain |
| `CATEGORY_OFFICE` *(new)* | `office` | Office |
| `CATEGORY_REFUND_LOSS` *(new)* | `refund_loss` | Refund Loss |
| `CATEGORY_OTHER` | `other` | Other |

`CATEGORY_INTERNET_DOMAIN` (`internet_domain`) was removed; `internet` and `domain` are now separate. No DB migration needed — `category` is a plain string column with no DB enum constraint.

### What Phase 8 implemented

**`BusinessExpenseService::recordExpense(array $data, ?User $createdBy = null): BusinessExpense`** (implements Phase 9 stub, renamed from `record()`)
- Validates `amount > 0`; throws `InvalidArgumentException`.
- Validates `category` against `BusinessExpense::categories()` keys; throws `InvalidArgumentException`.
- Duplicate reference_id guard: rejects same `(payment_mode, reference_id)` pair for non-cash, non-auto_deducted modes.
- `cash` and `auto_deducted` modes skip the duplicate check (no receipt to deduplicate).
- Empty string `reference_id` is normalised to `null` before storage.
- Fires `business.expense_recorded` structured log entry.
- Does NOT touch `clients.credit_balance`, `users.pending_earning_balance`, or `users.approved_payable_balance`.
- Does NOT touch `slots` or `slots_consumed`.

**`BusinessExpenseService::totalExpenses(?Carbon $from, ?Carbon $to): float`** (implemented stub)
- Optional date-range filter on `expense_date`.

**`BusinessExpenseService::totalByCategory(?Carbon $from, ?Carbon $to): array`** (new method)
- Returns `array<string, float>` keyed by category, summed by amount.

**`App\Http\Requests\Finance\StoreBusinessExpenseRequest`** (new)
- `amount`: required, numeric, `min:0.01`, `max:9999999.99`.
- `category`: required, `Rule::in` validated against `BusinessExpense::categories()`.
- `payment_mode`: nullable, `in:upi,bank_transfer,cash,card,auto_deducted`.
- `reference_id`: nullable string, max 255.
- `expense_date`: required, date.
- `notes`: nullable string, max 2000.

**`App\Http\Controllers\Admin\Finance\BusinessExpenseController`** (new)
- `index()` — paginates expenses (25/page), computes `$total` and `$byCategory` summary.
- `show(BusinessExpense)` — loads `createdBy` relationship.
- `store(StoreBusinessExpenseRequest)` — calls `expenseService->recordExpense()`, catches `InvalidArgumentException` and returns `back()->with('error', ...)`.

**Routes added** (inside `admin.finance` prefix group):
```
GET  /admin/finance/expenses                  → admin.finance.expenses.index
POST /admin/finance/expenses                  → admin.finance.expenses.store
GET  /admin/finance/expenses/{businessExpense} → admin.finance.expenses.show
```

**Views:**
- `resources/views/admin/finance/expenses/index.blade.php` — shows total expense card, category breakdown card, paginated expense table, add-expense modal (amount, category, payment_mode, reference_id, expense_date, notes).
- `resources/views/admin/finance/expenses/show.blade.php` — shows all expense fields.

**Admin sidebar** — Added "Expenses" link (with `trending-down` icon) in the Vendors section, after Payouts.

### No new migrations in Phase 8

All required columns were added in Phase 1. `category` is a string column — new category values are added by model constants only, with no DB schema change.

### Profit formula context (for Phase 9)

```
Gross profit = client earned revenue (client_amount on delivered orders) - vendor approved earnings
Net profit   = gross profit - total business expenses
```

Business expenses are standalone records. They do not affect any balance columns on `clients` or `users`.

### What is NOT in Phase 8

- Finance profit/loss dashboard (Phase 9).
- Date-range filter in the UI (service supports it, UI deferred).
- Report/export of expenses.
- Expense editing or voiding.
- Expense categories are fixed by code constants — no admin-configurable category management.

---

## Phase 9 — Finance Dashboard

### What Phase 9 implemented

**`FinanceDashboardService`** (full implementation, was a stub)

`metrics(?Carbon $from, ?Carbon $to): array` — returns all 22 dashboard keys:

| Key | Formula / source |
|-----|-----------------|
| `total_money_received` | `SUM(client_payments.amount_received WHERE status='confirmed')` |
| `credits_added` | `SUM(client_credit_transactions.credits_delta WHERE type='payment_credit')` |
| `credits_used` | `ABS(SUM(...credits_delta WHERE type='order_debit'))` |
| `credits_refunded` | `SUM(...credits_delta WHERE type='refund_credit')` |
| `credits_remaining` | `SUM(clients.credit_balance)` — **always current, never date-filtered** |
| `files_uploaded` | `SUM(orders.credits_consumed WHERE status != 'cancelled')` |
| `files_completed` | `COUNT(orders WHERE vendor_approved_at IS NOT NULL)` |
| `revenue_earned` | `SUM(orders.client_amount WHERE vendor_approved_at IS NOT NULL)` |
| `vendor_cost` | `SUM(orders.vendor_amount WHERE vendor_approved_at IS NOT NULL)` |
| `gross_profit` | `revenue_earned - vendor_cost` |
| `vendor_pending` | `SUM(users.pending_earning_balance WHERE role='vendor')` — **always current** |
| `vendor_payable` | `SUM(users.approved_payable_balance WHERE role='vendor')` — **always current** |
| `vendor_paid` | `SUM(vendor_payouts.amount WHERE status='paid')` |
| `business_expenses` | `SUM(business_expenses.amount)` |
| `net_profit` | `gross_profit - business_expenses` |
| `cash_balance` | `total_money_received - vendor_paid - business_expenses` |
| `expense_by_category` | `GROUP BY category` on `business_expenses` |
| `client_summaries` | via `clientBalances()` |
| `vendor_summaries` | via `vendorBalances()` |
| `recent_payments` | last 5 confirmed `ClientPayment` rows |
| `recent_payouts` | last 5 paid `VendorPayout` rows |
| `recent_expenses` | last 5 `BusinessExpense` rows |

`clientBalances(): Collection` — per-client: total_paid, credits_added, credits_used, credit_balance. Uses 2 bulk aggregate queries (no N+1).

`vendorBalances(): Collection` — per-vendor: pending_earning, approved_payable, total_paid, files_completed. Uses 2 bulk aggregate queries.

**Date-range behaviour:**
- Transaction-based fields filtered by their natural date column (`received_at`, `created_at`, `paid_at`, `vendor_approved_at`, `expense_date`) when `$from`/`$to` are supplied.
- `credits_remaining`, `vendor_pending`, `vendor_payable` are live balance fields — never date-filtered regardless of range. The view shows a warning banner when a range is active.

**`App\Http\Controllers\Admin\Finance\FinanceDashboardController`** (new)
- Parses optional `from`/`to` GET params into Carbon instances.
- Swaps reversed date pairs automatically.
- Calls `dashboardService->metrics()` — no business calculations in the controller.

**Route added:**
```
GET /admin/finance/dashboard → admin.finance.dashboard
```

**`resources/views/admin/finance/dashboard.blade.php`** (new) — sections:
1. Date range filter form + active-range warning banner
2. Cash & Profit cards (Total Received, Vendor Paid, Expenses, Cash Balance)
3. Revenue cards (Revenue Earned, Vendor Cost, Gross Profit, Net Profit)
4. Credits cards (Added, Used, Refunded, Remaining)
5. Files & Vendor-dues cards (Uploaded, Completed, Pending, Payable)
6. Vendor summary table (name, pending, payable, total paid, files done)
7. Client summary table (name, total paid, credits added/used, balance)
8. Expense by category with progress bars
9. Recent activity feed (payments + payouts + expenses)
10. Formula reference

**Admin sidebar** — Added "Finance" link (pie-chart icon) under Overview section.

### No new migrations in Phase 9

All required columns were present from Phases 1–8.

### What is NOT in Phase 9

- Reports / CSV export.
- Editing or voiding any ledger records.
- Revenue breakdown per client (deferred — would need order→client join on approved orders).
- Pagination on client/vendor tables (acceptable for current scale).
- Caching of metrics (deferred — straightforward to add a short TTL cache in Phase 10 if needed).

---

## Phase 10A — Finance Reports and CSV Export

### Audit date: 2026-06-18

### New files

| File | Purpose |
|------|---------|
| `app/Services/Finance/FinanceReportService.php` | 7 query builder methods + monthlySummary + csvFilename helper |
| `app/Http/Controllers/Admin/Finance/FinanceReportController.php` | 15 action methods (index + 7 HTML + 7 CSV), parseFilters helper |
| `resources/views/admin/finance/reports/index.blade.php` | Report cards index — 7 report cards, each with date inputs + View + CSV buttons |
| `resources/views/admin/finance/reports/client-payments.blade.php` | Paginated client payments table with filters |
| `resources/views/admin/finance/reports/client-credit-ledger.blade.php` | Paginated credit transactions with type colour badges |
| `resources/views/admin/finance/reports/vendor-earnings.blade.php` | Paginated vendor earning rows with type/status badges |
| `resources/views/admin/finance/reports/vendor-payouts.blade.php` | Paginated vendor payouts with total in header |
| `resources/views/admin/finance/reports/expenses.blade.php` | Paginated expenses with category colour badges |
| `resources/views/admin/finance/reports/order-profit.blade.php` | Paginated profit rows with page totals in tfoot |
| `resources/views/admin/finance/reports/monthly-summary.blade.php` | Month table with formula reference footer, grand totals row |
| `tests/Feature/Finance/Phase10AFinanceReportsTest.php` | 36 tests |

### Routes added (15)

All under `admin.finance.` prefix:

```
GET /admin/finance/reports                          → reports.index
GET /admin/finance/reports/client-payments          → reports.client-payments
GET /admin/finance/reports/client-payments.csv      → reports.client-payments.csv
GET /admin/finance/reports/client-credit-ledger     → reports.client-credit-ledger
GET /admin/finance/reports/client-credit-ledger.csv → reports.client-credit-ledger.csv
GET /admin/finance/reports/vendor-earnings          → reports.vendor-earnings
GET /admin/finance/reports/vendor-earnings.csv      → reports.vendor-earnings.csv
GET /admin/finance/reports/vendor-payouts           → reports.vendor-payouts
GET /admin/finance/reports/vendor-payouts.csv       → reports.vendor-payouts.csv
GET /admin/finance/reports/expenses                 → reports.expenses
GET /admin/finance/reports/expenses.csv             → reports.expenses.csv
GET /admin/finance/reports/order-profit             → reports.order-profit
GET /admin/finance/reports/order-profit.csv         → reports.order-profit.csv
GET /admin/finance/reports/monthly-summary          → reports.monthly-summary
GET /admin/finance/reports/monthly-summary.csv      → reports.monthly-summary.csv
```

### CSV streaming approach

All CSV routes use `response()->streamDownload()` with `chunkById(500, callback)`. `cursor()` was rejected because it does not properly eager-load with `with()`. Each chunk eager-loads its relations so there is no N+1 within the stream.

### Monthly summary cross-DB compatibility

The `monthlySummary()` method uses `SUBSTR(column, 1, 7)` to extract `YYYY-MM` from datetime columns. This works in both SQLite (tests) and MySQL (production). `DATE_FORMAT` is MySQL-only and was deliberately avoided.

### Filters supported per report

| Report | Filters |
|--------|---------|
| Client Payments | from, to, client_id, payment_mode, status |
| Client Credit Ledger | from, to, client_id, type |
| Vendor Earnings | from, to, vendor_id, type, status |
| Vendor Payouts | from, to, vendor_id, payment_mode, status |
| Business Expenses | from, to, category, payment_mode |
| Order Profit | from, to, client_id, vendor_id |
| Monthly Summary | from, to |

### Admin sidebar

Added "Reports" link (bar-chart-2 icon) directly below the "Finance" dashboard link. Active state set via `request()->routeIs('admin.finance.reports.*')`.

### Test results

36 tests, 76 assertions — all passing.

### What is NOT in Phase 10A

- Void / reversal of individual payments or payouts (Phase 10B).
- PDF export (not planned — CSV is sufficient for accounting workflows).
- Report scheduling / email delivery.

---

## Phase 10B — Finance Voiding and Reversal System

### Audit date: 2026-06-18

### Voiding approach

The ledger system never hard-deletes or directly edits completed finance records. Voiding creates reversal entries and marks the original record with void metadata (`voided_at`, `voided_by`, `void_reason`). This preserves the full audit trail.

### Migrations added (3)

| Migration | Changes |
|-----------|---------|
| `2026_06_18_000001_add_void_columns_to_client_payments_table` | `voided_at` timestamp nullable, `voided_by` FK nullable, `void_reason` text nullable |
| `2026_06_18_000002_add_void_columns_to_vendor_payouts_table` | same pattern as client_payments |
| `2026_06_18_000003_add_void_columns_and_status_to_business_expenses_table` | `status` string default 'active' + index, `voided_at`, `voided_by`, `void_reason` |

### Model updates

| Model | Changes |
|-------|---------|
| `ClientPayment` | Added `voided_at`, `voided_by`, `void_reason` to fillable; `voided_at` cast to datetime; `voidedBy()` relationship |
| `VendorPayout` | Same pattern as ClientPayment |
| `BusinessExpense` | Added `status`, `voided_at`, `voided_by`, `void_reason` to fillable; `STATUS_ACTIVE`, `STATUS_VOIDED` constants; `voidedByUser()` relationship |
| `VendorEarningTransaction` | Added `TYPE_PAYOUT_REVERSAL = 'payout_reversal'` constant |

### New service

**`app/Services/Finance/FinanceVoidService.php`** — 3 methods:

| Method | Behavior |
|--------|----------|
| `voidClientPayment()` | Locks client + payment rows. Checks balance >= credits_added. Creates TYPE_CORRECTION credit transaction with negative delta. Decreases credit_balance. Marks payment voided. Throws RuntimeException if balance insufficient. |
| `voidVendorPayout()` | Locks vendor + payout rows. Creates TYPE_PAYOUT_REVERSAL earning transaction. Restores approved_payable_balance. Does NOT touch pending_earning_balance. Marks payout voided. |
| `voidBusinessExpense()` | Marks expense STATUS_VOIDED with metadata. No balance mutations needed (expenses are informational, not tied to ledger balances). |

All methods are idempotent: voiding an already-voided record returns `false` without creating duplicate reversals.

### Routes added (3)

```
POST /admin/finance/client-payments/{clientPayment}/void → admin.finance.client-payments.void
POST /admin/finance/payouts/{vendorPayout}/void          → admin.finance.payouts.void
POST /admin/finance/expenses/{businessExpense}/void      → admin.finance.expenses.void
```

### Controller updates

| Controller | Method added |
|------------|--------------|
| `ClientPaymentController` | `void()` — validates void_reason required, catches RuntimeException for insufficient balance |
| `VendorPayoutController` (Admin) | `void()` — validates void_reason required, uses authorization gate |
| `BusinessExpenseController` | `void()` — validates void_reason required |

### View updates

| View | Changes |
|------|---------|
| `client-payments/show.blade.php` | Added void action form (reason textarea + button) when status=confirmed. Added void details panel (voided_at, voided_by, reason) when voided. |
| `payouts-show.blade.php` | Same pattern: void action form when paid, void details when voided. Status badge now red for voided. |
| `expenses/show.blade.php` | Same pattern: void action form when active, void details when voided. Added voided badge in header. |

All void forms include the warning: "This will not delete the record. It will create a reversal and keep audit history." and a confirm dialog.

### Dashboard / report formula updates

**FinanceDashboardService** — already excluded voided payments/payouts via status filters (`STATUS_CONFIRMED` / `status=paid`). Added exclusion of voided BusinessExpenses:
- `business_expenses` sum: `where('status', '!=', 'voided')`
- `expense_by_category` breakdown: `where('status', '!=', 'voided')`
- `recent_expenses` feed: `where('status', '!=', 'voided')`

**BusinessExpenseService** — `totalExpenses()` and `totalByCategory()` now exclude voided.

**BusinessExpenseController** — `index()` totals exclude voided.

**FinanceReportController** — header totals now exclude voided:
- Client payments total: `where('status', 'confirmed')`
- Vendor payouts total: `where('status', 'paid')`
- Expenses total: `where('status', '!=', 'voided')`

Report table rows still show voided records (with status badge visible) for audit purposes.

**FinanceReportService** — `monthlySummary()` expenses aggregation excludes voided.

### Safety constraints upheld

- No records are hard-deleted or directly edited.
- Original amounts are never modified.
- Old slots/topup are never touched.
- `order_debit`, `refund_credit`, vendor approval/rejection records are NOT voided in this phase.
- Core payment/upload/payout creation logic is unchanged except for void-status filtering in totals.

### Client payment void blocking rule

If `client.credit_balance < payment.credits_added`, the void is blocked with:
> "Cannot void this payment automatically because credits have already been used. Use manual correction flow."

This prevents negative credit balances. Admin must use manual credit adjustment (Phase 4) to correct the balance first, or handle the situation case-by-case.

### Test results

28 tests, 74 assertions — all passing.
Full finance test suite: 297 tests, 696 assertions — all passing.

### Railway deployment notes

Run `php artisan migrate` to apply the 3 new migrations. All migrations are additive (nullable columns only) — no data loss, no downtime, safe to run on existing data.

### Deferred items

- Voiding `order_debit` / `refund_credit` transactions (requires order-state reversal logic).
- Voiding vendor earning approval/rejection (requires re-evaluation of vendor balances).
- Manual correction flow UI (admin can already use `ClientCreditService::adjustCredits()` programmatically).
- Bulk void operations.
- Void audit log / timeline view.

---

## Phase 10C — Legacy Cleanup + Production Hardening

### Audit date: 2026-06-18

### Legacy slot/topup audit — classification

Phase 10C audited all remaining `slots`, `slots_consumed`, and topup references in the codebase. Each usage is classified as **fixed** (updated in this phase), **frozen** (legacy code that still reads/writes slots but does not affect credit_balance), or **deferred** (will be addressed in a future phase).

#### Admin sidebar and dashboard — FIXED

| File | Line(s) | Old behaviour | Fix |
|------|---------|--------------|-----|
| `resources/views/components/admin-layout.blade.php` | 83-85 | `$lowCreditClients` counted via `slots_consumed >= slots` | Changed to `credit_balance <= 0` |
| `app/Http/Controllers/AdminDashboardController.php` | 39 | `out_of_credit_clients` used `whereRaw('slots_consumed >= slots')` | Changed to `where('credit_balance', '<=', 0)` |
| `resources/views/components/admin-layout.blade.php` | 88-91 | Topup nav link with pending badge visible | Hidden — topup flow is superseded by client payments |

#### Client-facing views — FIXED

| File | Change |
|------|--------|
| `app/Http/Controllers/ClientSubscriptionController.php` | `slotsUsed`/`slotsRemaining` now derived from `credit_balance` instead of `slots - slots_consumed`. `topupHistory` replaced with `paymentHistory` from `ClientPayment`. `lastTopup` replaced with `lastPayment`. |
| `resources/views/client/subscription.blade.php` | Topup request form replaced with "Contact Admin" card. Top-up history replaced with payment history. Credits remaining/used cards use `credit_balance`. |
| `resources/views/client/upload.blade.php` | Sidebar remaining count uses `$client->credit_balance` instead of `slots - slots_consumed` |
| `resources/views/client/upload/partials/live.blade.php` | `$consumed` and `$remaining` use `credit_balance` instead of slot math |

#### Legacy paths — FROZEN (still read/write slots, do not affect credit_balance)

These controllers still write to `clients.slots` and `clients.slots_consumed`. They are used for admin matrix management and legacy topup approval. They do NOT touch `credit_balance`, and the `slots` column is not used for any credit decisions in the active business flow.

| File | Method | What it does | Status |
|------|--------|-------------|--------|
| `TopupRequestController` | `approve()` | Adds to `client->slots` | Frozen — topup route hidden from nav; does not affect credit_balance |
| `MatrixController` | `update()`, `refill()` | Sets/adds `slots` directly | Frozen — admin matrix tool; slots column is informational only |
| `ClientMatrixController` | `store()`, `refill()` | Same as above | Frozen — same as MatrixController |
| `ClientLinkController` | `store()` | Creates link client with `slots` | Frozen — slots used only for guest link display |
| `BotController` | various | Reads slots for Telegram bot messages | Frozen — cosmetic only |
| `PortalTelegramAlertService` | `topupApproved()` | Shows slot count in alert | Frozen — cosmetic only |
| `LogContext` | `forClient()` | Logs `slots` and `slots_consumed` | Frozen — diagnostic only |

#### Test cleanup — FIXED

| File | Change |
|------|--------|
| `tests/Feature/GuestLinkTest.php` | `makeClient()` helper updated to set `credit_balance`. Upload test asserts `credit_balance` decrease instead of `slots_consumed` increase. |

#### Items NOT changed in Phase 10C

- `slots` and `slots_consumed` DB columns — kept in database, never dropped.
- `BotController` slot display — cosmetic; deferred until Telegram bot overhaul.
- `PortalTelegramAlertService` topup alert — cosmetic; deferred.
- Razorpay signup flow (`ProvisionGuestLinkJob`) — deferred until payment gateway integration.
- `config/plans.php` slots key — deferred until plan management overhaul.
- Admin matrix pages — functional but informational; slots column does not drive credit decisions.

### New files

| File | Purpose |
|------|---------|
| `tests/Feature/Finance/Phase10CLegacyCleanupTest.php` | 7 tests, 20 assertions — verifies credit_balance usage in dashboard, sidebar, client subscription |
| `docs/finance-production-rollout-checklist.md` | Migration order, verification queries, cache clearing, monitoring |
| `docs/finance-ledger-user-guide.md` | Admin workflow guide for all finance operations |

### Test results

7 tests, 20 assertions — all passing.
Full finance test suite: 304 tests, 716 assertions — all passing.

Pre-existing failures (NOT caused by Phase 10C):
- `GuestLinkTest > guest view and download` — ZipArchive permission error (Windows-specific)
- `SmokeTest > a1, b1` — OTP/auth test signature mismatch (pre-existing)
- `SmokeTest > d3` — Correlation ID known gap (documented)
