# PlagExpert Portal — Credits, Ledger, Finance & Vendor Payout Implementation Plan

## Purpose of this document

This document is for Claude/AI coding agent implementation.

The project is an existing Laravel-based PlagExpert/Portal system deployed on Railway. It already has Admin, Client, Vendor roles, Telegram login/OTP, client file uploads, vendor report uploads, top-up requests, slots, vendor payouts, and dashboards.

The current payment/finance system is weak because credits/slots, money received, files checked, vendor earnings, vendor payouts, expenses, and profit are not connected in a reliable ledger-style flow. The goal is to introduce a proper credits and finance ledger system so the business can clearly know:

- How much money was received.2
- How many credits were added.
- How many credits/files were used.
- How many credits remain with clients.
- How many files were uploaded.
- How many files were completed.
- How much vendor payable is pending.
- How much vendor amount was already paid.
- What is the gross profit.
- What is the cash balance.
- Client-wise balances.
- Vendor-wise balances.

---

## Current project context

### Stack

- Backend: Laravel 12
- Frontend: Blade, Tailwind CSS, Alpine.js, Vite
- Deployment: Railway
- Storage: likely Cloudflare R2 for files/reports
- Auth: Telegram OTP / Telegram bot based login
- Roles: Admin, Client, Vendor

### Existing finance-like concepts

The existing project has concepts such as:

- Client slots / file balance
- Top-up requests
- Client price per file
- Vendor payout rate
- Vendor payouts
- Order upload and report delivery
- Daily ledger/reporting style pages

These should not be blindly removed in one step. The new system should introduce proper credits and ledgers, then migrate/replace the old slots logic safely.

---

## User-approved business decisions

These decisions must be followed exactly unless changed later by the project owner.

### 1. Credit meaning

For now:

```text
1 credit = 1 file check
```

When a client uploads one file, one credit is deducted.

Future flexibility: keep the design extensible for page-based or urgency-based credit calculation, but do not implement that now.

### 2. Client pricing

Each client can have a different rate per file.

Example:

```text
Client A: ₹50 per file
Client B: ₹40 per file
Client C: ₹70 per file
```

Every order must save the client rate snapshot at the time of order creation or financial locking. Do not calculate old order revenue using the current client price because rates can change later.

### 3. Vendor payout rate

Each vendor can have a different payout rate per file.

Example:

```text
Vendor A: ₹20 per file
Vendor B: ₹25 per file
Vendor C: ₹30 per file
```

Every order must save the vendor payout rate snapshot when vendor work is submitted/accepted. Do not calculate old vendor payable using the current vendor rate because rates can change later.

### 4. Vendor earning timing and failure handling

Vendor uploads completed report, but there may be failures/rejections.

Required approach:

- When vendor uploads completed report, create a **pending vendor earning** or provisional earning.
- Do not include pending earnings in final paid payout unless the system/admin allows it.
- When admin approves/delivers the report, convert pending earning into approved payable or mark it as approved.
- If the report fails/rejected, reverse/remove the pending earning and record a failure/reversal transaction.
- Vendor payable dashboard must clearly separate:
  - Pending review earning
  - Approved payable
  - Paid amount
  - Reversed/failed amount

Recommended simple status model:

```text
pending_review    = vendor uploaded, waiting for admin/client approval
approved_payable  = accepted/delivered, payable to vendor
paid              = included in payout
reversed          = rejected/failed/cancelled
```

### 5. Payment collection methods

Payments can be received through:

- UPI
- Bank transfer
- Cash
- Razorpay

Phase 1 should support manual admin entry for all modes.

Razorpay automation can be Phase 2/3 after manual ledger is stable.

### 6. Old slots migration

Introduce a new credits system and migrate old slots into credits.

Old slots should not remain the long-term source of truth.

Migration principle:

- Create credits ledger.
- Read existing client slot balance.
- Create opening credit transaction for each client.
- After migration, use credits ledger and `credit_balance` fields as the source of truth.
- Old `slots` fields can be kept temporarily for backward compatibility, but UI should gradually rename slots to credits.

### 7. Cancellation/refund rule

If client uploads one file:

```text
1 credit deducted
```

If order is cancelled before final delivery:

```text
1 credit returns to client
```

This refund must create a credit ledger transaction. Never silently change the balance.

### 8. Vendor payout cycle

Vendor payouts can happen:

- Daily
- Weekly
- Monthly
- Whenever requested

The system should not force a single payout cycle. It should allow admin to pay any approved payable amount at any time, while preventing overpayment.

### 9. Profit calculation

Profit should be calculated only for completed/delivered files.

Example:

```text
Client paid ₹5000 for 100 credits.
Only 20 files completed.
Profit should be calculated only for 20 completed files.
```

Show both values separately:

- Cash received
- Earned profit

Do not treat all received cash as profit because unused client credits are a liability/work obligation.

### 10. Expense tracking

The finance module should also support business expenses:

- Staff salary
- Software cost
- Razorpay charges
- Internet/domain/hosting
- Other manual expenses

Expense tracking can be implemented after core credits/vendor payout flow, but the database and dashboard plan should include it.

### 11. Existing data

Existing data migration is not critical according to the project owner. A clean reset/migration plan is acceptable.

However, because the app is deployed on Railway, implementation must still be safe:

- Never run destructive commands automatically during deploy.
- Provide an explicit Artisan command for resetting finance/order data only if needed.
- Require a confirmation flag such as `--confirm=yes`.
- Document backup steps before any production reset.

### 12. First dashboard requirements

Admin finance dashboard must show:

- Total money received
- Credits added
- Credits used
- Credits remaining
- Files uploaded
- Files completed
- Vendor payable
- Vendor paid
- Gross profit
- Cash balance
- Client-wise balance
- Vendor-wise balance

---

## Core design principle

Do not build finance logic using only increment/decrement columns.

Use a ledger approach:

```text
Every money or credit movement creates a transaction row.
```

Balances may be cached on client/vendor/user rows for speed, but the ledger remains the audit trail.

---

## Key definitions

### Credit

A non-money unit representing permission to check one file.

```text
1 credit = 1 file check
```

### Client payment

Money received from a client by admin/business.

### Client credit transaction

A ledger row explaining why credits increased or decreased.

Examples:

- Opening migration
- Payment credit
- Order debit
- Cancellation refund
- Manual adjustment
- Correction

### Vendor earning transaction

A ledger row explaining vendor earnings, pending payable, approved payable, payouts, and reversals.

### Financial snapshot

Order-level stored amounts that never change when future rates change.

Examples:

- Client rate per file
- Client amount
- Vendor rate per file
- Vendor amount
- Gross profit

---

## Recommended database changes

### A. Add/modify client financial fields

Identify the current client representation. It may be a `clients` table or `users` table with `role = client`.

Add fields to the appropriate client/user profile table:

```php
$table->unsignedInteger('credit_balance')->default(0);
$table->decimal('price_per_file', 10, 2)->default(0); // if not already present
$table->timestamp('credits_migrated_at')->nullable();
```

If `price_per_file` already exists, do not duplicate it. Use the existing field.

### B. Add/modify vendor financial fields

Identify the current vendor representation. It may be a `users` table with `role = vendor`.

Add fields if missing:

```php
$table->decimal('payout_rate_per_file', 10, 2)->default(0);
$table->decimal('approved_payable_balance', 12, 2)->default(0);
$table->decimal('pending_earning_balance', 12, 2)->default(0);
```

If payout rate already exists, reuse it.

### C. Create `client_payments`

Stores actual money received.

Suggested columns:

```php
Schema::create('client_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
    $table->decimal('amount_received', 12, 2);
    $table->unsignedInteger('credits_added');
    $table->decimal('rate_per_credit', 10, 2);
    $table->string('payment_mode'); // upi, bank_transfer, cash, razorpay
    $table->string('transaction_id')->nullable();
    $table->timestamp('received_at');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->string('status')->default('confirmed'); // confirmed, voided, refunded
    $table->timestamps();

    $table->index(['client_id', 'received_at']);
    $table->index(['payment_mode', 'received_at']);
});
```

### D. Create `client_credit_transactions`

Tracks credits, not rupees only.

Suggested columns:

```php
Schema::create('client_credit_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
    $table->foreignId('client_payment_id')->nullable()->constrained('client_payments')->nullOnDelete();
    $table->string('type'); // opening_balance, payment_credit, order_debit, refund_credit, manual_adjustment, correction
    $table->integer('credits_delta'); // + or -
    $table->unsignedInteger('balance_after');
    $table->decimal('rate_per_credit', 10, 2)->nullable();
    $table->decimal('money_value', 12, 2)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['client_id', 'created_at']);
    $table->index(['order_id']);
    $table->index(['type']);
});
```

### E. Add financial snapshot fields to `orders`

Add columns if missing:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->unsignedInteger('credits_consumed')->default(1);
    $table->decimal('client_rate_per_file', 10, 2)->nullable();
    $table->decimal('client_amount', 12, 2)->nullable();
    $table->decimal('vendor_rate_per_file', 10, 2)->nullable();
    $table->decimal('vendor_amount', 12, 2)->nullable();
    $table->decimal('gross_profit', 12, 2)->nullable();
    $table->timestamp('financial_locked_at')->nullable();
    $table->timestamp('vendor_submitted_at')->nullable();
    $table->timestamp('vendor_approved_at')->nullable();
    $table->timestamp('vendor_rejected_at')->nullable();
});
```

If the project already uses `files_count`, use that as the basis for credits consumed. For now, because decision is `1 credit = 1 file`, one order with one file consumes one credit. Keep flexible support for `files_count` where existing code has it.

### F. Create `vendor_earning_transactions`

Tracks vendor earnings, pending amounts, approvals, payouts, and reversals.

Suggested columns:

```php
Schema::create('vendor_earning_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
    $table->foreignId('vendor_payout_id')->nullable()->constrained('vendor_payouts')->nullOnDelete();
    $table->string('type'); // pending_order_earning, approve_earning, payout, reversal, manual_adjustment, correction
    $table->string('status')->default('posted'); // posted, voided
    $table->decimal('amount_delta', 12, 2); // + or -
    $table->decimal('pending_balance_after', 12, 2)->nullable();
    $table->decimal('approved_balance_after', 12, 2)->nullable();
    $table->unsignedInteger('files_count')->default(1);
    $table->decimal('rate_per_file', 10, 2)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['vendor_id', 'created_at']);
    $table->index(['order_id']);
    $table->index(['vendor_payout_id']);
    $table->index(['type']);
});
```

### G. Improve `vendor_payouts`

If existing table already exists, add missing fields only.

Suggested required fields:

```php
$table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
$table->decimal('amount', 12, 2);
$table->string('payment_mode'); // upi, bank_transfer, cash
$table->string('transaction_id')->nullable();
$table->timestamp('paid_at');
$table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
$table->text('notes')->nullable();
$table->string('status')->default('paid'); // paid, voided
```

Rules:

- Validate selected user is a vendor.
- Prevent payout greater than approved payable balance.
- Creating a payout must create a vendor earning transaction with negative `amount_delta`.

### H. Create `business_expenses`

For salaries, software, Razorpay charges, hosting, etc.

```php
Schema::create('business_expenses', function (Blueprint $table) {
    $table->id();
    $table->string('category'); // staff_salary, software, razorpay_charges, hosting, internet_domain, other
    $table->decimal('amount', 12, 2);
    $table->string('payment_mode')->nullable(); // upi, bank_transfer, cash, card, auto_deducted
    $table->string('reference_id')->nullable();
    $table->date('expense_date');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['category', 'expense_date']);
});
```

---

## Optional double-entry accounting layer

A full double-entry ledger is ideal, but may be too heavy for immediate implementation.

Recommended approach:

### Phase 1

Build practical ledgers:

- `client_payments`
- `client_credit_transactions`
- `vendor_earning_transactions`
- `vendor_payouts`
- `business_expenses`
- order financial snapshots

### Phase 2 or 3

Optionally add:

- `finance_accounts`
- `journal_entries`
- `journal_lines`

Use those if the owner wants formal accounting reports later.

Do not block the current business improvement waiting for a full accounting engine.

---

## Service architecture

Create dedicated services. Do not put finance logic directly in controllers.

Recommended services:

```text
app/Services/Finance/ClientCreditService.php
app/Services/Finance/ClientPaymentService.php
app/Services/Finance/VendorEarningService.php
app/Services/Finance/VendorPayoutService.php
app/Services/Finance/FinanceDashboardService.php
app/Services/Finance/BusinessExpenseService.php
app/Services/Finance/OpeningBalanceMigrationService.php
```

### ClientCreditService responsibilities

- Get client credit balance
- Add credits
- Debit credits on order creation
- Refund credits on cancellation
- Create credit ledger transactions
- Lock client row during balance changes
- Prevent negative balance

### ClientPaymentService responsibilities

- Admin records money received
- Create client payment
- Add credits via ClientCreditService
- Validate payment mode
- Store transaction ID/reference

### VendorEarningService responsibilities

- Create pending vendor earning when report is uploaded
- Approve pending earning after admin delivery/approval
- Reverse pending/approved earning on failure/rejection/cancellation
- Update vendor pending and approved balances
- Store vendor rate snapshot

### VendorPayoutService responsibilities

- Show vendor payable balances
- Validate vendor role
- Prevent overpayment
- Create payout record
- Create negative vendor earning transaction
- Update approved payable balance

### FinanceDashboardService responsibilities

- Calculate dashboard metrics
- Use ledger tables and order snapshots, not fragile manual calculations
- Return date-filtered summaries

### BusinessExpenseService responsibilities

- Create/edit/list business expenses
- Include expenses in cash and net profit reports

---

## Required workflow changes

### Workflow 1: Admin adds client payment and credits

Admin form fields:

```text
Client
Amount received
Credits added
Rate per credit/file
Payment mode: UPI / Bank transfer / Cash / Razorpay
Transaction ID/reference
Received date
Notes
```

Backend behavior:

1. Validate client exists and role is client.
2. Validate amount and credits are positive.
3. Use DB transaction.
4. Create `client_payments` row.
5. Lock client row.
6. Increase `credit_balance`.
7. Create `client_credit_transactions` row of type `payment_credit`.
8. Commit.

Acceptance:

- Client balance increases.
- Payment history shows amount received.
- Credit ledger shows why balance increased.
- Dashboard total money received increases.
- Dashboard credits added increases.

---

### Workflow 2: Migrate old slots into credits

Create an Artisan command:

```bash
php artisan finance:migrate-slots-to-credits --dry-run
php artisan finance:migrate-slots-to-credits --confirm=yes
```

Dry run should show:

- Number of clients found
- Existing slots per client
- Existing credit balance per client
- Whether already migrated

Actual run:

1. For every client not migrated:
2. Read current slot balance.
3. Set `credit_balance` to that value.
4. Create `client_credit_transactions` type `opening_balance`.
5. Set `credits_migrated_at`.

Acceptance:

- Command is idempotent.
- Running twice does not duplicate credits.
- Migration does not automatically delete old slots fields.

---

### Workflow 3: Client uploads file/order

Current order upload flow must change.

Required behavior:

1. Start DB transaction.
2. Lock client row.
3. Confirm client has at least required credits.
4. Required credits = number of files uploaded. For now, usually 1.
5. Create order.
6. Save `credits_consumed`.
7. Save `client_rate_per_file` from client's current price.
8. Save `client_amount = credits_consumed * client_rate_per_file`.
9. Debit credits using `ClientCreditService`.
10. Create `client_credit_transactions` type `order_debit` linked to order.
11. Commit.

Acceptance:

- Upload fails if client has insufficient credits.
- Credit is deducted exactly once.
- Order stores client rate snapshot.
- Changing client rate later does not change old order amount.

---

### Workflow 4: Cancel/refund order

If order is cancelled before final delivery:

1. Start DB transaction.
2. Lock order.
3. Check if credits already refunded.
4. Refund `order.credits_consumed` credits.
5. Create `client_credit_transactions` type `refund_credit` linked to order.
6. Mark order cancellation/refund timestamp.
7. If vendor pending earning exists, reverse it.
8. If approved vendor earning exists and should be reversed, create reversal.
9. Commit.

Acceptance:

- Client gets credit back.
- Double refund is impossible.
- Vendor pending/approved earning is handled based on status.
- Ledger history remains visible.

---

### Workflow 5: Vendor uploads completed report

When vendor uploads completed report:

1. Start DB transaction.
2. Lock order.
3. Validate vendor is assigned/allowed.
4. Save report file.
5. Set order status to something like `submitted_for_review` or existing equivalent.
6. Save `vendor_submitted_at`.
7. Save `vendor_rate_per_file` from current vendor payout rate if not already saved.
8. Save `vendor_amount = files_count * vendor_rate_per_file`.
9. Create vendor earning transaction:

```text
type = pending_order_earning
amount_delta = +vendor_amount
```

10. Increase vendor `pending_earning_balance`.
11. Commit.

Acceptance:

- Vendor upload creates pending earning, not final paid earning.
- Vendor dashboard can show pending review amount.
- Admin dashboard can show pending vendor review amount separately.

---

### Workflow 6: Admin approves/delivers vendor report

When admin accepts the vendor report and order becomes delivered/completed:

1. Start DB transaction.
2. Lock order.
3. Check pending vendor earning exists.
4. Move amount from pending to approved payable.
5. Create vendor earning transaction:

```text
type = approve_earning
amount_delta = +vendor_amount
```

Alternative: if preferred, update the original pending transaction status to approved and also create a clear approval transaction. The dashboard must remain auditable.

6. Decrease vendor `pending_earning_balance`.
7. Increase vendor `approved_payable_balance`.
8. Save order:

```text
vendor_approved_at
financial_locked_at
vendor_amount
gross_profit = client_amount - vendor_amount
```

9. Commit.

Acceptance:

- Approved payable increases.
- Pending review decreases.
- Gross profit is calculated only after delivery/approval.
- Revenue/profit dashboard includes only delivered/approved orders.

---

### Workflow 7: Admin rejects/fails vendor report

When admin rejects/fails report:

1. Start DB transaction.
2. Lock order.
3. Find pending earning.
4. Reverse it:

```text
type = reversal
amount_delta = -vendor_amount
```

5. Decrease vendor `pending_earning_balance`.
6. Save `vendor_rejected_at`.
7. Set order status appropriately, for example:

```text
needs_revision
rejected
failed
```

8. If vendor is allowed to re-upload, allow a new pending earning only after re-upload.
9. Commit.

Important:

Avoid duplicate pending earnings for repeated uploads. Either reverse the previous pending earning before creating a new one, or update the existing pending entry.

Acceptance:

- Vendor rejected work does not become payable.
- Pending earning is reduced/reversed.
- Ledger shows why the amount was removed.

---

### Workflow 8: Vendor payout

Admin selects vendor and enters amount paid.

Form fields:

```text
Vendor
Amount paid
Payment mode: UPI / Bank transfer / Cash
Transaction ID/reference
Paid date
Notes
```

Backend behavior:

1. Validate selected user is vendor.
2. Validate amount > 0.
3. Lock vendor row.
4. Check `amount <= approved_payable_balance`.
5. Create `vendor_payouts` row.
6. Create vendor earning transaction:

```text
type = payout
amount_delta = -amount
```

7. Reduce `approved_payable_balance`.
8. Commit.

Acceptance:

- Overpayment is impossible.
- Vendor balance reduces.
- Vendor payout history shows amount and transaction ID.
- Dashboard vendor paid increases.

---

### Workflow 9: Business expenses

Admin can add expense records.

Form fields:

```text
Category
Amount
Payment mode
Reference ID
Expense date
Notes
```

Acceptance:

- Expenses appear in finance dashboard.
- Expenses reduce cash balance/net profit reports.

---

## Dashboard calculations

Create Admin → Finance Dashboard.

Support optional date filters:

```text
Today
This week
This month
Custom range
All time
```

### Dashboard metrics

#### Total money received

```text
sum(client_payments.amount_received where status = confirmed)
```

#### Credits added

```text
sum(client_credit_transactions.credits_delta where type in opening_balance/payment_credit/manual_adjustment positive)
```

For business revenue reporting, keep opening balances separate from real paid credits.

#### Credits used

```text
abs(sum(client_credit_transactions.credits_delta where type = order_debit))
```

#### Credits remaining

```text
sum(users.credit_balance where role = client)
```

#### Files uploaded

```text
count(orders) or sum(orders.files_count)
```

#### Files completed

```text
count/sum orders where delivered/completed/approved status
```

#### Revenue earned

```text
sum(orders.client_amount where order is delivered/financial_locked_at is not null)
```

#### Vendor payable

```text
sum(vendors.approved_payable_balance)
```

#### Vendor pending review

```text
sum(vendors.pending_earning_balance)
```

#### Vendor paid

```text
sum(vendor_payouts.amount where status = paid)
```

#### Gross profit

```text
sum(orders.gross_profit where order is delivered/financial_locked_at is not null)
```

#### Total expenses

```text
sum(business_expenses.amount)
```

#### Net profit

```text
gross_profit - total_expenses
```

#### Cash balance

Simple operational formula:

```text
total money received - vendor paid - business expenses - client cash refunds
```

If cash refunds are implemented later, include them in the formula.

#### Client unused credit liability

```text
sum(client.credit_balance * client.price_per_file)
```

This is an estimate of future work obligation, not actual cash.

---

## Required admin pages

### 1. Finance Dashboard

Route example:

```text
/admin/finance
```

Cards:

- Total money received
- Credits added
- Credits used
- Credits remaining
- Files uploaded
- Files completed
- Revenue earned
- Vendor pending review
- Vendor payable
- Vendor paid
- Gross profit
- Total expenses
- Net profit
- Cash balance

Tables:

- Client-wise balance
- Vendor-wise balance
- Recent payments
- Recent payouts
- Recent expenses

### 2. Client Payments

Route example:

```text
/admin/finance/client-payments
```

Features:

- Add payment
- List payments
- Filter by client, date, payment mode
- View linked credit transaction

### 3. Client Credit Ledger

Route example:

```text
/admin/finance/client-credits
```

Features:

- Client-wise ledger
- Balance after every transaction
- Linked order/payment
- Manual adjustment with note and admin password confirmation

### 4. Vendor Payables

Route example:

```text
/admin/finance/vendor-payables
```

Features:

- Vendor name
- Pending review amount
- Approved payable amount
- Paid amount
- Completed files
- Reversed amount
- Pay vendor button

### 5. Vendor Payout History

Route example:

```text
/admin/finance/vendor-payouts
```

Features:

- Add payout
- View payout history
- Filter by vendor/date/payment mode
- Link to vendor earning ledger

### 6. Order Profit Report

Route example:

```text
/admin/finance/order-profit-report
```

Columns:

- Order ID
- Client
- Vendor
- Status
- Files count
- Client rate
- Client amount
- Vendor rate
- Vendor amount
- Gross profit
- Financial locked date

### 7. Business Expenses

Route example:

```text
/admin/finance/expenses
```

Features:

- Add expense
- List expenses
- Filter by category/date
- Include in dashboard

---

## Security and production safety rules

Because this project is deployed on Railway:

1. Do not run destructive resets automatically.
2. All finance writes must use database transactions.
3. Balance-changing operations must lock the relevant client/vendor/order row.
4. Manual adjustments require notes.
5. Destructive corrections should require admin password confirmation if available.
6. Do not expose raw IDs unnecessarily in views.
7. Validate user roles before financial actions.
8. Vendor payout must only be allowed for users with vendor role.
9. Client payments must only be allowed for users with client role.
10. All finance actions should record `created_by` / `paid_by` admin user.
11. Never silently edit old ledger rows. Prefer reversal/correction rows.
12. Keep ledger history immutable as much as possible.

---

## Reset/clean-start plan

The project owner said existing data is not important and a clear plan can be made.

However, implement reset safely as an explicit command only.

Suggested command:

```bash
php artisan finance:reset --dry-run
php artisan finance:reset --confirm=yes
```

The command should optionally clear:

- client_payments
- client_credit_transactions
- vendor_earning_transactions
- vendor_payouts
- business_expenses
- order financial snapshot columns
- client credit balances
- vendor pending/approved balances

Do not delete users by default.
Do not delete uploaded files by default.
Do not delete orders by default unless explicitly requested with another flag.

Suggested flags:

```bash
php artisan finance:reset --confirm=yes --include-orders=no --include-files=no
```

---

## Suggested implementation phases

## Phase 0 — Audit current code and prepare branch

Goal: Understand current file paths and avoid breaking production.

Tasks:

1. Create new branch:

```bash
git checkout -b finance-ledger-system
```

2. Inspect current files:

```text
app/Http/Controllers/TopupRequestController.php
app/Http/Controllers/BillingController.php
app/Http/Controllers/OrderController.php
app/Http/Controllers/VendorEarningsController.php
app/Http/Controllers/VendorPayoutController.php
app/Http/Controllers/RefundController.php
app/Models/User.php
app/Models/Order.php
app/Models/TopupRequest.php
app/Models/VendorPayout.php
routes/web.php
database/migrations/
resources/views/
```

3. Identify exact current columns for:

- slots
- price per file
- vendor payout rate
- order status
- files count
- top-up requests
- payouts

Deliverable:

- Short implementation note in `docs/finance-ledger-implementation-notes.md` describing existing column names before coding.

Acceptance:

- No behavior changed.
- Existing tests still pass.

---

## Phase 1 — Database migrations and models

Goal: Add new finance tables and columns without changing main behavior yet.

Tasks:

1. Create migrations for:

- client_payments
- client_credit_transactions
- vendor_earning_transactions
- business_expenses
- order financial snapshot columns
- client/vendor balance columns
- vendor_payout improvements if needed

2. Create models:

```text
app/Models/ClientPayment.php
app/Models/ClientCreditTransaction.php
app/Models/VendorEarningTransaction.php
app/Models/BusinessExpense.php
```

3. Add relationships in User/Order/VendorPayout models.

4. Add constants/enums for transaction types if project style supports it.

Acceptance:

- `php artisan migrate` runs cleanly.
- No existing route breaks.
- Models can create/read records.

---

## Phase 2 — Credit migration from old slots

Goal: Move old slot balances into new credit balances/ledger.

Tasks:

1. Create command:

```text
app/Console/Commands/MigrateSlotsToCreditsCommand.php
```

Command:

```bash
php artisan finance:migrate-slots-to-credits --dry-run
php artisan finance:migrate-slots-to-credits --confirm=yes
```

2. Use idempotency: skip clients with `credits_migrated_at` already set.
3. Create opening balance transaction.
4. Update client `credit_balance`.

Acceptance:

- Dry run shows changes without writing.
- Confirm mode writes once.
- Running confirm twice does not duplicate credits.

---

## Phase 3 — Admin client payment and credit top-up

Goal: Admin can record received money and add credits in one auditable action.

Tasks:

1. Create services:

```text
ClientPaymentService
ClientCreditService
```

2. Create admin controller or extend existing top-up/billing controller.
3. Add routes under admin finance group.
4. Add Blade form for adding payment.
5. Make old top-up approval create proper payment + credit ledger entries.

Acceptance:

- Admin can add UPI/bank/cash/Razorpay payment manually.
- Client credit balance increases.
- Client payment row is created.
- Client credit transaction row is created.
- Dashboard can read total received and credits added.

---

## Phase 4 — Order upload uses credits

Goal: Client upload consumes credits from the new system.

Tasks:

1. Modify order creation flow.
2. Use DB transaction and lock client row.
3. Check credit balance.
4. Deduct credit.
5. Store order financial snapshot:

```text
credits_consumed
client_rate_per_file
client_amount
```

6. Create credit transaction type `order_debit`.
7. Stop using old slots as the source of truth.

Acceptance:

- Client cannot upload without enough credits.
- Upload consumes exactly one credit per file.
- Old slots no longer control upload permission after this phase.
- Old UI labels should say Credits, not Slots, where practical.

---

## Phase 5 — Cancellation/refund credits

Goal: Cancelled orders return credits safely.

Tasks:

1. Update cancellation/refund flow.
2. Add `credits_refunded_at` to orders if useful.
3. Prevent duplicate credit refund.
4. Create credit transaction type `refund_credit`.
5. Reverse vendor pending/approved earning if needed.

Acceptance:

- Cancelled order returns credits.
- Double cancellation does not double refund.
- Ledger explains the refund.

---

## Phase 6 — Vendor pending earning on report upload

Goal: Vendor upload creates pending earning, not final payout.

Tasks:

1. Create service:

```text
VendorEarningService
```

2. Modify vendor report upload flow.
3. On upload:

```text
Create pending_order_earning
Increase pending_earning_balance
Save vendor rate snapshot
```

4. Prevent duplicate pending earning on repeated upload unless previous one is reversed.

Acceptance:

- Vendor dashboard shows pending review earning.
- Admin dashboard shows pending review amount.
- No approved payable is created until approval.

---

## Phase 7 — Admin approval/rejection of vendor work

Goal: Admin can approve or reject report and vendor finance updates correctly.

Tasks:

1. Add/modify admin actions:

```text
Approve delivered report
Reject/fail report
Request revision
```

2. On approval:

```text
Move pending earning to approved payable
Set financial_locked_at
Calculate gross_profit
```

3. On rejection/failure:

```text
Reverse pending earning
Set vendor_rejected_at
Allow revision/reupload if needed
```

Acceptance:

- Approved reports increase vendor payable.
- Rejected reports reduce/reverse pending earning.
- Gross profit appears only for approved/delivered files.

---

## Phase 8 — Vendor payout module

Goal: Admin can pay vendors and system tracks pending vs paid.

Tasks:

1. Create/modify service:

```text
VendorPayoutService
```

2. Create vendor payable page.
3. Add payout form.
4. Prevent overpayment.
5. Create vendor payout row.
6. Create vendor earning transaction type `payout`.
7. Reduce approved payable balance.

Acceptance:

- Admin can pay any approved amount.
- Daily/weekly/monthly/requested payouts are all supported.
- Overpayment is impossible.
- Vendor paid and payable dashboard values are correct.

---

## Phase 9 — Business expenses

Goal: Track non-vendor expenses.

Tasks:

1. Create BusinessExpenseService.
2. Create admin expense page.
3. Categories:

```text
staff_salary
software
razorpay_charges
hosting
internet_domain
other
```

4. Include expenses in dashboard.

Acceptance:

- Admin can add expenses.
- Expenses reduce net profit/cash balance.

---

## Phase 10 — Finance dashboard and reports

Goal: Admin gets clear visibility.

Tasks:

1. Create FinanceDashboardService.
2. Create Admin Finance Dashboard.
3. Add client-wise and vendor-wise tables.
4. Add date filters.
5. Add order profit report.

Acceptance:

Dashboard shows:

- Total money received
- Credits added
- Credits used
- Credits remaining
- Files uploaded
- Files completed
- Revenue earned
- Vendor pending review
- Vendor payable
- Vendor paid
- Gross profit
- Total expenses
- Net profit
- Cash balance
- Client-wise balance
- Vendor-wise balance

---

## Phase 11 — Tests and verification

Goal: Prevent future finance mismatch.

Add tests for:

1. Admin payment adds credits and ledger row.
2. Client upload deducts one credit.
3. Client cannot upload without credits.
4. Cancellation refunds credit once only.
5. Vendor upload creates pending earning.
6. Vendor rejection reverses pending earning.
7. Vendor approval creates approved payable and gross profit.
8. Vendor payout reduces approved payable.
9. Overpayment is rejected.
10. Changing client/vendor rate later does not change old order financial snapshots.
11. Dashboard totals match transaction records.

Run:

```bash
php artisan test
npm run build
```

---

## Phase 12 — Production rollout on Railway

Goal: Deploy safely.

Steps:

1. Backup production database.
2. Deploy code with migrations.
3. Run migrations.
4. Run dry-run migration:

```bash
php artisan finance:migrate-slots-to-credits --dry-run
```

5. If output is correct, run:

```bash
php artisan finance:migrate-slots-to-credits --confirm=yes
```

6. Test:

- Admin login
- Add client payment
- Client upload
- Vendor upload
- Admin approve
- Vendor payout
- Finance dashboard

7. Monitor logs.

Rollback plan:

- Keep old slot columns during early rollout.
- Do not delete old data in first deployment.
- If dashboard issue occurs, disable finance menu but keep old core workflow running.

---

## Important edge cases

### Duplicate order debit

Prevent two credit debits for one order.

Approach:

- Use transaction.
- Check if existing `client_credit_transactions` with `order_id` and `type = order_debit` exists.

### Duplicate refund

Prevent two refunds for the same order.

Approach:

- Add `credits_refunded_at` on order.
- Check existing refund transaction.

### Duplicate vendor pending earning

Prevent multiple pending earnings on repeated report uploads unless previous is reversed.

Approach:

- Check existing pending earning for order/vendor.
- Reverse old pending earning before new upload, or block reupload until admin action.

### Vendor rejection after approval

If already approved and then rejected later, create reversal from approved payable.

### Vendor payout after reversal

If approved payable already paid, do not silently reverse below zero. Mark issue and require admin correction/manual adjustment.

### Rate changes

Old orders must not change.

Rates must be snapshotted on order.

### Client payment edits

Avoid editing confirmed payments. Prefer void/reversal and new corrected entry.

### Expenses

Expenses should not affect client credit or vendor earning ledgers.

---

## UI wording change

Replace user-facing word `slots` with `credits` where appropriate.

Suggested wording:

```text
Credits
Credit Balance
Credits Used
Credits Remaining
Add Client Payment & Credits
Credit Ledger
```

Do not change database column names aggressively in early phases if it risks breaking existing code. Rename UI first, then refactor DB later if needed.

---

## Coding standards for Codex

1. Use Laravel services for finance logic.
2. Use Form Request classes for validation when forms are complex.
3. Use database transactions for all financial writes.
4. Use `lockForUpdate()` for client/vendor/order balance-changing actions.
5. Add model relationships cleanly.
6. Keep controllers thin.
7. Do not remove existing routes until replacement is verified.
8. Do not hardcode roles if the project has role constants/helpers.
9. Match existing Blade design style.
10. Add tests for every money/credit movement.
11. Avoid destructive migrations.
12. Do not commit secrets or `.env` files.

---

## One-shot Codex prompt

Use this prompt after placing this MD file in the repository, for example as:

```text
docs/finance-ledger-plan.md
```

Prompt:

```text
You are working on an existing Laravel 12 Railway-deployed PlagExpert Portal project with Admin, Client, Vendor roles, Telegram login, client file uploads, vendor report uploads, top-up/slots, and vendor payouts.

Read docs/finance-ledger-plan.md completely first. Implement the finance ledger system exactly in phases. Do not rewrite the whole project. Do not remove old slot logic until the new credit ledger is working. Use non-destructive migrations, service classes, DB transactions, lockForUpdate for balance-changing operations, and tests.

Business decisions:
- 1 credit = 1 file for now.
- Each client has a different price per file.
- Each vendor has a different payout rate per file.
- Vendor report upload creates pending earning.
- Admin approval converts pending earning to approved payable and calculates gross profit.
- Rejection/failure reverses pending earning.
- Client cancellation before delivery refunds the consumed credit.
- Existing slots must be migrated into credits with an idempotent artisan command.
- Payments can be UPI, bank transfer, cash, or Razorpay.
- Vendor payouts can be daily, weekly, monthly, or whenever requested.
- Profit is calculated only for completed/delivered/approved files.
- Business expenses should include staff salary, software, Razorpay charges, internet/domain/hosting, and other expenses.

Start with Phase 0 and Phase 1 only unless asked to continue. Before editing, inspect the current schema/controllers/models and write notes to docs/finance-ledger-implementation-notes.md. Then add migrations/models/services needed for the first phase. Keep all existing behavior working.

After each phase, provide:
1. Files changed
2. What was implemented
3. How to test locally
4. Any migration commands
5. Risk notes for Railway deployment
```

---

## Expected final achievement

After all phases, the project should become a proper business management portal where admin can answer:

```text
How much money did we receive?
How many credits did we create?
How many credits were used?
How many client credits remain?
How many files were uploaded?
How many files were completed?
How much should we pay vendors?
How much did we already pay vendors?
How much profit did we earn from completed work?
How much cash is currently left after vendor payouts and expenses?
Which client has how much balance?
Which vendor has how much payable?
```

This system should reduce manual calculation and prevent finance mismatch.
