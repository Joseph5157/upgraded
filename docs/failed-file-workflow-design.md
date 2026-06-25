# Failed-File Workflow Design

**Phase:** 7C  
**Date:** 2026-06-24  
**Branch:** `filament-migration-phase-0-1`

---

## 1. Business Meaning

A **Failed** order is one where the vendor was unable to produce a valid report. This is distinct from:

- **Cancelled** — client withdrew the order before work began (credits refunded automatically)
- **Delivered** — vendor completed and submitted reports successfully

Failed means: the vendor attempted the work but could not complete it (e.g., file corruption, unsupported format, tool failure). The order remains in the system with its credit consumption intact — credit refunds for failed orders are handled separately by admin via the existing refund workflow.

---

## 2. Design Decision: Enum Addition (Option A)

**Chosen:** Add `case Failed = 'failed'` to the existing `OrderStatus` enum.

**Rejected alternative:** Separate `order_failures` table — adds unnecessary complexity for what is fundamentally a status transition. The enum approach keeps all status logic in one place.

---

## 3. Data Model Changes

### OrderStatus Enum
```php
case Failed = 'failed';  // Added after Cancelled
```

### Migration: `add_failed_status_to_orders_table`
- MySQL: `ALTER TABLE orders MODIFY COLUMN status ENUM(...,'failed')` — adds 'failed' to existing ENUM
- SQLite: skipped (tests use string column)
- New columns:
  - `failed_at` (timestamp, nullable) — when the order was marked failed
  - `failure_reason` (string 500, nullable) — vendor/admin explanation
  - `failed_by` (unsignedBigInteger, nullable, FK to users) — who marked it failed

### Order Model
- Add `failed_at`, `failure_reason`, `failed_by` to `$fillable`
- Add `failed_at` to `$casts` as datetime
- Add `Failed` case to `getComputedStatusAttribute()`

---

## 4. Workflow Rules

### Who can mark an order as Failed?
- The assigned vendor (owner) — for orders in `claimed` or `processing` status
- An admin — for any non-terminal order

### Status transitions TO Failed:
- `claimed → failed` ✓
- `processing → failed` ✓
- `pending → failed` ✗ (no vendor assigned yet)
- `delivered → failed` ✗ (terminal — use refund/reversal instead)
- `cancelled → failed` ✗ (terminal)

### Status transitions FROM Failed:
- `failed → pending` ✓ (admin can re-queue — unclaims and resets)
- `failed → [anything else]` ✗ (must go through pending first)

### Guard: Failed orders block further actions
- `uploadReport()` — rejects Failed orders (cannot upload to a failed order)
- `deliver()` — rejects Failed orders
- `startProcessing()` — rejects Failed orders
- `unclaim()` — rejects Failed orders (use requeue instead)

---

## 5. Credit Handling

**No automatic credit refund on failure.** Rationale:
- Failed orders DID consume credits at upload time
- Admin reviews failed orders and decides whether to issue a refund
- The existing `RefundController` handles refunds — admin can manually refund
- `RefundController` does NOT include Failed in `$refundableStatuses` — this is intentional; admin uses the separate admin refund/credit workflow

---

## 6. Finance Impact

**No changes needed to finance calculations:**
- Revenue queries use `whereNotNull('vendor_approved_at')` — failed orders have no approval, naturally excluded
- `FinanceDashboardService` "files uploaded" count excludes only `cancelled` — failed orders DID consume credits, so including them is correct
- `FinanceReportService` monthly summary uses same pattern — safe
- `VendorEarningService::createPendingForOrder()` is only called from `markDelivered()` — failed orders never reach delivery, so no earning is created

---

## 7. Files Modified

| File | Change |
|------|--------|
| `app/Enums/OrderStatus.php` | Add `case Failed = 'failed'` |
| `database/migrations/2026_06_24_*` | Add 'failed' to ENUM, add columns |
| `app/Models/Order.php` | Add fields to fillable/casts, update computed status |
| `app/Services/OrderWorkflowService.php` | Add `markFailed()`, add Failed guards to existing methods |
| `app/Policies/OrderPolicy.php` | Add `markFailed()` policy, add Failed guards |
| `app/Filament/Admin/Resources/OrderResource.php` | Add Failed to status color match |
| `app/Filament/Client/Resources/MyOrdersResource.php` | Add 'failed' to color maps |
| `app/Filament/Vendor/Resources/MyWorkResource.php` | Add 'failed' to color maps, add "Mark Failed" action |
| `app/Filament/Vendor/Resources/MyWorkResource/Pages/ViewMyWork.php` | Add "Mark Failed" header action |

---

## 8. Safety Assessment

**Safe to implement:**
- All strict `match` statements identified and will be updated
- Finance calculations are naturally safe (no changes needed)
- DashboardController uses explicit status lists, not exhaustive matches — safe
- BotController has `default` case in status match — safe
- RefundController intentionally excludes Failed — correct
- Tests use SQLite (string column) — migration skip means no schema change in tests
- Adding a new enum case is backwards-compatible — old code that doesn't reference it simply never encounters it until `markFailed()` is called
