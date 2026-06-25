# Filament Workflow Bridge Audit

**Phase:** 6 — Workflow Bridge + Gap Audit  
**Date:** 2026-06-24  
**Branch:** `filament-migration-phase-0-1`

---

## 1. Old Blade Routes Linked from Filament

### Client Panel — "Upload New File"

| File | Link |
|------|------|
| `app/Filament/Client/Resources/MyOrdersResource/Pages/ListMyOrders.php:19` | `url('/client/dashboard')` |

**Route:** `GET /client/dashboard` → named `client.dashboard`  
**Controller:** `ClientDashboardController@index`  
**View:** `resources/views/client/dashboard.blade.php`  
**Verdict:** **CORRECT.** `/client/dashboard` IS the client upload page (full dashboard with upload form). The route is protected by `role:client` and `account.status` middleware. No change needed.

---

### Vendor Panel — "Upload Report" (3 locations) — MIGRATED in Phase 7B

All three `/dashboard` links have been replaced with native Filament modal upload actions.

| File | Old Link | Phase 7B Change |
|------|----------|-----------------|
| `MyWorkResource.php` (table action) | `url('/dashboard')` | Replaced with `->form()` + `->action()` modal |
| `ListMyWork.php` (header action) | `url('/dashboard')` | Removed — upload is now per-order via table row action |
| `ViewMyWork.php` (header action) | `url('/dashboard')` | Replaced with `->form()` + `->action()` modal |

The modal calls `UploadVendorReportService::execute()` directly, preserving all existing logic.

---

## 2. Workflow Map

### Client Workflows

| Workflow | Status | Notes |
|----------|--------|-------|
| Client login | Still in Blade | OTP login at `/login`. Telegram auth via `/auth/telegram/{token}`. |
| Client dashboard | Still in Blade | `/client/dashboard` — full upload + order status view. |
| Client upload file/order | Still in Blade | `POST /client/dashboard/upload` via `ClientDashboardController@store`. |
| Credit deduction | Still in Blade (service layer) | `ClientDashboardController@store` calls credit service. |
| Order creation | Still in Blade (service layer) | Order + files created in `ClientDashboardController@store`. |
| View order status | **Migrated to Filament** | `MyOrdersResource` at `/client-panel/my-orders`. Also still in Blade dashboard. |
| Download completed report | **Migrated to Filament** | Download action on `MyOrdersResource` table. Also still in Blade dashboard. |
| Credit history | **Migrated to Filament** | `CreditWalletResource` at `/client-panel/credit-wallet`. |
| Payment history | **Migrated to Filament** | `PaymentHistoryResource` at `/client-panel/payment-history`. |
| Credit top-up request | Still in Blade | `POST /client/topup`. No Filament equivalent. |
| Refund request | Still in Blade | `POST /client/refunds`. No Filament equivalent. |
| Telegram link management | Still in Blade | `POST /client/dashboard/telegram/...`. No Filament equivalent. |
| Subscription page | Still in Blade | `GET /client/subscription`. No Filament equivalent. |

### Vendor Workflows

| Workflow | Status | Notes |
|----------|--------|-------|
| Vendor login | Still in Blade | Same OTP/Telegram login as clients. |
| Vendor dashboard | **Migrated to Filament** | Vendor panel home shows summary; "My Work" list in Filament. Report upload native in Filament since Phase 7B. Old Blade `/dashboard` still works. |
| Vendor assigned work | **Migrated to Filament** | `MyWorkResource` at `/vendor-panel/my-work`. |
| Vendor report upload | **Migrated to Filament** | Phase 7B: native Filament modal on `MyWorkResource`. Calls `UploadVendorReportService`. Old Blade route still works. |
| Vendor order claim/unclaim | Still in Blade | `POST /orders/{order}/claim` and `/unclaim` in `DashboardController`. No Filament equivalent. |
| Vendor status update | Still in Blade | `POST /orders/{order}/status` in `DashboardController`. |
| Vendor completion | Still in Blade | Upload report triggers order delivered status in `UploadVendorReportService`. |
| Vendor failed-file handling | **Missing — see Section 4** | No failed/rejected order status exists. Gap documented below. |
| Vendor earnings | **Migrated to Filament** | `EarningHistoryResource` at `/vendor-panel/earnings`. Also still in Blade at `/earnings`. |
| Vendor payout history | **Migrated to Filament** | `PayoutHistoryResource` at `/vendor-panel/payouts`. |
| Vendor payout request | Still in Blade | `POST /earnings/request-payout`. No Filament equivalent. |

### Admin Workflows

| Workflow | Status | Notes |
|----------|--------|-------|
| Client management | **Migrated to Filament** | `ClientResource` in admin panel. |
| Vendor management | **Migrated to Filament** | `VendorResource` in admin panel. |
| Order management | **Migrated to Filament** | `OrderResource` in admin panel. |
| Vendor assignment | **Migrated to Filament** | Admin can set `claimed_by` on orders in Filament. |
| Finance dashboard | **Migrated to Filament** | Finance panel at `/filament-finance`. Also old Blade at `/admin/finance/dashboard`. |
| Payment entry | **Migrated to Filament** | `ClientPaymentResource` in finance panel. Also old Blade route. |
| Credit ledger | **Migrated to Filament** | `ClientCreditTransactionResource` in finance panel. |
| Vendor earnings approval | **Migrated to Filament** | `VendorEarningTransactionResource` in finance panel (view). Approve/reject still in Blade (`POST /admin/finance/vendor-earnings/{order}/approve`). |
| Vendor payouts | **Migrated to Filament** | `VendorPayoutResource` in finance panel. |
| Expenses | **Migrated to Filament** | `BusinessExpenseResource` in finance panel. |
| Reports | Still in Blade | Finance reports at `/admin/finance/reports/...`. No Filament equivalent. |
| Voiding | **Migrated to Filament** | Void actions on ClientPayment, VendorPayout, BusinessExpense. |
| User freeze/unfreeze | Still in Blade + UserResource in Filament | Blade: `/admin/accounts/{user}/freeze`. Filament: UserResource has this action. |
| Account management | **Migrated to Filament** | `UserResource` in admin panel. |
| Client matrix / pricing | Still in Blade | `/admin/matrix`, `/admin/pricing`. No Filament equivalent. |
| Announcements | Still in Blade | `/admin/announcements`. No Filament equivalent. |
| Topup approvals | Still in Blade | `/admin/topup`. No Filament equivalent. |
| Billing / ledger | Still in Blade | `/admin/billing`. No Filament equivalent. |
| Refund approvals | Still in Blade | `/admin/refunds`. No Filament equivalent. |
| Client links | Still in Blade | `/admin/client-links`. No Filament equivalent. |
| Payment settings | Still in Blade | `/admin/payment-settings`. No Filament equivalent. |

---

## 3. Failed-File Workflow Gap

### Current State

**`OrderStatus` enum** (`app/Enums/OrderStatus.php`):
```php
case Pending    = 'pending';
case Claimed    = 'claimed';
case Processing = 'processing';
case Delivered  = 'delivered';
case Cancelled  = 'cancelled';
```

**No `Failed` status exists.**

The order model has `vendor_rejected_at` timestamp, but this tracks **earning rejection** (admin rejecting vendor's claimed earnings), not order/file failure.

### Is failure handled anywhere?

| Question | Answer |
|----------|--------|
| Is there any existing failed-file/order status? | No. `OrderStatus` enum has no `Failed` or `Rejected` case. |
| Is failure handled in any Blade controller? | No. `DashboardController` only handles `processing` and `delivered` in `updateStatus()`. |
| Is failure tracked at file level or order level? | Neither. No file-level or order-level failure tracking exists. |
| Does current finance logic exclude failed files? | N/A — no failed status to exclude. `vendor_rejected_at` affects earnings, not order status. |
| Would adding a failed status break existing tests? | Possibly — any test covering `OrderStatus` enum exhaustiveness or match statements could be affected. Needs careful review before adding. |

### Recommendation

Do not add failed status in this phase. Document the gap here. Address in Phase 7C if needed.

**Potential future design:**
- Add `Failed = 'failed'` to `OrderStatus`
- Add to all status `match` statements in resources, widgets, Blade views
- Decide: does failed restore client credits? (probably yes, same as cancelled)
- Does failed reverse vendor earning? (probably yes)

---

## 4. Data Scoping and Authorization Audit

### Security Gap Found and Fixed

**Gap:** All four Filament panel providers only had `Authenticate::class` in `authMiddleware`. Any authenticated user (regardless of role) could navigate to any panel URL and access data.

**Risk examples:**
- A `client` accessing `/filament-admin` → sees all orders, all clients, all vendor data via admin widgets
- A `client` accessing `/filament-finance` → could create fake client payments, vendor payouts, expenses

**Fix applied:** Created `app/Http/Middleware/FilamentPanelRole.php` and added it to `authMiddleware` in all four panel providers.

| Panel | Path | Required Role | Fix |
|-------|------|---------------|-----|
| Admin | `/filament-admin` | `admin` | `FilamentPanelRole::class . ':admin'` added |
| Finance | `/filament-finance` | `admin` | `FilamentPanelRole::class . ':admin'` added |
| Client | `/client-panel` | `client` | `FilamentPanelRole::class . ':client'` added |
| Vendor | `/vendor-panel` | `vendor` | `FilamentPanelRole::class . ':vendor'` added |

On role mismatch, the middleware redirects the user to their own correct panel (not a logout). This is intentional — it avoids an aggressive session kill for an accidental navigation.

### Per-Panel Data Scoping (after fix)

**Client panel** — correct scoping:
- `MyOrdersResource`: `where('client_id', $client->id)` — client sees only their own orders
- `CreditWalletResource`: `where('client_id', $client->id)` — client sees only their own credits
- `PaymentHistoryResource`: implicitly scoped similarly
- Widgets: `auth()->user()?->client` — returns null for non-clients, shows zeros

**Vendor panel** — correct scoping:
- `MyWorkResource`: `where('claimed_by', $user->id)` AND `$user->role !== 'vendor'` guard — vendor sees only their own work
- `EarningHistoryResource`: `where('vendor_id', $user->id)` — vendor sees only their earnings
- `PayoutHistoryResource`: `where('user_id', $user->id)` — vendor sees only their payouts

**Admin panel** — full access (correct for admin role):
- No client/vendor data scoping — admin intentionally sees everything

**Finance panel** — full access (correct for admin role):
- All finance resources show unscoped data — intended for admin only
- `canDelete() = false` on all finance resources — records cannot be hard-deleted
- Voiding is the soft-delete mechanism for finance records

### Frozen User Protection

`CheckAccountStatus` middleware is appended to the `web` middleware group in `bootstrap/app.php`. The web group applies to ALL web routes, including all Filament panel routes. Frozen users are logged out and redirected to `/login` before reaching any panel page. **No additional fix needed.**

### Cross-Client Data Access

A client CANNOT access another client's data:
- Filament queries are scoped by `$client->id` (derived from `auth()->user()?->client`)
- Old Blade routes are protected by `role:client` middleware which only allows authenticated clients
- Order download requires the order to belong to the client (policy checks in controllers)

### Cross-Vendor Data Access

A vendor CANNOT access another vendor's data:
- `MyWorkResource::getEloquentQuery()` scopes by `claimed_by = auth()->id()`
- `EarningHistoryResource::getEloquentQuery()` scopes by `vendor_id = auth()->id()`
- `PayoutHistoryResource::getEloquentQuery()` scopes by `user_id = auth()->id()`

---

## 5. Vendor Panel Mobile Notes

The following pages need manual viewport testing (360px, 390px, 430px, 768px):

- `/vendor-panel` (VendorDashboard)
- `/vendor-panel/my-work` (ListMyWork / ViewMyWork)
- `/vendor-panel/earnings` (ListEarningHistory)
- `/vendor-panel/payouts` (ListPayoutHistory)

**Panel configuration:**
- `->maxContentWidth('xl')` — limits layout width to `max-w-xl`
- `->sidebarCollapsibleOnDesktop()` — sidebar collapses on smaller screens
- `->spa()` — SPA mode for smoother navigation

**Checklist (requires manual browser verification):**

| Check | Expected | Verified |
|-------|----------|----------|
| No horizontal overflow at 360px | Tables should use horizontal scroll if needed | Manual |
| Main actions tappable at 360px | "Upload Report" button visible and large enough | Manual |
| Earnings table readable at 390px | Columns should not overlap | Manual |
| Payout table readable at 390px | Amount and date columns should be visible | Manual |
| Empty states useful | "No work assigned", "No earnings yet" messages | Manual |
| Upload Report button visible | On both ListMyWork and ViewMyWork | Manual |

**Known potential issues:**
- Table columns with many fields may overflow on narrow screens. Filament's default table behavior adds horizontal scroll, which is acceptable.
- The "Upload Report" button navigates to the Blade dashboard (`/dashboard`) — this cross-panel UX is intentional and acceptable until Phase 7B migration.

---

## 6. Next Phase Recommendation

### Recommended: Phase 7B — Migrate Vendor Report Upload Flow into Filament

**Reasoning:**

1. The vendor upload flow is the most disruptive cross-panel experience. A vendor on `/vendor-panel` clicks "Upload Report" and lands on the Blade `/dashboard`. This is confusing UX.

2. The vendor upload (`DashboardController@uploadReport` + `UploadVendorReportService`) is self-contained — it doesn't depend on complex credit logic (that's the client side). The upload service stores files and delivers orders.

3. The client upload flow (`ClientDashboardController@store`) involves credit deduction, order creation, file parsing, and public link logic — significantly more complex to move.

4. Phase 7B can be done safely:
   - Add a Filament modal/action to `ViewMyWork` page for report upload
   - Call the existing `UploadVendorReportService` from the Filament action
   - Remove the `/dashboard` link from Filament vendor panel
   - Old Blade vendor dashboard remains intact for backward compat during transition

5. Phase 7A (client upload) is riskier because it involves the full order/credit creation pipeline. Better to do 7B first and build confidence.

**Alternative paths:**
- **Phase 7D** (route cleanup) is premature — too many Blade routes still active
- **Phase 7E** (auth unification) is valuable but not urgent — OTP and Filament login both work
- **Phase 7C** (failed-file workflow) is a new feature, not a migration — should not block

---

## 7. Files Created / Modified in Phase 6

### Created
- `app/Http/Middleware/FilamentPanelRole.php` — role-based Filament panel access middleware
- `docs/filament-workflow-bridge-audit.md` — this file

### Modified
- `app/Providers/Filament/AdminPanelProvider.php` — added `FilamentPanelRole::class . ':admin'` to authMiddleware
- `app/Providers/Filament/FinancePanelProvider.php` — added `FilamentPanelRole::class . ':admin'` to authMiddleware
- `app/Providers/Filament/ClientPanelProvider.php` — added `FilamentPanelRole::class . ':client'` to authMiddleware
- `app/Providers/Filament/VendorPanelProvider.php` — added `FilamentPanelRole::class . ':vendor'` to authMiddleware

### Not Modified
- No Filament page files changed — upload route links (`/client/dashboard`, `/dashboard`) are correct as-is
- No model, migration, or test files changed
