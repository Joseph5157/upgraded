# Phase 11D — Remaining Missing Filament Features

## Date: 2026-06-25

---

## 1. Full Missing Features Audit

| Feature | Blade Route | Controller | Business Logic | Risk | Implement Now? | Reason |
|---|---|---|---|---|---|---|
| Admin Announcements | `GET/POST/DELETE /admin/announcements/*` | `AnnouncementController` | Simple CRUD, Gate `manage-announcements` (admin) | Low | **Yes** | No external deps, simple model |
| Admin Pricing | `GET/POST /admin/pricing/*` | `PricingController` | Update `clients.price_per_file`, `users.payout_rate` | Low | **Yes** | Two simple field updates |
| Admin Client Links | `all /admin/client-links/*` | `ClientLinkController` | Create/revoke/view links, AuditLogger | Medium | **Yes** (read + create/revoke only) | No destructive deletes |
| Client Order Deletion | `DELETE /client/orders/{order}/delete` | `ClientDashboardController::destroy` | `DeleteClientOrderService` — safe, service-based | Medium | **Yes** | Service handles credit refund safely |
| Admin Payment Settings | `all /admin/payment-settings/*` | `PaymentSettingsController` | UPI config, QR code upload, activate/deactivate | Medium | **Defer** | File upload + storage lifecycle |
| Admin Invite | `POST /admin/accounts/invite` | `InviteController` | Creates PendingInvite, generates Telegram link | Low | **Defer** | Depends on Telegram bot config |
| Admin Billing | `GET /admin/billing/*` | `BillingController` | DailyLedger read-only | Low | **Defer** | Low priority, legacy ledger |
| Admin Matrix | `GET/POST/DELETE /admin/matrix/*` | `ClientMatrixController` | Legacy credit refill, client delete | High | **Defer** | Legacy slots system, destructive |
| Account Delete/Restore/Force | `DELETE/POST /admin/accounts/*` | `AccountManagerController` | Soft-delete, restore, force-delete | High | **Defer** | Destructive actions |
| Client File Deletion | `DELETE /client/orders/{order}/files/{file}` | `ClientDashboardController::destroyFile` | Direct file delete, pending-only guard | Medium | **Defer** | Needs careful scoping in Filament |
| Client Telegram Management | `POST /client/dashboard/telegram/*` | `ClientDashboardController` | Telegram link regen, test message | Low | **Defer** | External Telegram API coupling |
| Finance Reports (HTML) | `GET /admin/finance/reports/*` | `FinanceReportController` | 8 HTML report views | Medium | **Defer** | Large effort, 8 views |
| Profile Edit | `GET/PATCH /profile` | `ProfileController` | User profile update | Low | **Defer** | Low priority |
| Signup Flow | `GET/POST /signup/*` | `SignupController` | Razorpay integration | High | **Never** | External payment webhook |
| Guest Links | `all /u/{token}/*` | `OrderController` | Public guest flow | Critical | **Never** | External URLs in circulation |
| CSV Exports | `GET /admin/finance/reports/*.csv` | `FinanceReportController` | CSV download | Medium | **Never** | Keep Blade — no Filament equivalent |

---

## 2. Features Implemented in Phase 11D

### A. Admin Announcements Resource

**Current:** `AnnouncementController` with Gate `manage-announcements` (admin-only)
**Filament:** Resource at `/filament-admin/announcements`
- List all announcements with status badges
- Create new announcement (title, message, target, type, expires_at)
- Toggle active/inactive via table action
- Delete via table action
- No notification logic (none exists in current system)

### B. Admin Pricing Page

**Current:** `PricingController` — lists clients + vendors with editable rates
**Filament:** Page at `/filament-admin/pricing`
- Two tables: client pricing, vendor pricing
- Inline edit action for `price_per_file` (client) and `payout_rate` (vendor)
- Read-only display of current rates
- No business logic changes

### C. Admin Client Links Resource

**Current:** `ClientLinkController` — CRUD for guest upload links
**Filament:** Resource at `/filament-admin/client-links`
- List all client links with client name, token, status, dates
- Create new link for a client (generates random token)
- Revoke link (sets is_active=false, revoked_at, revoked_by)
- View link orders in relation manager
- **Deferred:** delete link, delete client, delete order (destructive)
- Does NOT break `/u/{token}` public links

### D. Client Order Deletion Action

**Current:** `ClientDashboardController::destroy` → `DeleteClientOrderService::execute()`
**Filament:** Delete action on `MyOrdersResource` table
- Only visible for unclaimed pending orders
- Delegates to `DeleteClientOrderService::execute()`
- Handles credit refund through `ClientCreditService`
- Confirmation required
- Shows success message with credit restoration info

---

## 3. Features Deferred

| Feature | Reason |
|---|---|
| Admin Payment Settings | File upload complexity, QR code storage lifecycle |
| Admin Invite | Telegram bot dependency |
| Admin Billing | Legacy DailyLedger, low priority |
| Admin Matrix | Legacy slots system, destructive operations |
| Account Delete/Restore/Force | Destructive actions need careful review |
| Client File Deletion | Individual file delete needs Filament scoping work |
| Client Telegram Management | External Telegram API |
| Finance Reports HTML | 8 separate views, large effort |
| Profile Edit | Low priority |

---

## 4. Security Rules

- Admin resources behind `FilamentPanelRole:admin`
- Client actions scoped by `client_id`
- Client cannot delete non-pending or claimed orders
- Client cannot access admin panel
- Vendor cannot access admin or client panels
- Public `/u/{token}` links untouched
- AuditLogger used for client link operations

---

## 5. Test Plan

- Admin can CRUD announcements
- Admin can view/edit pricing
- Admin can view/create/revoke client links
- Client can delete own unclaimed pending order
- Client cannot delete claimed/processing/delivered orders
- Role-based access denied for wrong roles
- Blade routes still work (regression)
