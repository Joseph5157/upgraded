# Blade Retirement Plan — Portal PlagExpert

**Status:** Draft — audit only. No deletions, redirects, or route changes until each stage is explicitly approved.

**Date drafted:** 2026-06-24
**Branch:** filament-migration-phase-0-1
**Test baseline:** 551 passed, same pre-existing failures, no regressions

---

## Table of Contents

1. [Background](#1-background)
2. [Route Audit](#2-route-audit)
3. [Controller Audit](#3-controller-audit)
4. [View Audit](#4-view-audit)
5. [Workflow Coverage Map](#5-workflow-coverage-map)
6. [Routes Safe for Future Redirect](#6-routes-safe-for-future-redirect)
7. [Routes That Must Remain](#7-routes-that-must-remain)
8. [Routes Needing Filament Replacement First](#8-routes-needing-filament-replacement-first)
9. [Browser Verification Checklist](#9-browser-verification-checklist)
10. [Recommended Retirement Sequence](#10-recommended-retirement-sequence)
11. [Risks](#11-risks)
12. [Rollback Plan](#12-rollback-plan)

---

## 1. Background

Portal PlagExpert is currently running a **dual-stack architecture**:

- **Blade stack** — original production system. All core workflows served through `routes/web.php`, traditional controllers, and Blade templates.
- **Filament stack** — layered on top across four panels: `/filament-admin`, `/filament-finance`, `/client-panel`, `/vendor-panel`.

As of Phase 8.1, the following workflows have fully migrated into Filament:

| Workflow | Filament Panel | Phase |
|---|---|---|
| Client dashboard | `/client-panel` | Phase 4 |
| Client upload files | `/client-panel/upload-files` | Phase 8 |
| Client: My Orders | `/client-panel/my-orders` | Phase 4 |
| Client: Credit wallet | `/client-panel/credit-wallet` | Phase 4 |
| Client: Payment history | `/client-panel/payment-history` | Phase 4 |
| Vendor dashboard | `/vendor-panel` | Phase 5 |
| Vendor: My Work (claim/process/deliver) | `/vendor-panel/my-work` | Phase 5 |
| Vendor: Upload report | `/vendor-panel/my-work/{id}` | Phase 7B |
| Vendor: Mark Failed | `/vendor-panel/my-work/{id}` | Phase 7C |
| Vendor: Earning history | `/vendor-panel/earning-history` | Phase 5 |
| Vendor: Payout history | `/vendor-panel/payout-history` | Phase 5 |
| Admin dashboard | `/filament-admin` | Phase 2 |
| Admin: Users | `/filament-admin/users` | Phase 2 |
| Admin: Clients | `/filament-admin/clients` | Phase 2 |
| Admin: Vendors | `/filament-admin/vendors` | Phase 2 |
| Admin: Orders (view/requeue) | `/filament-admin/orders` | Phase 2 + 7D |
| Finance dashboard | `/filament-finance` | Phase 3 |
| Finance: Client payments | `/filament-finance/client-payments` | Phase 3 |
| Finance: Credit transactions | `/filament-finance/client-credit-transactions` | Phase 3 |
| Finance: Vendor earnings | `/filament-finance/vendor-earning-transactions` | Phase 3 |
| Finance: Vendor payouts | `/filament-finance/vendor-payouts` | Phase 3 |
| Finance: Business expenses | `/filament-finance/business-expenses` | Phase 3 |

**Blade routes are not yet retired.** Both stacks run in parallel.

---

## 2. Route Audit

### Legend

| Status | Meaning |
|---|---|
| **Keep** | Do not touch — critical, no replacement, or shared infrastructure |
| **Redirect later** | Filament replacement exists; can add GET redirect after approval |
| **Can retire after browser verification** | POST equivalent exists in Filament; retire only after smoke tests confirm |
| **Needs Filament replacement first** | No equivalent in Filament yet |
| **Do not touch yet** | External links, auth, or risky — requires full analysis before any change |

---

### 2.1 Public / Guest Routes

| Route | Method | Name | Controller | User | Purpose | Filament replacement? | Recommendation | Risk |
|---|---|---|---|---|---|---|---|---|
| `/` | GET | — | closure (role-based redirect) | all | Root redirect | N/A | Keep | Low |
| `/csrf-token-public` | GET | `csrf.token.public` | closure | anon | CSRF refresh for upload pages | N/A | Keep | Low |
| `/csrf-refresh` | GET | `csrf.refresh` | closure | auth | CSRF refresh for long sessions | N/A | Keep | Low |
| `/telegram/webhook/{secret}` | POST | `telegram.webhook` | `BotController::webhook` | anon | Telegram bot receiver | N/A | Keep | High — external Telegram API |
| `/signup` | GET | `signup.show` | `SignupController::show` | guest | New client signup | None | Needs Filament replacement first | High |
| `/signup/initiate` | POST | `signup.initiate` | `SignupController::initiate` | guest | Signup form submission | None | Needs Filament replacement first | High |
| `/signup/success` | GET | `signup.success` | `SignupController::success` | guest | Signup confirmation | None | Needs Filament replacement first | High |
| `/webhooks/razorpay` | POST | `webhooks.razorpay` | `SignupController::webhook` | anon | Razorpay payment webhook | N/A | Keep | High — payment callback |
| `/login` | GET | `login` | `OtpLoginController::showLogin` | guest | OTP login page | N/A | Keep | High — auth entry |
| `/login/send-otp` | POST | `login.send-otp` | `OtpLoginController::sendOtp` | guest | Send OTP | N/A | Keep | High |
| `/login/verify-otp` | POST | `login.verify-otp` | `OtpLoginController::verifyOtp` | guest | Verify OTP | N/A | Keep | High |
| `/auth/telegram/{token}` | GET | `telegram.login` | `TelegramLoginController::authenticate` | guest | Telegram auth link | N/A | Keep | High — external links in chats |

### 2.2 Client Public Link Routes (Unauthenticated)

| Route | Method | Name | Controller | User | Purpose | Filament replacement? | Recommendation | Risk |
|---|---|---|---|---|---|---|---|---|
| `/u/{token}` | GET | `client.upload` | `OrderController::showUpload` | anon | Guest upload page via client link | None | Do not touch yet | Very High — shared via WhatsApp/external |
| `/u/{token}/pulse` | GET | `client.link.pulse` | `OrderController::guestPulse` | anon | Live-poll for guest upload | None | Do not touch yet | High |
| `/u/{token}/orders/{order}/pulse` | GET | `client.link.track.pulse` | `OrderController::guestPulse` | anon | Live-poll for guest tracking | None | Do not touch yet | High |
| `/u/{token}` | POST | `client.store` | `OrderController::store` | anon | Submit order via client link | None | Do not touch yet | Very High |
| `/u/{token}/orders/{order}` | GET | `client.link.track` | `OrderController::trackGuest` | anon | Track order (guest) | None | Do not touch yet | High |
| `/u/{token}/orders/{order}/download` | GET | `client.link.download` | `OrderController::downloadGuest` | anon | Download report (guest) | None | Do not touch yet | High |
| `/track/{token_view}` | GET | `client.track` | `OrderController::track` | anon | Track any order by token | None | Do not touch yet | High |
| `/download/{token_view}` | GET | `client.download` | `OrderController::download` | anon | Download by token | None | Do not touch yet | High |

### 2.3 Vendor / Admin Dashboard Routes

| Route | Method | Name | Controller | User | Purpose | Filament replacement? | Recommendation | Risk |
|---|---|---|---|---|---|---|---|---|
| `/dashboard` | GET | `dashboard` | `DashboardController::index` | vendor, admin | Vendor work queue | `/vendor-panel` | Redirect later | Medium |
| `/dashboard/pulse` | GET | `dashboard.pulse` | `DashboardController::pulse` | vendor, admin | Live updates | Filament widgets | Keep until redirect approved | Medium |
| `/orders/{order}/claim` | POST | `orders.claim` | `DashboardController::claim` | vendor, admin | Claim an order | Filament action | Can retire after browser verification | Medium |
| `/orders/{order}/unclaim` | POST | `orders.unclaim` | `DashboardController::unclaim` | vendor, admin | Unclaim an order | Filament action | Can retire after browser verification | Medium |
| `/orders/{order}/status` | POST | `orders.status` | `DashboardController::updateStatus` | vendor, admin | Advance status | Filament action | Can retire after browser verification | Medium |
| `/orders/{order}/report` | POST | `orders.report` | `DashboardController::uploadReport` | vendor, admin | Upload report | `/vendor-panel/my-work/{id}` | Can retire after browser verification | Medium |
| `/orders/{order}/files/{file}` | GET | `orders.files.download` | `DashboardController::downloadFile` | vendor, admin | Download order file | None (direct file serving) | Keep | Medium — file serving |
| `/earnings` | GET | `vendor.earnings` | `VendorEarningsController::index` | vendor | Earnings overview | `/vendor-panel/earning-history` | Redirect later | Low |
| `/earnings/request-payout` | POST | `earnings.request-payout` | `VendorPayoutController::requestPayout` | vendor | Request payout | None | Needs Filament replacement first | Medium |

### 2.4 Client Dashboard Routes

| Route | Method | Name | Controller | User | Purpose | Filament replacement? | Recommendation | Risk |
|---|---|---|---|---|---|---|---|---|
| `/client/dashboard` | GET | `client.dashboard` | `ClientDashboardController::index` | client | Client dashboard | `/client-panel` | Redirect later | Medium |
| `/client/dashboard/pulse` | GET | `client.dashboard.pulse` | `ClientDashboardController::pulse` | client | Live poll | Filament widgets | Keep until redirect approved | Medium |
| `/client/dashboard/upload` | POST | `client.dashboard.upload` | `ClientDashboardController::store` | client | Upload file | `/client-panel/upload-files` | Keep (safety fallback) | Low — tested in Phase 8.1 |
| `/client/dashboard/telegram/regenerate-link` | POST | `client.dashboard.telegram.regenerate` | `ClientDashboardController::regenerateTelegramLink` | client | Regen Telegram link | None | Needs Filament replacement first | Low |
| `/client/dashboard/telegram/test` | POST | `client.dashboard.telegram.test` | `ClientDashboardController::sendTelegramTest` | client | Test Telegram | None | Needs Filament replacement first | Low |
| `/client/orders/{order}/delete` | DELETE | `client.orders.delete` | `ClientDashboardController::destroy` | client | Delete order | None (Filament view-only) | Needs Filament replacement first | Medium |
| `/client/orders/{order}/files/{file}` | DELETE | `client.orders.files.delete` | `ClientDashboardController::destroyFile` | client | Delete file | None | Needs Filament replacement first | Medium |
| `/client/topup` | POST | `client.topup.store` | `TopupRequestController::store` | client | Request topup | None | Needs Filament replacement first | Medium |
| `/client/refunds` | POST | `client.refunds.store` | `RefundController::store` | client | Request refund | None | Needs Filament replacement first | Medium |
| `/client/subscription` | GET | `client.subscription` | `ClientSubscriptionController::index` | client | Subscription info | None | Needs Filament replacement first | Low |
| `/client/downloads` | GET | `client.downloads` | `ClientDashboardController::downloads` | client | Delivered reports list | None | Needs Filament replacement first | Low |
| `/announcements/{}/dismiss` | POST | `announcements.dismiss` | `AnnouncementController::dismiss` | auth | Dismiss banner | None | Keep | Low |

### 2.5 Admin Routes

| Route | Method | Name | Controller | Purpose | Filament replacement? | Recommendation | Risk |
|---|---|---|---|---|---|---|---|
| `/admin/dashboard` | GET | `admin.dashboard` | `AdminDashboardController::index` | Admin overview | `/filament-admin` | Redirect later | Low |
| `/admin/accounts/invite` | POST | `admin.accounts.invite` | `InviteController::store` | Invite users | None | Needs Filament replacement first | Low |
| `/admin/matrix` | GET/POST | `admin.matrix.*` | `ClientMatrixController` | Client credit matrix | None | Needs Filament replacement first | Medium |
| `/admin/matrix/{client}/refill` | POST | `admin.matrix.refill` | `ClientMatrixController::refill` | Refill credits | None | Needs Filament replacement first | Medium |
| `/admin/matrix/{client}` | DELETE | `admin.matrix.destroy` | `ClientMatrixController::destroy` | Delete client | None | Needs Filament replacement first | High |
| `/admin/topup` | GET | `admin.topup.index` | `TopupRequestController::index` | Topup requests | None | Needs Filament replacement first | Medium |
| `/admin/topup/{}/approve` | POST | `admin.topup.approve` | `TopupRequestController::approve` | Approve topup | None | Needs Filament replacement first | Medium |
| `/admin/topup/{}/reject` | POST | `admin.topup.reject` | `TopupRequestController::reject` | Reject topup | None | Needs Filament replacement first | Medium |
| `/admin/billing` | GET | `admin.billing.index` | `BillingController::index` | Billing ledger | None | Needs Filament replacement first | Low |
| `/admin/billing/{ledger}` | GET | `admin.billing.show` | `BillingController::show` | Billing detail | None | Needs Filament replacement first | Low |
| `/admin/finance/dashboard` | GET | `admin.finance.dashboard` | `FinanceDashboardController::index` | Finance overview | `/filament-finance` | Redirect later | Low |
| `/admin/finance/reports` | GET | `admin.finance.reports.*` | `FinanceReportController` | Finance reports | Filament finance panel | Redirect later (HTML) / Keep (CSV) | Low (HTML), Medium (CSV) |
| `/admin/finance/reports/*.csv` | GET | various | `FinanceReportController` | CSV exports | None (Filament has no CSV export yet) | Keep | Medium — production exports |
| `/admin/finance/payouts` | GET/POST | `admin.finance.payouts.*` | `AdminVendorPayoutController` | Vendor payouts | `/filament-finance/vendor-payouts` | Redirect later | Low |
| `/admin/finance/client-payments` | GET/POST | `admin.finance.client-payments.*` | `ClientPaymentController` | Client payments | `/filament-finance/client-payments` | Redirect later | Low |
| `/admin/finance/client-credit-transactions` | GET | `admin.finance.client-credit-transactions.index` | `ClientCreditTransactionController::index` | Credit ledger | `/filament-finance/client-credit-transactions` | Redirect later | Low |
| `/admin/finance/client-balances` | GET | `admin.finance.client-balances.index` | `ClientBalanceController::index` | Client balances | None | Needs Filament replacement first | Low |
| `/admin/finance/expenses` | GET/POST | `admin.finance.expenses.*` | `BusinessExpenseController` | Expenses | `/filament-finance/business-expenses` | Redirect later | Low |
| `/admin/finance/vendor-earnings` | GET | `admin.finance.vendor-earnings.index` | `VendorEarningController::index` | Earning approvals | `/filament-finance/vendor-earning-transactions` | Redirect later | Medium |
| `/admin/finance/vendor-earnings/{}/approve` | POST | `admin.finance.vendor-earnings.approve` | `VendorEarningController::approve` | Approve earning | Filament action | Can retire after browser verification | Medium |
| `/admin/finance/vendor-earnings/{}/reject` | POST | `admin.finance.vendor-earnings.reject` | `VendorEarningController::reject` | Reject earning | Filament action | Can retire after browser verification | Medium |
| `/admin/refunds` | GET | `admin.refunds.index` | `RefundController::index` | Refund requests | None | Needs Filament replacement first | Medium |
| `/admin/refunds/{}/approve` | POST | `admin.refunds.approve` | `RefundController::approve` | Approve refund | None | Needs Filament replacement first | Medium |
| `/admin/refunds/{}/reject` | POST | `admin.refunds.reject` | `RefundController::reject` | Reject refund | None | Needs Filament replacement first | Medium |
| `/admin/announcements` | GET/POST/DELETE | `admin.announcements.*` | `AnnouncementController` | Announcements CRUD | None | Needs Filament replacement first | Low |
| `/admin/accounts` | GET | `admin.accounts.index` | `AccountManagerController::index` | Account listing | `/filament-admin/users` | Redirect later | Low |
| `/admin/accounts/{}/freeze` | POST | `admin.accounts.freeze` | `AccountManagerController::freeze` | Freeze account | Filament action | Can retire after browser verification | Low |
| `/admin/accounts/{}/unfreeze` | POST | `admin.accounts.unfreeze` | `AccountManagerController::unfreeze` | Unfreeze account | Filament action | Can retire after browser verification | Low |
| `/admin/accounts/{}/` | DELETE | `admin.accounts.destroy` | `AccountManagerController::destroy` | Soft-delete account | None | Needs Filament replacement first | High |
| `/admin/accounts/{}/restore` | POST | `admin.accounts.restore` | `AccountManagerController::restore` | Restore account | None | Needs Filament replacement first | High |
| `/admin/accounts/{}/force` | DELETE | `admin.accounts.forceDelete` | `AccountManagerController::forceDelete` | Permanent delete | None | Needs Filament replacement first | Very High |
| `/admin/client-links` | all | `admin.client-links.*` | `ClientLinkController` | Client link management | None | Needs Filament replacement first | High |
| `/admin/pricing` | all | `admin.pricing.*` | `PricingController` | Client/vendor pricing | None | Needs Filament replacement first | High |
| `/admin/payment-settings` | all | `admin.payment-settings.*` | `PaymentSettingsController` | Payment gateway config | None | Needs Filament replacement first | High |
| `/profile` | GET/PATCH | `profile.*` | `ProfileController` | User profile | None | Needs Filament replacement first | Low |

---

## 3. Controller Audit

### 3.1 Root Controllers

| Controller | Active route? | Filament replacement? | Business logic to move? | Safe to retire? | Needs tests first? |
|---|---|---|---|---|---|
| `AccountManagerController` | Yes — `/admin/accounts/*` | Partial (UserResource exists) | Freeze/unfreeze/soft-delete/restore/force-delete logic | No — missing Filament destructive actions | Yes |
| `AdminDashboardController` | Yes — `/admin/dashboard` | Yes — `/filament-admin` | No (view-only, stats from widgets) | After redirect approved | No |
| `AnnouncementController` | Yes — GET/POST/DELETE + dismiss | No | Announcement CRUD | No | Yes |
| `BillingController` | Yes — `/admin/billing/*` | No | Billing ledger | No | Yes |
| `BotController` | Yes — `/telegram/webhook/{secret}` | N/A | Telegram webhook processing | Never | N/A |
| `ClientDashboardController` | Yes — 8 routes | Partial (dashboard, upload, orders in Filament) | Telegram link regen, file deletion, downloads | No — several endpoints still needed | Yes |
| `ClientMatrixController` | Yes — `/admin/matrix/*` | No | Credit refill logic | No | Yes |
| `ClientSubscriptionController` | Yes — `/client/subscription` | No | Subscription display | No | No |
| `Controller` | Base class | N/A | N/A | No | N/A |
| `DashboardController` | Yes — 7 routes | Partial (vendor panel replaces dashboard/workflow) | File download serving | No — `downloadFile` still needed | Yes |
| `LedgerController` | Check if routed | Unknown | Finance ledger | Verify first | Yes |
| `MatrixController` | Check if routed | No | Matrix logic | Verify first | Yes |
| `OrderController` | Yes — 8 public routes `/u/*` + `/track/*` + `/download/*` | None | Guest link logic | Never | N/A |
| `ProfileController` | Yes — `/profile` | No | Profile update | No | No |
| `RefundController` | Yes — client POST + admin CRUD | No | Refund workflow | No | Yes |
| `SignupController` | Yes — signup + Razorpay webhook | No | Signup + payment flow | Never | N/A |
| `TopupRequestController` | Yes — client POST + admin CRUD | No | Topup workflow | No | Yes |
| `VendorEarningsController` | Yes — `/earnings` | Yes — `/vendor-panel/earning-history` | View only | After redirect approved | No |
| `VendorPayoutController` | Yes — `/earnings/request-payout` | No | Payout request submission | No | Yes |

### 3.2 Auth Controllers

| Controller | Active route? | Safe to retire? |
|---|---|---|
| `Auth/AuthenticatedSessionController` | Required by `auth.php` | Never |
| `Auth/OtpLoginController` | Yes — all login routes | Never |
| `Auth/TelegramLoginController` | Yes — `/auth/telegram/{token}` | Never |

### 3.3 Admin Subdirectory Controllers

| Controller | Active route? | Filament replacement? | Safe to retire? |
|---|---|---|---|
| `Admin/ClientLinkController` | Yes — 8 routes | No | No |
| `Admin/InviteController` | Yes — `/admin/accounts/invite` | No | No |
| `Admin/PaymentSettingsController` | Yes — 5 routes | No | No |
| `Admin/PricingController` | Yes — 3 routes | No | No |
| `Admin/VendorEarningController` | Yes — 3 routes | Partial (Filament finance panel) | No — approve/reject POSTs still active |

### 3.4 Admin/Finance Controllers

| Controller | Active route? | Filament replacement? | Safe to retire? |
|---|---|---|---|
| `Admin/Finance/BusinessExpenseController` | Yes — 4 routes | Yes — `/filament-finance/business-expenses` | After browser verification & redirect approved |
| `Admin/Finance/ClientBalanceController` | Yes — 1 route | No | No |
| `Admin/Finance/ClientCreditTransactionController` | Yes — 1 route | Yes — `/filament-finance/client-credit-transactions` | After redirect approved |
| `Admin/Finance/ClientPaymentController` | Yes — 4 routes | Yes — `/filament-finance/client-payments` | After browser verification & redirect approved |
| `Admin/Finance/FinanceDashboardController` | Yes — 1 route | Yes — `/filament-finance` | After redirect approved |
| `Admin/Finance/FinanceReportController` | Yes — 16 routes (HTML + CSV) | HTML: partial; CSV: No | No — CSV exports have no Filament equivalent |
| `Admin/Finance/VendorPayoutController` | Yes — 4 routes | Yes — `/filament-finance/vendor-payouts` | After browser verification & redirect approved |

---

## 4. View Audit

### 4.1 Active Production Views

| View | Route/Controller | Filament replacement? | Classification |
|---|---|---|---|
| `dashboard.blade.php` | `DashboardController::index` | `/vendor-panel` | Replaced by Filament — retire after redirect |
| `dashboard/partials/available-queue.blade.php` | Vendor dashboard | `/vendor-panel` widgets | Replaced by Filament — retire after redirect |
| `dashboard/partials/live.blade.php` | `DashboardController::pulse` | Filament real-time | Replaced by Filament — retire after redirect |
| `dashboard/partials/workspace.blade.php` | Vendor order card | Filament table row | Replaced by Filament — retire after redirect |
| `partials/workspace-card.blade.php` | Vendor card partial | Filament table | Replaced by Filament — retire after redirect |
| `partials/workspace-row.blade.php` | Vendor row partial | Filament table | Replaced by Filament — retire after redirect |
| `partials/workspace-upload-modal.blade.php` | Vendor upload modal | `/vendor-panel/my-work/{id}` modal | Replaced by Filament — retire after redirect |
| `partials/client-upload-modal.blade.php` | Client dashboard modal | `/client-panel/upload-files` | Replaced by Filament — retire after redirect |
| `client/dashboard.blade.php` | `ClientDashboardController::index` | `/client-panel` | Replaced by Filament — retire after redirect |
| `client/dashboard/partials/live.blade.php` | `ClientDashboardController::pulse` | Filament widgets | Replaced by Filament — retire after redirect |
| `client/upload.blade.php` | Probably legacy | `/client-panel/upload-files` | Verify usage — likely can retire |
| `client/upload/partials/live.blade.php` | Probably legacy | Filament upload page | Verify usage — likely can retire |
| `client/track.blade.php` | `OrderController::trackGuest` | None | Keep — guest tracking |
| `client/track/partials/live.blade.php` | `OrderController::guestPulse` | None | Keep — guest live poll |
| `client/downloads.blade.php` | `ClientDashboardController::downloads` | None | Keep — no Filament replacement |
| `client/profile.blade.php` | `ProfileController` (client context) | None | Keep — no Filament replacement |
| `client/subscription.blade.php` | `ClientSubscriptionController::index` | None | Keep — no Filament replacement |
| `admin/dashboard.blade.php` | `AdminDashboardController::index` | `/filament-admin` | Replaced by Filament — retire after redirect |
| `admin/matrix/index.blade.php` | `ClientMatrixController::index` | None | Keep — no Filament replacement |
| `admin/accounts/index.blade.php` | `AccountManagerController::index` | `/filament-admin/users` | Replaced by Filament — retire after redirect |
| `admin/billing/index.blade.php` | `BillingController::index` | None | Keep — no Filament replacement |
| `admin/billing/show.blade.php` | `BillingController::show` | None | Keep — no Filament replacement |
| `admin/topups.blade.php` | `TopupRequestController::index` | None | Keep — no Filament replacement |
| `admin/refunds.blade.php` | `RefundController::index` | None | Keep — no Filament replacement |
| `admin/announcements.blade.php` | `AnnouncementController::index` | None | Keep — no Filament replacement |
| `admin/pricing/index.blade.php` | `PricingController::index` | None | Keep — no Filament replacement |
| `admin/payment-settings/index.blade.php` | `PaymentSettingsController::index` | None | Keep — no Filament replacement |
| `admin/client-links/index.blade.php` | `ClientLinkController::index` | None | Keep — no Filament replacement |
| `admin/client-links/orders.blade.php` | `ClientLinkController::showOrders` | None | Keep — no Filament replacement |
| `admin/profile.blade.php` | `ProfileController` (admin context) | None | Keep — no Filament replacement |
| `admin/finance/dashboard.blade.php` | `FinanceDashboardController::index` | `/filament-finance` | Replaced by Filament — retire after redirect |
| `admin/finance/client-payments/index.blade.php` | `ClientPaymentController::index` | `/filament-finance/client-payments` | Replaced by Filament — retire after redirect |
| `admin/finance/client-payments/show.blade.php` | `ClientPaymentController::show` | Filament view page | Replaced by Filament — retire after redirect |
| `admin/finance/client-credit-transactions/index.blade.php` | `ClientCreditTransactionController::index` | Filament resource | Replaced by Filament — retire after redirect |
| `admin/finance/client-balances/index.blade.php` | `ClientBalanceController::index` | None | Keep — no Filament replacement |
| `admin/finance/expenses/index.blade.php` | `BusinessExpenseController::index` | `/filament-finance/business-expenses` | Replaced by Filament — retire after redirect |
| `admin/finance/expenses/show.blade.php` | `BusinessExpenseController::show` | Filament view page | Replaced by Filament — retire after redirect |
| `admin/finance/payouts.blade.php` | `AdminVendorPayoutController::index` | `/filament-finance/vendor-payouts` | Replaced by Filament — retire after redirect |
| `admin/finance/payouts-show.blade.php` | `AdminVendorPayoutController::show` | Filament view page | Replaced by Filament — retire after redirect |
| `admin/finance/vendor-earnings/pending.blade.php` | `VendorEarningController::index` | `/filament-finance/vendor-earning-transactions` | Replaced by Filament — retire after redirect |
| `admin/finance/reports/*.blade.php` (8 files) | `FinanceReportController` | Partial — HTML views replaced, CSV routes must stay | HTML: retire after redirect; CSV: keep |
| `admin/finance/ledger.blade.php` | Unknown | Unknown | Verify usage before touching |
| `admin/finance/matrix.blade.php` | Unknown | None | Verify usage before touching |
| `vendor/earnings.blade.php` | `VendorEarningsController::index` | `/vendor-panel/earning-history` | Replaced by Filament — retire after redirect |
| `vendor/profile.blade.php` | `ProfileController` (vendor context) | None | Keep — no Filament replacement |

### 4.2 Shared Components — Do Not Remove

| Component | Used by | Notes |
|---|---|---|
| `components/order-status-badge.blade.php` | Client dashboard, guest tracking, admin views | Still in use by Blade guest routes |
| `components/admin-layout.blade.php` | All admin Blade views | Keep until all admin routes retired |
| `components/announcements-banner.blade.php` | Layouts | Keep — active feature |
| `components/application-logo.blade.php` | Auth layout | Keep |
| `components/auth-session-status.blade.php` | Login page | Keep |
| `layouts/app.blade.php` | All authenticated Blade views | Keep |
| `layouts/guest.blade.php` | Login, signup, public pages | Keep |
| `layouts/vendor.blade.php` | Vendor Blade views | Keep until vendor Blade retired |
| `layouts/navigation.blade.php` | Auth layout | Keep |
| `layouts/_sidebar-nav.blade.php` | Admin sidebar | Keep until admin Blade retired |
| `auth/login.blade.php` | OTP login | Keep |
| `signup/show.blade.php`, `signup/success.blade.php` | Signup flow | Keep |
| `errors/*.blade.php` | Framework | Keep |
| `welcome.blade.php` | Root | Keep (or verify if used) |

### 4.3 Filament-Specific Views (keep, not part of Blade stack)

| View | Purpose |
|---|---|
| `filament/client/pages/client-dashboard.blade.php` | Client panel dashboard (Livewire) |
| `filament/client/pages/upload-files.blade.php` | Upload page (Livewire) |

---

## 5. Workflow Coverage Map

### 5.1 Client Workflows

| Workflow | Blade status | Filament status | Action |
|---|---|---|---|
| Login (OTP) | Active | N/A | Keep Blade — auth |
| Dashboard (order list + balance) | Active `/client/dashboard` | Complete `/client-panel` | Both exist in parallel |
| Upload file | Active `POST /client/dashboard/upload` | Complete `/client-panel/upload-files` | Both exist in parallel — verified Phase 8.1 |
| My Orders | Part of dashboard | Complete `/client-panel/my-orders` | Both exist in parallel |
| Report download (guest link) | Active `/u/{token}/*` | None | Blade only — keep |
| Report download (auth) | `/client/downloads` | None | Blade only — keep |
| Credit wallet | Part of dashboard | Complete `/client-panel/credit-wallet` | Both exist in parallel |
| Payment history | Part of dashboard | Complete `/client-panel/payment-history` | Both exist in parallel |
| Failed status display | Active (client/dashboard.blade.php) | Complete (MyOrdersResource) | Both exist in parallel |
| Topup request | Active `/client/topup` | None | **Needs Filament replacement** |
| Refund request | Active `/client/refunds` | None | **Needs Filament replacement** |
| Subscription info | Active `/client/subscription` | None | **Needs Filament replacement** |
| Telegram link management | Active (2 routes) | None | **Needs Filament replacement** |
| Order file deletion | Active (DELETE routes) | None | **Needs Filament replacement** |
| Order deletion | Active (DELETE route) | None | **Needs Filament replacement** |
| Guest order tracking | Active `/track/*` | None | **Blade only — do not retire** |

### 5.2 Vendor Workflows

| Workflow | Blade status | Filament status | Action |
|---|---|---|---|
| Login (OTP) | Active | N/A | Keep Blade — auth |
| Dashboard (work queue) | Active `/dashboard` | Complete `/vendor-panel` | Both exist in parallel |
| Assigned work / claim | Active `POST /orders/{order}/claim` | Complete (action in MyWorkResource) | Both exist in parallel |
| Unclaim | Active `POST /orders/{order}/unclaim` | Complete (action) | Both exist in parallel |
| Start processing | Active `POST /orders/{order}/status` | Complete (action) | Both exist in parallel |
| Upload report | Active `POST /orders/{order}/report` | Complete `/vendor-panel/my-work/{id}` | Both exist in parallel — Phase 7B |
| Mark Failed | Active (via `POST /orders/{order}/status`) | Complete (action in MyWorkResource) | Both exist in parallel — Phase 7C |
| Earning history | Active `/earnings` | Complete `/vendor-panel/earning-history` | Both exist in parallel |
| Payout history | Part of earnings view | Complete `/vendor-panel/payout-history` | Both exist in parallel |
| Request payout | Active `POST /earnings/request-payout` | None | **Needs Filament replacement** |
| File download | Active `GET /orders/{order}/files/{file}` | None (served directly) | **Blade only — keep** |
| Earnings overview | Active `/earnings` | Complete in Filament | Both exist in parallel |

### 5.3 Admin Workflows

| Workflow | Blade status | Filament status | Action |
|---|---|---|---|
| Dashboard | Active `/admin/dashboard` | Complete `/filament-admin` | Both exist in parallel |
| Users management | Active `/admin/accounts` | Complete `/filament-admin/users` | Both exist in parallel |
| Clients management | Active `/admin/matrix` | Complete `/filament-admin/clients` | Partial parallel (matrix is specialized) |
| Vendors management | None separate | Complete `/filament-admin/vendors` | Filament only |
| Orders management | None separate in Blade | Complete `/filament-admin/orders` | Filament only |
| Failed order requeue | None in Blade | Complete (Phase 7D) | Filament only |
| Account freeze/unfreeze | Active (POST routes) | Filament actions | Both exist in parallel |
| Account delete/restore/force-delete | Active | Partial in Filament | Both exist in parallel |
| Topup requests | Active `/admin/topup/*` | None | **Needs Filament replacement** |
| Refunds | Active `/admin/refunds/*` | None | **Needs Filament replacement** |
| Announcements | Active `/admin/announcements/*` | None | **Needs Filament replacement** |
| Client links | Active `/admin/client-links/*` | None | **Needs Filament replacement** |
| Pricing | Active `/admin/pricing/*` | None | **Needs Filament replacement** |
| Payment settings | Active `/admin/payment-settings/*` | None | **Needs Filament replacement** |
| Invite users | Active `POST /admin/accounts/invite` | None | **Needs Filament replacement** |
| Billing ledger | Active `/admin/billing/*` | None | **Needs Filament replacement** |

### 5.4 Finance Workflows

| Workflow | Blade status | Filament status | Action |
|---|---|---|---|
| Finance dashboard | Active `/admin/finance/dashboard` | Complete `/filament-finance` | Both exist in parallel |
| Client payments | Active (CRUD routes) | Complete `/filament-finance/client-payments` | Both exist in parallel |
| Credit ledger | Active | Complete `/filament-finance/client-credit-transactions` | Both exist in parallel |
| Vendor earnings (view/approve/reject) | Active | Complete `/filament-finance/vendor-earning-transactions` | Both exist in parallel |
| Vendor payouts | Active (CRUD routes) | Complete `/filament-finance/vendor-payouts` | Both exist in parallel |
| Business expenses | Active (CRUD routes) | Complete `/filament-finance/business-expenses` | Both exist in parallel |
| Finance reports (HTML) | Active — 8 report views | Partial — Filament has data but no report pages | HTML reports: **Needs Filament replacement** |
| Finance reports (CSV exports) | Active — 8 CSV routes | None | **Keep — no Filament equivalent** |
| Client balance summary | Active | None | **Needs Filament replacement** |
| Voiding transactions | Active (POST void routes) | Complete (Filament actions) | Both exist in parallel |

---

## 6. Routes Safe for Future Redirect

These routes have complete Filament replacements and can be changed to server-side redirects after explicit approval. No code should be deleted until the redirect has been in production for a confidence window.

| Blade route | Redirect target | Pre-condition |
|---|---|---|
| `GET /dashboard` | `GET /vendor-panel` | Browser verification of vendor panel |
| `GET /earnings` | `GET /vendor-panel/earning-history` | Browser verification of earning history |
| `GET /client/dashboard` | `GET /client-panel` | Browser verification of client panel |
| `GET /admin/dashboard` | `GET /filament-admin` | Browser verification of admin panel |
| `GET /admin/finance/dashboard` | `GET /filament-finance` | Browser verification of finance panel |
| `GET /admin/finance/client-payments` | `GET /filament-finance/client-payments` | Browser verification |
| `GET /admin/finance/client-credit-transactions` | `GET /filament-finance/client-credit-transactions` | Browser verification |
| `GET /admin/finance/vendor-earnings` | `GET /filament-finance/vendor-earning-transactions` | Browser verification |
| `GET /admin/finance/payouts` | `GET /filament-finance/vendor-payouts` | Browser verification |
| `GET /admin/finance/expenses` | `GET /filament-finance/business-expenses` | Browser verification |
| `GET /admin/finance/reports` (HTML only) | `GET /filament-finance` | Requires Filament report pages first |
| `GET /admin/accounts` | `GET /filament-admin/users` | Browser verification |

**GET-only redirects are safe.** POST/DELETE/PATCH routes must never be blindly redirected — they carry form data.

---

## 7. Routes That Must Remain

These routes cannot be retired and must not be touched in any upcoming phase:

| Route | Reason |
|---|---|
| All `/u/{token}/*` routes | Public guest links shared via WhatsApp/external. Cannot be moved without breaking existing links in circulation. |
| `/track/{token_view}` | Public guest tracking — same risk as above |
| `/download/{token_view}` | Public file download — same risk |
| `/telegram/webhook/{secret}` | External Telegram API — cannot be changed without reconfiguring production webhook |
| `/auth/telegram/{token}` | Telegram login tokens are distributed to users, must keep working |
| `/login`, `/login/send-otp`, `/login/verify-otp` | Auth entry point — never remove |
| `/signup/*` | Active signup flow with Razorpay webhook |
| `/webhooks/razorpay` | Payment provider callback — external dependency |
| `POST /client/dashboard/upload` | Kept as safety fallback per Phase 8 spec |
| `GET /orders/{order}/files/{file}` | File serving — no Filament file delivery endpoint |
| `/admin/finance/reports/*.csv` | CSV export routes — no Filament CSV equivalent exists |
| `/announcements/{}/dismiss` | Used by the announcements banner on all panels |

---

## 8. Routes Needing Filament Replacement First

Before these Blade routes can be retired, the corresponding Filament feature must be built, tested, and verified in browser:

| Blade route group | Missing Filament feature |
|---|---|
| `POST /earnings/request-payout` | Vendor payout request form in `/vendor-panel` |
| `DELETE /client/orders/{order}` | Client order deletion in Filament |
| `DELETE /client/orders/{order}/files/{file}` | Client file deletion in Filament |
| `POST /client/dashboard/telegram/regenerate-link` | Telegram management page in `/client-panel` |
| `POST /client/dashboard/telegram/test` | Telegram test in `/client-panel` |
| `POST /client/topup` | Client topup request form in `/client-panel` |
| `POST /client/refunds` | Client refund request form in `/client-panel` |
| `GET /client/subscription` | Subscription info page in `/client-panel` |
| `GET /client/downloads` | Delivered reports page in `/client-panel` |
| `/admin/topup/*` | Topup request management in `/filament-admin` |
| `/admin/refunds/*` | Refund management in `/filament-admin` |
| `/admin/announcements/*` | Announcement management in `/filament-admin` |
| `/admin/client-links/*` | Client link management in `/filament-admin` |
| `/admin/pricing/*` | Pricing management in `/filament-admin` or `/filament-finance` |
| `/admin/payment-settings/*` | Payment settings in `/filament-admin` |
| `POST /admin/accounts/invite` | Invite form in `/filament-admin` |
| `/admin/billing/*` | Billing ledger in `/filament-admin` or `/filament-finance` |
| `/admin/matrix/*` | Client credit matrix in `/filament-admin` |
| `/admin/finance/client-balances` | Client balance summary in `/filament-finance` |
| `/admin/finance/reports` (HTML views) | Finance report pages in `/filament-finance` |
| `/profile` | Profile edit in Filament panel pages |
| `/signup/*` | Signup flow (may remain Blade — depends on branding decision) |

---

## 9. Browser Verification Checklist

Complete these checks before retiring any route. They must be done by a human with a real browser.

### 9.1 Client Panel Checklist (before retiring client Blade routes)

```
[ ] /client-panel renders at login (correct credits shown)
[ ] /client-panel/upload-files renders correctly
[ ] File picker accepts PDF, DOC, DOCX, ZIP
[ ] File picker rejects invalid types (e.g. .exe)
[ ] Upload with 1 credit succeeds → redirects to /client-panel/my-orders
[ ] New order visible in My Orders list immediately
[ ] Credit balance decrements after upload (page refresh)
[ ] Upload with 0 credits shows error, no deduction
[ ] "Upload New File" button in My Orders navigates to upload page
[ ] Mobile 360px — form and button not clipped
[ ] Mobile 390px — same
[ ] Mobile 430px — same
[ ] Mobile 768px — same
[ ] /client/dashboard still loads (old Blade — regression check)
[ ] /client/dashboard/upload POST still works (regression check)
[ ] Telegram link management (if not yet in Filament, verify Blade still works)
```

### 9.2 Vendor Panel Checklist (before retiring vendor Blade routes)

```
[ ] /vendor-panel renders at login with work queue
[ ] Available orders shown in queue
[ ] Claim order action works
[ ] Unclaim action works
[ ] Start Processing action works
[ ] Upload report modal opens and submits (AI skipped path)
[ ] Upload report — file appears on order
[ ] Mark Failed modal opens and requires reason
[ ] Requeue from admin panel reflects in vendor queue
[ ] /vendor-panel/earning-history shows correct earnings
[ ] /vendor-panel/payout-history shows history
[ ] Payout request form (when built) submits correctly
[ ] Mobile vendor panel works — tables scroll correctly
[ ] /dashboard still loads (old Blade — regression check)
[ ] /orders/{order}/report POST still works (regression check)
```

### 9.3 Admin Panel Checklist (before retiring admin Blade routes)

```
[ ] /filament-admin renders dashboard with stats
[ ] /filament-admin/users lists all users with role filter
[ ] /filament-admin/clients lists clients with balances
[ ] /filament-admin/orders lists orders with status filters
[ ] /filament-admin/orders/{id} shows full order detail + requeue action
[ ] Failed order requeue works end-to-end
[ ] /admin/dashboard still loads (regression check)
```

### 9.4 Finance Panel Checklist (before retiring finance Blade routes)

```
[ ] /filament-finance renders dashboard with totals
[ ] /filament-finance/client-payments — create, view, void all work
[ ] /filament-finance/vendor-payouts — create, view, void all work
[ ] /filament-finance/business-expenses — create, view, void all work
[ ] /filament-finance/client-credit-transactions — visible, searchable
[ ] /filament-finance/vendor-earning-transactions — approve/reject works
[ ] CSV export routes still work: /admin/finance/reports/client-payments.csv
[ ] All other CSV routes return downloadable files
[ ] /admin/finance/dashboard still loads (regression check)
```

---

## 10. Recommended Retirement Sequence

### Stage 1 — No deletion (current state — Phase 9)

- Keep all Blade routes.
- Add deprecation notes in code comments.
- Confirm Filament replacements via browser.
- Document gaps requiring new Filament features.

### Stage 2 — GET redirects only (Phase 10)

Requires explicit approval. Only after Stage 1 browser verification is complete.

```php
// web.php — add above existing routes
Route::get('/dashboard', fn() => redirect('/vendor-panel'))->middleware('auth');
Route::get('/earnings', fn() => redirect('/vendor-panel/earning-history'))->middleware('auth');
Route::get('/client/dashboard', fn() => redirect('/client-panel'))->middleware('auth');
Route::get('/admin/dashboard', fn() => redirect('/filament-admin'))->middleware('auth');
Route::get('/admin/finance/dashboard', fn() => redirect('/filament-finance'))->middleware('auth');
// etc.
```

Keep all POST/DELETE routes intact. Monitor for any users hitting redirect loops.

### Stage 3 — POST route retirement (Phase 11+)

Only after Stage 2 has been stable in production for a confidence window (minimum 1 week of production traffic).

Retire in this order:
1. Vendor workflow POSTs (`/orders/{order}/claim`, `/unclaim`, `/status`, `/report`) — replaced by Filament actions
2. Finance operation POSTs — after confirming Filament parity (approve/reject/void)
3. Client upload POST `POST /client/dashboard/upload` — kept longest as final safety fallback

Do not retire until matching Filament features have been verified in production.

### Stage 4 — Missing-feature Filament build (Phase 12+)

Build the missing Filament features required before remaining Blade routes can retire:
- Vendor payout request in `/vendor-panel`
- Client topup/refund/subscription/downloads in `/client-panel`
- Announcement management, client links, pricing, payment settings in `/filament-admin`
- Finance report pages (HTML) in `/filament-finance`
- Profile pages in both panels

### Stage 5 — View and controller cleanup (Phase 13+)

Only after all routes are retired:
- Remove unused Blade views (keep shared components until last)
- Remove unused controller methods
- Remove dead routes
- Remove obsolete feature tests covering retired code paths
- Remove shared components that are no longer referenced

### Do not remove in this phase or the next

```
All auth routes and controllers
BotController and Telegram routes
OrderController and all /u/* routes
SignupController and signup routes
Razorpay webhook
/orders/{order}/files/{file} file-serving route
All /admin/finance/*.csv routes
All shared Blade components
Error views
Email/notification views
```

---

## 11. Risks

| Risk | Severity | Details |
|---|---|---|
| Guest links in circulation | Critical | `/u/{token}` links are distributed to external clients and shared via WhatsApp. Any removal or URL change will break active orders. |
| Telegram webhook reconfiguration | High | The webhook URL is registered with BotFather. Changing it requires a production API call and is error-prone. |
| CSV export routes | High | Finance team uses `/admin/finance/reports/*.csv` for external reporting (possibly shared with accountants). No Filament equivalent exists. |
| Client link routes | High | `/admin/client-links` creates the `/u/{token}` links. Retiring this without a Filament replacement blocks link creation. |
| CSRF token mismatch on long sessions | Medium | The `/csrf-token-public` and `/csrf-refresh` endpoints are required by the Blade upload forms. Remove only after forms are gone. |
| Matrix credit refill | Medium | `/admin/matrix/{client}/refill` is the primary way to top up client credits. No Filament equivalent yet. |
| Payout request form | Medium | Vendors use `/earnings/request-payout` to trigger payouts. No Filament replacement means vendors lose this capability if retired early. |
| Announcement banner | Low | The dismiss endpoint `POST /announcements/{}/dismiss` is called from shared Blade layout. If announcements are added to Filament layouts, the dismiss route must also work there. |
| Role-based redirect at `/` | Low | The root redirect closure reads `auth()->user()->role`. If new roles are added, this must be updated alongside any Filament panel changes. |

---

## 12. Rollback Plan

### For GET redirect rollback (Stage 2)

Redirects are added as new route definitions above existing routes. To rollback:
- Remove or comment out the redirect route lines.
- No data is changed, no routes are deleted.
- Users re-routed back to Blade automatically.

### For POST route retirement rollback (Stage 3)

Before retiring any POST route:
1. Tag the current commit before deletion.
2. Keep retired controller methods in place but commented out (not deleted) for 2 weeks.
3. To rollback: restore the route + uncomment the controller method + deploy.

### For view/controller deletion rollback (Stage 5)

Views and controllers must be deleted from version control only, not from production disk on first deploy. Rollback by reverting the git commit and redeploying.

### Emergency rollback

If production users report broken workflows after any retirement step:
1. Revert the `routes/web.php` change via `git revert`.
2. Deploy immediately — no database changes are involved.
3. Investigate before re-attempting.

---

## Appendix: Filament Panel URLs Reference

| Panel | Base URL | Roles with access |
|---|---|---|
| Admin | `/filament-admin` | `admin` |
| Finance | `/filament-finance` | `admin`, `accountant` |
| Client | `/client-panel` | `client` |
| Vendor | `/vendor-panel` | `vendor` |
