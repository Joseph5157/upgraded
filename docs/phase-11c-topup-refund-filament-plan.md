# Phase 11C — Client Topup/Refund Request + Admin Approval Flows

## Date: 2026-06-25

---

## 1. Existing Blade Workflow Audit

### Topup Requests

**Routes:**
- `POST /client/topup` → `TopupRequestController::store()` — **DISABLED**. Returns error: "Self-service top-up is no longer available. Please contact the admin."
- `GET /admin/topup` → `TopupRequestController::index()` — Admin views all topup requests
- `POST /admin/topup/{topupRequest}/approve` → `TopupRequestController::approve()` — Admin approves (legacy: adds `slots`, NOT `credit_balance`)
- `POST /admin/topup/{topupRequest}/reject` → `TopupRequestController::reject()` — Admin rejects

**Model:** `TopupRequest` — `topup_requests` table
- Fields: `client_id`, `amount_requested`, `amount_paid`, `transaction_id`, `status`, `notes`, `reviewed_at`
- Statuses: `pending`, `approved`, `rejected`
- Relationship: `belongsTo(Client)`

**Policy:** `TopupRequestPolicy` — admin-only `viewAny`, `approve`, `reject`

**Business Rule:** Self-service topup is intentionally disabled. Credits are added by admin via Client Payments system. The topup approval still writes to `clients.slots` (frozen column), not `credit_balance`. This is legacy behavior.

**Notifications:** `PortalTelegramAlertService::notifyTopupSubmitted()` (deprecated), `notifyTopupApproved()` (deprecated)

### Refund Requests

**Routes:**
- `POST /client/refunds` → `RefundController::store()` — Client submits refund request against own order
- `GET /admin/refunds` → `RefundController::index()` — Admin views all refund requests
- `POST /admin/refunds/{refundRequest}/approve` → `RefundController::approve()` — Admin approves (uses `ClientCreditService::refundOrderIfDebited()`)
- `POST /admin/refunds/{refundRequest}/reject` → `RefundController::reject()` — Admin rejects

**Model:** `RefundRequest` — `refund_requests` table
- Fields: `order_id`, `client_id`, `user_id`, `status`, `reason`, `admin_note`, `resolved_at`
- Statuses: `pending`, `approved`, `rejected`
- Relationships: `belongsTo(Order)`, `belongsTo(Client)`, `belongsTo(User)`

**Policy:** `RefundRequestPolicy` — admin `viewAny`/`approve`/`reject`, client `create`

**Business Rules:**
- Only orders in `Delivered`, `Processing`, or `Claimed` status are refundable
- No duplicate pending refund for same order
- Approval uses `ClientCreditService::refundOrderIfDebited()` inside a DB transaction with row locking
- Only refunds credits if a `TYPE_ORDER_DEBIT` transaction exists for the order
- Reactivates suspended client if credit balance becomes positive after refund
- Admin note stored on approval/rejection

**Notifications:** None currently for refund requests

### Existing Filament Coverage

- `MySubscription` page already shows refund history in a "Refund History" tab
- No dedicated client refund request creation page in Filament
- No admin Filament resource for topup or refund requests

---

## 2. Filament Replacement Design

### Client Panel

#### A. Refund Requests Page (`/client-panel/refund-requests`)

New Filament page with:
- Table of client's own refund requests (scoped by `client_id`)
- "Request Refund" action that opens a modal form
- Form fields: order selector (only refundable orders), reason text
- Validation mirrors `RefundController::store()` rules
- Shows status badges: pending (warning), approved (success), rejected (danger)
- Shows admin note for resolved requests

#### B. Topup Request History Page (`/client-panel/topup-requests`)

New Filament page with:
- "Contact admin" info banner (preserves disabled self-service rule)
- Read-only table of client's own topup request history
- No create action (self-service disabled)
- Shows status, amount requested, notes, dates

### Admin Panel

#### A. Topup Request Resource (`/filament-admin/topup-requests`)

New Filament resource with:
- List all topup requests with filters (status, client, date)
- View individual request
- Approve action (header action on view page) — reuses existing legacy `slots` logic
- Reject action with admin note modal
- Cannot approve/reject non-pending requests
- Busts `admin_nav_pending_topups` cache

#### B. Refund Request Resource (`/filament-admin/refund-requests`)

New Filament resource with:
- List all refund requests with filters (status, client, date)
- View individual request with order details
- Approve action — delegates to `ClientCreditService::refundOrderIfDebited()` inside DB transaction
- Reject action with admin note modal
- Cannot approve/reject non-pending requests
- Busts `admin_nav_pending_refunds` cache

---

## 3. Services Reused

- `ClientCreditService::refundOrderIfDebited()` — for refund approval
- `PortalTelegramAlertService::notifyTopupApproved()` — for topup approval (legacy)
- Cache busting for `admin_nav_pending_topups` and `admin_nav_pending_refunds`

## 4. Financial Safety Rules

- Refund approval: delegates to `ClientCreditService` inside `DB::transaction()` with `lockForUpdate()`
- No direct `credit_balance` manipulation in Filament actions
- Topup approval: uses existing legacy `slots` logic (does NOT affect `credit_balance`)
- No double-approval (status check before action)
- No record deletion

## 5. Security Rules

- Client pages scoped by `client_id` from `auth()->user()->client`
- Admin resources require `admin` role via panel middleware
- Client cannot access admin resources
- Vendor/accountant cannot access client panel
- Frozen users blocked by `account.status` middleware on Blade routes and `FilamentPanelRole` on panels

## 6. Test Plan

- Client can view own refund requests
- Client can create refund request for own refundable order
- Client cannot create refund for non-refundable order
- Client cannot create duplicate pending refund
- Client cannot view other client's requests
- Client can view own topup request history
- Client cannot create topup request (disabled)
- Vendor/admin/accountant cannot access client pages
- Admin can view all topup/refund requests
- Admin can approve pending topup request
- Admin can reject pending topup request
- Admin can approve pending refund request
- Admin can reject pending refund request
- Cannot approve already-resolved request
- Old Blade routes still work (regression)

## 7. Deferred Items

- Topup self-service remains disabled
- No new migrations needed (tables exist)
- No Blade deletion
- No POST route retirement
