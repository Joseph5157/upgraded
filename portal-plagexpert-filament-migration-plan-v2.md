# Portal PlagExpert — Filament Migration Plan V2

## 1. Purpose of This V2 Plan

This document replaces the older `Portal PlagExpert Laravel Filament Plan.md` direction.

The old plan assumed a fresh Laravel + Filament rebuild. After reviewing the updated project, the better direction is different:

> **Do not restart blindly from zero. Keep the useful Laravel backend/finance logic, install Filament into the existing Laravel project, and gradually replace the hardcoded Blade UI with structured Filament panels.**

The current project already contains useful backend work for:

- Clients
- Vendors
- Orders
- Order files
- Order reports
- Credit balances
- Client payments
- Client credit transactions
- Vendor earning transactions
- Vendor payouts
- Business expenses
- Finance reports
- Finance dashboard services
- R2/storage support
- Telegram/WhatsApp notification services
- Audit logging

The problem is mainly UI maintainability and responsiveness, not the complete absence of backend logic.

---

## 2. Current Project Review Summary

### 2.1 Current Stack

The uploaded updated project is currently:

```txt
Laravel 12
Laravel Breeze
Blade views
Tailwind CSS
DaisyUI-style custom UI
Custom controllers
Custom service classes
MySQL/PostgreSQL compatible migrations
Redis/queue support
R2/S3 storage support
```

### 2.2 Important Finding

Filament is **not currently installed** in the updated project.

The UI is still mostly custom Blade pages with many repeated styles and large page files.

### 2.3 UI Maintainability Scan

Approximate scan from the uploaded project:

```txt
Blade files: 84
Total Blade lines: 13,185+
Inline style usages: 215+
Hardcoded hex color usages: 1,300+
Table elements: 41
Horizontal overflow wrappers: 38
Largest Blade page: resources/views/client/dashboard.blade.php ~986 lines
```

This confirms the core issue:

> Styling changes are difficult because UI is spread across large Blade files with hardcoded design and repeated layouts.

### 2.4 Current Production Readiness Rating

```txt
Business logic:        7.5/10
Finance backend:       7/10
UI/UX:                 5/10
Mobile responsiveness: 5/10
Maintainability:       5.5/10
Testing confidence:    5/10
Production readiness:  5.5/10
```

### 2.5 Main Decision

Do **not** spend more time polishing the current Blade UI.

Move to:

```txt
Existing Laravel backend + Filament panels
```

---

## 3. Final Architecture Direction

### 3.1 Keep

Keep the useful backend parts from the current project:

```txt
Existing migrations
Existing models
Finance service classes
Credit service classes
Vendor earning service classes
Vendor payout service classes
Business expense service classes
Order workflow service classes
File upload/report services
Audit logger
Storage configuration
Queue configuration
Notification services
Existing tests after fixing blockers
```

### 3.2 Replace Gradually

Gradually replace:

```txt
Large Blade dashboards
Hardcoded Blade tables
Custom finance Blade pages
Custom admin CRUD pages
Custom client dashboard UI
Custom vendor dashboard UI
Repeated Tailwind/daisyUI components
```

with:

```txt
Filament Panels
Filament Resources
Filament Tables
Filament Forms
Filament Actions
Filament Widgets
Filament Infolists
Filament Custom Pages
Filament Policy-based access
```

### 3.3 Do Not Duplicate Business Logic

Do not rewrite financial calculations inside Filament pages.

Filament should call the existing service classes wherever possible.

Example:

```txt
Filament Resource/Form Action -> calls ClientPaymentService
Filament Vendor Payout Action -> calls VendorPayoutService
Filament Dashboard Widget -> calls FinanceDashboardService
Filament Report Page -> calls FinanceReportService
```

This keeps business logic centralized.

---

## 4. Target Panel Structure

Create Filament panels gradually.

Recommended panel paths:

```txt
/admin
/finance
/client
/vendor
```

### 4.1 Admin Panel

For owner/admin/staff.

Responsibilities:

```txt
Clients
Vendors
Orders
Order files
Reports uploaded by vendors
Client links
Pricing settings
Payment settings
Announcements
Operational dashboard
```

### 4.2 Finance Panel

For owner/admin/accountant.

Responsibilities:

```txt
Client payments
Credit ledger
Client balances
Vendor earnings
Vendor payouts
Business expenses
Daily reports
Weekly reports
Monthly reports
Profit reports
Cash balance reports
Void/reversal actions
```

### 4.3 Client Panel

For clients.

Responsibilities:

```txt
Credit wallet
Upload files
View order status
Download completed reports
View payment history
View credit history
Submit support/refund request if needed
```

### 4.4 Vendor Panel

For vendors.

Responsibilities:

```txt
View assigned/claimed orders
Upload reports
Mark failed/skipped report parts
View completed work
View earnings summary
View payout history
```

---

## 5. Role and Access Rules

Use existing roles if already present, but standardize them.

Recommended roles:

```txt
owner / super_admin
admin
staff
accountant
client
vendor
```

If the current project uses different names, map them carefully and document the mapping.

### 5.1 Panel Access

```txt
Owner/super_admin -> all panels
Admin -> admin + finance if allowed
Staff -> admin operational pages only
Accountant -> finance panel only
Client -> client panel only
Vendor -> vendor panel only
```

### 5.2 Important Status Cleanup

Current project appears to use mixed status terms:

```txt
users.status = active / frozen
clients.status = active / suspended
```

This must be standardized or clearly mapped.

Recommended rule:

```txt
User login access is controlled by users.status.
Client business access is controlled by clients.status.
Vendor business access is controlled by users.status or vendor profile status.
```

Suggested standard:

```txt
users.status: active, inactive, frozen
clients.status: active, suspended, closed
vendors.status: active, suspended, closed
```

Acceptance rule:

```txt
Frozen/inactive user cannot login.
Suspended client cannot upload new files.
Suspended vendor cannot claim/upload work.
```

---

## 6. Business Rules to Preserve

### 6.1 Credit Rule

```txt
1 credit = 1 uploaded file
```

### 6.2 Client Credit Rate

Each client can have a different credit purchase rate.

```txt
Client A: ₹50 per credit
Client B: ₹70 per credit
Client C: ₹100 per credit
```

### 6.3 Vendor Payout Rate

Each vendor can have a different payout rate.

```txt
Vendor A: ₹20 per completed file
Vendor B: ₹35 per completed file
Vendor C: ₹50 per completed file
```

### 6.4 Credit Deduction

When a client uploads a file:

```txt
Deduct 1 credit
Create order/order file record
Create client credit transaction
Store snapshot of client rate
```

### 6.5 Credit Refund

When a file/order is cancelled before completion or eligible for refund:

```txt
Return credit only once
Create refund credit transaction
Mark related file/order status clearly
Log who performed the refund
```

### 6.6 Failed File Rule

If a vendor fails a file/report:

```txt
Mark failed/skipped reason
Do not count toward vendor payable
Do not count toward completed file profit
Admin decides refund/reassign/close
```

### 6.7 Vendor Payable Rule

Only completed/approved files count.

```txt
vendor_payable = sum(completed approved files * stored vendor rate)
```

### 6.8 Profit Rule

Profit must be based on completed files, not only credits sold.

```txt
revenue_recognized = sum(completed approved files * stored client rate)
gross_profit = revenue_recognized - vendor_payable
net_profit = revenue_recognized - vendor_payable - expenses
```

### 6.9 Cash Balance Rule

```txt
cash_balance = total_client_payments_received - vendor_payouts_paid - business_expenses
```

---

## 7. Existing Backend Components to Reuse

The updated project already contains important services. Reuse them instead of duplicating logic.

### 7.1 Finance Services

```txt
app/Services/Finance/ClientCreditService.php
app/Services/Finance/ClientPaymentService.php
app/Services/Finance/VendorEarningService.php
app/Services/Finance/VendorPayoutService.php
app/Services/Finance/BusinessExpenseService.php
app/Services/Finance/FinanceDashboardService.php
app/Services/Finance/FinanceReportService.php
app/Services/Finance/FinanceVoidService.php
app/Services/Finance/OpeningBalanceMigrationService.php
```

### 7.2 Workflow Services

```txt
app/Services/CreateClientOrderService.php
app/Services/DeleteClientOrderService.php
app/Services/OrderWorkflowService.php
app/Services/UploadVendorReportService.php
```

### 7.3 Support Services

```txt
app/Services/AuditLogger.php
app/Services/NotificationService.php
app/Services/TelegramService.php
app/Services/WhatsappService.php
app/Services/PortalTelegramAlertService.php
```

### 7.4 Rule

Filament resources/pages should use these services.

Do not place money/credit/payout logic directly inside Filament form callbacks unless it delegates to a service.

---

## 8. Existing Database to Respect

The current project already has migrations for:

```txt
orders
order_files
order_reports
vendor_payouts
vendor_payout_requests
razorpay_orders
payment_settings
client_payments
client_credit_transactions
vendor_earning_transactions
business_expenses
finance fields on clients/users/orders/vendor_payouts
void columns for payments/payouts/expenses
```

### 8.1 Important Instruction

Do not create duplicate tables with new names unless necessary.

Before creating any new migration, check whether a matching table already exists.

### 8.2 Recommended Table Strategy

```txt
Use existing orders table.
Use existing order_files table.
Use existing order_reports table.
Use existing client_payments table.
Use existing client_credit_transactions table.
Use existing vendor_earning_transactions table.
Use existing vendor_payouts table.
Use existing business_expenses table.
```

### 8.3 New Tables Only If Needed

Possible new tables later:

```txt
filament_activity_logs if existing audit logs are insufficient
report_exports if saved export history is needed
client_support_requests if support workflow is needed
file_failure_reviews if failed-file review needs separate audit trail
```

---

## 9. Critical Issues to Fix Before Filament Migration

### 9.1 Test Signature Blocker

Current issue found:

Parent service signature:

```php
public function sendMessage(string $chatId, string $text, array $options = []): int|false
```

Some tests override it with incompatible signature:

```php
public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, array $options = []): bool
```

Fix test fakes to match the parent signature:

```php
public function sendMessage(string $chatId, string $text, array $options = []): int|false
{
    return 1;
}
```

Files to check:

```txt
tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Auth/TelegramWebhookTest.php
```

### 9.2 Remove Accidental Root File

Remove accidental file:

```txt
value('portal_number'))
```

This looks like a failed Tinker/PsySH output artifact.

### 9.3 Status Logic Cleanup

Fix or document:

```txt
active / frozen / inactive
active / suspended
```

Make sure tests and middleware agree.

### 9.4 Test Suite Baseline

Before installing Filament, establish current test baseline:

```bash
php artisan test
```

Then fix known test failures or mark unrelated legacy tests clearly.

Goal before Phase 2:

```txt
Core auth, credit, finance, order workflow tests pass.
No PHP fatal signature errors.
No Mockery dirty-state errors.
```

---

## 10. Filament Installation Strategy

### 10.1 Branch

Create a new branch:

```bash
git checkout -b filament-migration-v2
```

### 10.2 Install Filament

Use Composer to install Filament.

```bash
composer require filament/filament
```

Then install/create panel files according to the Filament version used.

### 10.3 Do Not Remove Blade Immediately

Keep current Blade routes working initially.

Add Filament panels side-by-side.

This reduces deployment risk.

### 10.4 Route Strategy

During migration:

```txt
Existing Blade admin/client/vendor routes remain available.
New Filament routes are added under /admin-new or /admin initially if safe.
```

Recommended safer temporary paths:

```txt
/filament-admin
/filament-finance
/filament-client
/filament-vendor
```

After verification, switch final paths to:

```txt
/admin
/finance
/client
/vendor
```

---

## 11. UI/UX Rules for Filament Migration

### 11.1 Global Rules

```txt
No inline styles
No random hardcoded hex colors in pages
No repeated custom dashboard cards
No giant Blade-style pages recreated inside Filament
No business logic inside views
No finance calculations inside table columns unless delegated to service/accessor
```

### 11.2 Use Filament Components

Use:

```txt
Resources
Tables
Forms
Infolists
Actions
Bulk Actions
Widgets
StatsOverviewWidget
Chart widgets if needed
Custom Pages only where Resource is not suitable
```

### 11.3 Mobile Rules

Admin and finance can be desktop/table-heavy.

Client and vendor must be mobile-first:

```txt
Card layouts instead of wide tables
Large action buttons
Clear status badges
Simple dashboard cards
One-column upload forms on mobile
No horizontal scrolling on important client/vendor pages
```

### 11.4 Client Mobile Screen Priority

Client panel should prioritize:

```txt
Credits remaining
Upload new file
Pending orders
Reports ready
Payment history
Credit history
```

### 11.5 Vendor Mobile Screen Priority

Vendor panel should prioritize:

```txt
Assigned work
Pending uploads
Completed work
Failed/skipped work
Payable amount
Payout history
```

---

## 12. Updated Phase-wise Plan

## Phase 0 — Stabilize Existing Project Before Filament

### Goal

Clean the current codebase so Filament migration starts safely.

### Tasks

- Fix TelegramService fake method signatures in tests.
- Remove accidental root file `value('portal_number'))`.
- Run test suite and record baseline failures.
- Check current migrations run on fresh database.
- Check finance migrations are ordered correctly.
- Confirm current storage config works.
- Confirm queue config works.
- Confirm current auth works.
- Document current roles/status values.
- Document current routes that will be replaced by Filament.

### Deliverables

```txt
Clean branch
No accidental root files
No fatal test errors
Baseline test report
Role/status mapping document
Blade route inventory
```

### Acceptance Criteria

```txt
php artisan test does not crash with fatal signature errors
php artisan migrate:fresh works in local/test environment
Existing login still works
Existing finance services still load
```

---

## Phase 1 — Install Filament and Create Panel Skeletons

### Goal

Add Filament side-by-side without breaking old Blade UI.

### Tasks

- Install Filament.
- Create Admin panel.
- Create Finance panel.
- Create Client panel.
- Create Vendor panel.
- Add panel access rules.
- Add simple placeholder dashboards.
- Add user profile/password support if clean.
- Ensure inactive/frozen users cannot access panels.

### Recommended Temporary Paths

```txt
/filament-admin
/filament-finance
/filament-client
/filament-vendor
```

### Final Paths Later

```txt
/admin
/finance
/client
/vendor
```

### Deliverables

```txt
Filament installed
Four panel providers created
Panel access logic working
Placeholder dashboards working
```

### Acceptance Criteria

```txt
Owner can access all panels
Admin/staff can access admin panel
Accountant can access finance panel
Client can access client panel only
Vendor can access vendor panel only
Frozen/inactive users cannot access any panel
Old Blade UI still works
```

---

## Phase 2 — Filament Admin Foundation

### Goal

Move operational admin CRUD to Filament first.

### Resources to Create

```txt
UserResource
ClientResource
VendorResource
OrderResource
OrderFileResource
OrderReportResource
PaymentSettingResource
AnnouncementResource if needed
ClientLinkResource if needed
```

### Tasks

- Build ClientResource using existing clients table/model.
- Build VendorResource using existing users/vendors structure.
- Build UserResource carefully with role restrictions.
- Build OrderResource read/update screens.
- Build OrderFile/OrderReport views.
- Add filters by status, client, vendor, date.
- Add badges for statuses.
- Add safe actions only.

### Deliverables

```txt
Admin can manage clients
Admin can manage vendors
Admin can view orders/files/reports
Admin can filter operational queues
```

### Acceptance Criteria

```txt
Admin CRUD does not duplicate backend logic
All actions respect policies
Tables are usable on desktop/tablet
No inline styling
```

---

## Phase 3 — Filament Finance Panel

### Goal

Replace custom Blade finance pages with Filament finance resources/pages.

### Resources/Pages to Create

```txt
ClientPaymentResource
ClientCreditTransactionResource
VendorEarningTransactionResource
VendorPayoutResource
BusinessExpenseResource
FinanceDashboardPage
ClientBalancePage
VendorBalancePage
ProfitReportPage
CashBalanceReportPage
```

### Existing Services to Use

```txt
ClientPaymentService
ClientCreditService
VendorEarningService
VendorPayoutService
BusinessExpenseService
FinanceDashboardService
FinanceReportService
FinanceVoidService
```

### Tasks

- Build client payment entry form.
- Build credit ledger table.
- Build client balance view.
- Build vendor earning table.
- Build vendor payout creation flow.
- Build expense entry flow.
- Build void/reversal actions.
- Build finance dashboard widgets.
- Build date-range report filters.

### Deliverables

```txt
Finance panel usable by accountant/admin
Payment records managed in Filament
Credit ledger visible in Filament
Vendor payout workflow started
Business expenses managed in Filament
```

### Acceptance Criteria

```txt
Payment creates correct ledger entry
Credit balance matches ledger
Vendor payout only includes eligible completed earnings
Voided payments/payouts/expenses are audit-safe
Finance dashboard matches service calculations
```

---

## Phase 4 — Order and Credit Workflow Hardening

### Goal

Make order/file/credit workflows transaction-safe and clear before moving client/vendor UI.

### Tasks

- Audit order creation flow.
- Confirm 1 uploaded file deducts 1 credit.
- Confirm insufficient credit blocks upload.
- Confirm cancelled files refund credit only once.
- Confirm failed/skipped files do not count for vendor earning.
- Confirm completed reports create/confirm vendor earning.
- Add missing tests.
- Add idempotency guards.
- Ensure credit and earning actions are atomic DB transactions.

### Deliverables

```txt
Credit deduction tests
Credit refund tests
Vendor earning tests
Order cancellation tests
Failed file tests
```

### Acceptance Criteria

```txt
Credits cannot go negative
Refund cannot happen twice
One file cannot generate duplicate vendor earning
Failed file cannot enter payout
Profit calculations use completed/approved files only
```

---

## Phase 5 — Vendor Filament Panel

### Goal

Create mobile-friendly vendor work panel.

### Pages to Create

```txt
VendorDashboardPage
VendorAssignedOrdersPage
VendorPendingReportsPage
VendorCompletedReportsPage
VendorFailedReportsPage
VendorEarningsPage
VendorPayoutHistoryPage
```

### Tasks

- Vendor sees only assigned/claimable work allowed by policy.
- Vendor can upload report files.
- Vendor can mark report/file as failed/skipped with reason.
- Vendor can view earnings summary.
- Vendor can view payout history.
- Mobile layout must be card-first.

### Deliverables

```txt
Vendor panel usable on mobile
Vendor report upload works
Vendor failed-file reason flow works
Vendor earnings are visible
```

### Acceptance Criteria

```txt
Vendor cannot see other vendors' work
Vendor cannot upload to cancelled/delivered orders
Vendor cannot mark completed work twice
Vendor mobile screens have no important horizontal overflow
```

---

## Phase 6 — Client Filament Panel

### Goal

Create mobile-friendly client panel.

### Pages to Create

```txt
ClientDashboardPage
ClientCreditWalletPage
ClientUploadPage
ClientOrdersPage
ClientReportsPage
ClientPaymentHistoryPage
ClientCreditHistoryPage
```

### Tasks

- Client dashboard with credit balance and simple status cards.
- Mobile-first upload page.
- Client can see only own orders/reports/payments.
- Client can download completed reports only when authorized.
- Client can see credit ledger in simple language.
- Client cannot access admin/finance/vendor data.

### Deliverables

```txt
Client mobile dashboard
Client upload screen
Client order tracking
Client report download screen
Client wallet/history screen
```

### Acceptance Criteria

```txt
Client can upload from Android mobile easily
One file deducts one credit
Insufficient credits show clear message
Completed reports are easy to download
No important client screen has horizontal overflow
```

---

## Phase 7 — Reports and Dashboards

### Goal

Make reports clear, filterable, and reliable.

### Reports

```txt
Daily report
Weekly report
Monthly report
Date-range report
Client-wise report
Vendor-wise report
Profit report
Cash balance report
Payment mode report
Expense report
```

### Widgets

Admin dashboard:

```txt
Total orders
Pending files
Completed files
Failed files
Active clients
Active vendors
```

Finance dashboard:

```txt
Money received
Credits added
Credits used
Credits remaining
Vendor payable
Vendor paid
Expenses
Gross profit
Net profit
Cash balance
```

Client dashboard:

```txt
Credits remaining
Files uploaded
Files pending
Reports ready
```

Vendor dashboard:

```txt
Assigned files
Pending uploads
Completed files
Failed files
Payable amount
Paid amount
Balance amount
```

### Acceptance Criteria

```txt
Report numbers are generated from services/transactions
Date filters work correctly
Reports do not use hardcoded numbers
Finance dashboard and reports match each other
```

---

## Phase 8 — Blade UI Retirement

### Goal

Remove or freeze old hardcoded Blade screens after Filament equivalents are verified.

### Tasks

- List all old Blade routes.
- Mark replaced screens as deprecated.
- Redirect old admin finance routes to Filament finance routes.
- Redirect old admin pages to Filament resources if stable.
- Keep public/client upload links only if required.
- Remove unused Blade files after safe period.
- Remove unused controllers after safe period.
- Clean unused Tailwind/daisyUI components.

### Do Not Remove Immediately

Do not delete old screens until:

```txt
Filament replacement is tested
User roles confirmed
Financial calculations verified
File upload/download tested
Production backup exists
```

### Acceptance Criteria

```txt
No duplicate active UI for the same finance workflow
Old Blade finance pages are no longer primary
Old hardcoded dashboards are retired
Users are routed to Filament panels
```

---

## Phase 9 — Testing, Security, and Audit

### Goal

Make the migrated system safe for production.

### Tests to Add/Fix

```txt
Panel access tests
Client credit deduction tests
Credit refund tests
Client payment ledger tests
Vendor earning tests
Vendor payout tests
Business expense tests
Void/reversal tests
Client data isolation tests
Vendor data isolation tests
File download authorization tests
Mobile route smoke tests
```

### Security Checks

```txt
Client sees only own data
Vendor sees only own/assigned data
Finance user cannot mutate non-finance data unless permitted
Staff cannot delete financial records
Money/credit changes are logged
Files are not publicly accessible without authorization
```

### Acceptance Criteria

```txt
Core test suite passes
No fatal errors
No authorization leaks
Financial changes are auditable
Production backup plan exists
```

---

## Phase 10 — Production Deployment Strategy

### Goal

Deploy the Filament migration safely.

### Recommended Deployment Flow

1. Deploy backend fixes only.
2. Deploy Filament installed with hidden/temporary paths.
3. Test Filament panels in production with admin users only.
4. Move finance users to Filament finance panel.
5. Move admin users to Filament admin panel.
6. Move vendor users to Filament vendor panel.
7. Move client users to Filament client panel.
8. Retire old Blade pages gradually.

### Pre-Deployment Checklist

```txt
Database backup taken
Storage backup verified
php artisan migrate --pretend checked
Queue worker configured
Scheduler configured
R2/S3 credentials verified
APP_URL correct
SESSION_DOMAIN correct if used
Filament auth works
Owner account exists
Rollback plan ready
```

### Post-Deployment Checks

```txt
Login works
Admin panel loads
Finance panel loads
Client panel loads
Vendor panel loads
Client upload works
Vendor upload works
Report download works
Payment entry works
Credit ledger works
Vendor payout works
Expenses work
Reports match expected totals
```

---

## 13. Filament Resource Build Order

Build in this order:

```txt
1. UserResource
2. ClientResource
3. VendorResource
4. ClientPaymentResource
5. ClientCreditTransactionResource
6. BusinessExpenseResource
7. VendorEarningTransactionResource
8. VendorPayoutResource
9. OrderResource
10. OrderFileResource
11. OrderReportResource
12. FinanceDashboardPage
13. Report Pages
14. Client Custom Pages
15. Vendor Custom Pages
```

Reason:

```txt
Finance/Admin pages are table-heavy and ideal for Filament first.
Client/Vendor pages need more mobile UX care, so build them after workflows are stable.
```

---

## 14. Minimum First Codex/Claude Prompt

Use this first. Do not ask Codex/Claude to build the full project in one pass.

```txt
You are working on the existing Portal PlagExpert Laravel project.

I have attached the updated project and this file: `portal-plagexpert-filament-migration-plan-v2.md`.

Read this full file first.

Important direction:
Do not restart from zero.
Do not rebuild the backend from scratch.
Keep the current Laravel backend, migrations, models, finance services, order services, storage services, and audit logic.
The main goal is to install Filament and gradually replace the hardcoded Blade UI.

Start with Phase 0 and Phase 1 only.

Phase 0 tasks:
1. Fix the TelegramService test fake method signatures so they match the parent method:
   public function sendMessage(string $chatId, string $text, array $options = []): int|false
2. Remove the accidental root file named `value('portal_number'))` if present.
3. Run or inspect the test suite and document current baseline failures.
4. Confirm migrations run or identify migration blockers.
5. Document existing roles and status values.
6. Document old Blade routes that will eventually be replaced by Filament.

Phase 1 tasks:
1. Install Filament into the existing Laravel project.
2. Create four Filament panel skeletons using temporary safe paths:
   /filament-admin
   /filament-finance
   /filament-client
   /filament-vendor
3. Add access rules:
   - owner/super_admin can access all panels
   - admin/staff can access admin panel
   - accountant can access finance panel
   - client can access client panel only
   - vendor can access vendor panel only
4. Add placeholder dashboards only.
5. Do not build finance resources yet.
6. Do not remove old Blade UI yet.
7. Do not change business logic unless required for panel access.

Deliverables after this task:
1. Files created/modified
2. Commands run
3. Test results/baseline
4. Migration status
5. How to access each Filament panel
6. How panel access is controlled
7. Any risks or pending questions

Stop after Phase 0 and Phase 1. Do not proceed to Phase 2 until approved.
```

---

## 15. Main Warnings for the Developer

### 15.1 Do Not Mix UI Migration With Business Rewrite

Bad approach:

```txt
Install Filament + rewrite credits + rewrite orders + rewrite payouts all together
```

Good approach:

```txt
Install Filament shell first
Move one module at a time
Keep old service logic
Test each module before replacing old UI
```

### 15.2 Do Not Create Duplicate Financial Paths

Avoid having two active screens for the same financial action.

During transition, one should be primary and the other should be read-only or hidden.

### 15.3 Do Not Delete Blade Too Early

Old Blade pages are messy, but they are also working reference screens.

Delete only after Filament equivalents are tested.

### 15.4 Do Not Trust Dashboard Numbers Without Service Tests

All dashboard numbers must come from services/queries, not manually calculated in widgets.

---

## 16. Final Recommended Direction

The correct direction now is:

```txt
Do not restart from zero.
Use current upgraded Laravel project as the backend base.
Fix test blockers first.
Install Filament side-by-side.
Build Admin + Finance panels first.
Then build mobile-first Vendor panel.
Then build mobile-first Client panel.
Retire old Blade UI gradually.
Keep finance and credit logic centralized in services.
Use tests to protect credits, refunds, payouts, and reports.
```

Expected rating after successful migration:

```txt
Business logic:        8.5/10
Finance backend:       8.5/10
UI/UX:                 8/10
Mobile responsiveness: 7.5-8/10
Maintainability:       8.5/10
Production readiness:  8/10+
```

---

## 17. Short Decision Summary

```txt
Old plan: Fresh Laravel + Filament rebuild.
New V2 plan: Existing Laravel backend + Filament migration.

Reason:
The backend and finance logic have useful work already.
The main weakness is Blade UI maintainability and mobile responsiveness.
Filament should replace the UI gradually without throwing away business logic.
```
