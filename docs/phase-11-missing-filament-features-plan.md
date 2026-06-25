# Phase 11B — Missing Filament Features Audit & Safe Implementation Plan

**Date:** 2026-06-25
**Branch:** `filament-migration-phase-0-1`
**Test baseline:** 474 passed, 13 pre-existing failures, 0 regressions
**Source of truth:** `docs/blade-retirement-plan.md`

---

## 1. Full Missing Features Audit

### 1.1 Vendor Panel — Missing Features

| Feature | Current Blade Route | Controller / Service | Filament Status | Risk | Recommended Phase | Can Implement Now? |
|---|---|---|---|---|---|---|
| Payout request | `POST /earnings/request-payout` | `VendorPayoutController::requestPayout` (inline logic) | **None** | Low | **11B** | **Yes** |
| Profile edit | `GET/PATCH /profile` | `ProfileController` | None | Low | 12+ | No — low priority |

### 1.2 Client Panel — Missing Features

| Feature | Current Blade Route | Controller / Service | Filament Status | Risk | Recommended Phase | Can Implement Now? |
|---|---|---|---|---|---|---|
| Downloads page | `GET /client/downloads` | `ClientDashboardController::downloads` | **None** | Low | **11B** | **Yes** |
| Subscription / credit info | `GET /client/subscription` | `ClientSubscriptionController::index` | **None** (partial widget) | Low | **11B** | **Yes** |
| Topup request | `POST /client/topup` | `TopupRequestController::store` | None | Medium | 11C | Not yet — needs admin approval flow |
| Refund request | `POST /client/refunds` | `RefundController::store` | None | Medium | 11C | Not yet — needs admin approval flow |
| Order deletion | `DELETE /client/orders/{order}/delete` | `ClientDashboardController::destroy` | None | Medium | 11C | Not yet — credit refund logic |
| File deletion | `DELETE /client/orders/{order}/files/{file}` | `ClientDashboardController::destroyFile` | None | Medium | 11C | Not yet |
| Telegram link management | `POST /client/dashboard/telegram/*` | `ClientDashboardController` | None | Low | 11C | Not yet — Telegram coupling |
| Profile edit | `GET/PATCH /profile` | `ProfileController` | None | Low | 12+ | No |

### 1.3 Admin Panel — Missing Features

| Feature | Current Blade Route | Controller / Service | Filament Status | Risk | Recommended Phase | Can Implement Now? |
|---|---|---|---|---|---|---|
| Topup request management | `GET/POST /admin/topup/*` | `TopupRequestController` | None | Medium | 11C | Not yet |
| Refund management | `GET/POST /admin/refunds/*` | `RefundController` | None | Medium | 11C | Not yet |
| Announcements CRUD | `GET/POST/DELETE /admin/announcements/*` | `AnnouncementController` | None | Low | 11C | Not yet |
| Client links management | `all /admin/client-links/*` | `ClientLinkController` | None | High | 12+ | No — complex, creates `/u/{token}` links |
| Pricing management | `all /admin/pricing/*` | `PricingController` | None | High | 12+ | No — affects billing |
| Payment settings | `all /admin/payment-settings/*` | `PaymentSettingsController` | None | High | 12+ | No — payment gateway config |
| Invite users | `POST /admin/accounts/invite` | `InviteController::store` | None | Low | 11C | Not yet |
| Billing ledger | `GET /admin/billing/*` | `BillingController` | None | Low | 12+ | No |
| Client matrix | `GET/POST/DELETE /admin/matrix/*` | `ClientMatrixController` | None | Medium | 12+ | No — legacy credit system |
| Account delete/restore/force | `DELETE/POST /admin/accounts/*` | `AccountManagerController` | Partial (freeze/unfreeze in Filament) | High | 12+ | No — destructive actions |
| Profile edit | `GET/PATCH /profile` | `ProfileController` | None | Low | 12+ | No |

### 1.4 Finance Panel — Missing Features

| Feature | Current Blade Route | Controller / Service | Filament Status | Risk | Recommended Phase | Can Implement Now? |
|---|---|---|---|---|---|---|
| Client balance summary | `GET /admin/finance/client-balances` | `ClientBalanceController::index` | **None** | Low | **11B** | **Yes** |
| Finance reports (HTML) | `GET /admin/finance/reports/*` | `FinanceReportController` | None (8 HTML views) | Medium | 12+ | No — complex, many views |
| Finance reports (CSV) | `GET /admin/finance/reports/*.csv` | `FinanceReportController` | None | Medium | Keep Blade | **Should remain Blade** |

---

## 2. Features Selected for Phase 11B Implementation

### Candidate A — Vendor Payout Request ✅

**What:** Add a "Request Payout" page to the vendor panel at `/vendor-panel/request-payout`.

**Current Blade workflow:**
- Route: `POST /earnings/request-payout`
- Controller: `VendorPayoutController::requestPayout()`
- Logic: Zero-input — reads vendor's full computed balance, creates `VendorPayoutRequest` record
- Guards: No duplicate pending requests; balance must be > 0
- Side effect: Sends Telegram alert via `PortalTelegramAlertService::notifyVendorPayoutRequested()`
- Model: `VendorPayoutRequest` (table: `vendor_payout_requests`)

**Filament implementation:**
- New page: `app/Filament/Vendor/Pages/RequestPayout.php`
- Shows: current approved payable balance, pending request status
- Action: "Request Payout" button (replicates existing logic exactly)
- History: table of vendor's own `VendorPayoutRequest` records
- Scoping: `user_id = auth()->id()` — vendor sees only own data

**Risk:** Low — read-only display + single POST action with existing guards

---

### Candidate B — Client Downloads ✅

**What:** Add a "My Downloads" page to the client panel at `/client-panel/my-downloads`.

**Current Blade workflow:**
- Route: `GET /client/downloads`
- Controller: `ClientDashboardController::downloads()`
- Query: Orders where `client_id = $client->id`, `source = 'account'`, status in [Processing, Delivered], updated in last 24h
- Shows: file name, token_view, status, download buttons (plag report, AI report, bundle)

**Filament implementation:**
- New page: `app/Filament/Client/Pages/MyDownloads.php`
- Shows: delivered orders with download actions
- Download actions: Plagiarism report, AI report (when available)
- Scoping: `client_id = auth()->user()->client->id`
- No time filter (show all delivered orders, not just 24h — more useful in Filament)

**Risk:** Low — read-only page with download actions

---

### Candidate C — Client Subscription Info ✅

**What:** Add a "My Subscription" page to the client panel at `/client-panel/my-subscription`.

**Current Blade workflow:**
- Route: `GET /client/subscription`
- Controller: `ClientSubscriptionController::index()`
- Shows: plan status, credits remaining, credits used, rate per file, payment history table, refund history table

**Filament implementation:**
- New page: `app/Filament/Client/Pages/MySubscription.php`
- Widgets: credit overview stats (balance, used, rate)
- Tables: payment history, refund history
- Read-only — no online payment integration
- Scoping: all data filtered by `client_id`

**Risk:** Low — read-only display of existing data

---

### Candidate D — Finance Client Balances ✅

**What:** Add a "Client Balances" page to the finance panel at `/filament-finance/client-balances`.

**Current Blade workflow:**
- Route: `GET /admin/finance/client-balances`
- Controller: `ClientBalanceController::index()`
- Query: All non-deleted clients with aggregated balance/payment/usage data
- Shows: summary cards (total remaining, total received, total used) + table per client

**Filament implementation:**
- New page: `app/Filament/Finance/Pages/ClientBalances.php`
- Summary stats widgets
- Table: client name, balance, total received, credits added, credits used, refunded, last payment
- Read-only — no actions
- No scoping needed (finance panel = admin only)

**Risk:** Low — read-only aggregation page

---

## 3. Features Deliberately Deferred

### Deferred to Phase 11C (next safe batch)

| Feature | Reason for deferral |
|---|---|
| Client topup request | Needs matching admin approval flow — both sides must exist |
| Client refund request | Needs matching admin approval flow |
| Admin topup management | Should ship with client-side topup request |
| Admin refund management | Should ship with client-side refund request |
| Admin announcements | Low priority; Blade works fine |
| Client order/file deletion | Involves credit refund logic — needs careful testing |
| Client Telegram management | Involves external Telegram API — needs careful scoping |
| Admin invite users | Low priority |

### Deferred to Phase 12+ (higher risk)

| Feature | Reason for deferral |
|---|---|
| Admin client links | Creates `/u/{token}` links — complex, high risk |
| Admin pricing | Affects billing rates — high risk |
| Admin payment settings | Payment gateway config — high risk |
| Admin billing ledger | Low priority, complex |
| Admin matrix | Legacy credit system — medium risk |
| Account delete/restore/force | Destructive actions — high risk |
| Finance reports (HTML) | 8 separate views — large effort |
| Profile edit | Low priority |

### Must remain Blade permanently

| Feature | Reason |
|---|---|
| Guest links `/u/{token}/*` | External URLs in circulation |
| Telegram webhook | External API |
| Auth routes (OTP, Telegram login) | Auth entry points |
| Signup + Razorpay | Payment webhook |
| CSV export routes | No Filament CSV equivalent; production finance exports |
| File serving `/orders/{order}/files/{file}` | Direct file serving |
| Announcement dismiss | Shared across panels |
| Client upload POST fallback | Safety fallback per Phase 8 spec |

---

## 4. Implementation Rules (Phase 11B)

- Use Filament pages/resources/widgets only
- Reuse existing models and services — no new tables, no new business logic
- No new Blade pages, no inline styles, no duplicate logic
- All data scoped to authenticated user (client/vendor) or admin-only (finance)
- No POST route retirement — old Blade routes remain untouched
- Tests must pass with same baseline (474+ passed, 13 pre-existing failures)

---

## 5. Security Checklist

| Check | Verified |
|---|---|
| Client sees only own orders/downloads | Scoped by `client_id` |
| Vendor sees only own payout requests | Scoped by `user_id` |
| Finance panel requires admin role | `FilamentPanelRole::class . ':admin'` middleware |
| No raw storage paths exposed to client | Downloads served via `Storage::download()` |
| Guest links untouched | No changes to `/u/{token}` routes |
| Telegram routes untouched | No changes to webhook routes |
