# Finance Ledger — Production Rollout Checklist

Last updated: 2026-06-18 (Phase 10C)

---

## Pre-deployment (local)

- [ ] Backup the MySQL database on Railway before running migrations
- [ ] Verify the app is on the correct branch with all finance phases merged
- [ ] Confirm no active admin sessions are editing client/vendor records

```bash
php artisan test --filter=Finance
php artisan migrate --pretend
```

Both must pass before deploying.

## On Railway after deploy

```bash
php artisan migrate
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

All migrations are additive (no column drops, no data loss).

### Migrations included (Phases 1–10B)

| Migration | Purpose |
|-----------|---------|
| `add_finance_fields_to_clients_table` | `credit_balance`, `credits_migrated_at` |
| `add_finance_fields_to_users_table` | `pending_earning_balance`, `approved_payable_balance` |
| `create_client_payments_table` | Client payment records |
| `create_client_credit_transactions_table` | Credit ledger |
| `add_financial_snapshot_to_orders_table` | Order-level rate/amount snapshots |
| `create_vendor_earning_transactions_table` | Vendor earning ledger |
| `add_finance_fields_to_vendor_payouts_table` | `payment_mode`, `paid_by`, `status` |
| `create_business_expenses_table` | Business expense tracking |
| `add_void_columns_to_client_payments_table` | Void metadata |
| `add_void_columns_to_vendor_payouts_table` | Void metadata |
| `add_void_columns_and_status_to_business_expenses_table` | Status + void metadata |

## Post-migration verification

### 1. Check migration status

```bash
php artisan migrate:status
```

All migrations should show "Ran".

### 2. Verify new columns exist

```sql
-- Clients
SELECT credit_balance FROM clients LIMIT 1;

-- Users (vendors)
SELECT pending_earning_balance, approved_payable_balance FROM users WHERE role = 'vendor' LIMIT 1;

-- Client payments
SELECT id, client_id, amount_received, credits_added, status FROM client_payments LIMIT 1;

-- Orders financial snapshot
SELECT credits_consumed, client_amount, vendor_amount, gross_profit, financial_locked_at FROM orders LIMIT 1;

-- Business expenses
SELECT id, category, amount, status FROM business_expenses LIMIT 1;

-- Void columns
SELECT voided_at, voided_by, void_reason FROM client_payments LIMIT 0;
SELECT voided_at, voided_by, void_reason FROM vendor_payouts LIMIT 0;
```

### 3. Verify all finance routes load

```bash
php artisan route:list --name=admin.finance
```

Should show 33 routes.

### 4. Verify admin can access finance pages

- [ ] `/admin/finance/dashboard` — loads with zero values
- [ ] `/admin/finance/client-payments` — loads empty table
- [ ] `/admin/finance/expenses` — loads empty table
- [ ] `/admin/finance/payouts` — loads empty table
- [ ] `/admin/finance/reports` — loads index with 7 report cards
- [ ] `/admin/finance/vendor-earnings` — loads empty queue

## Cache clearing

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

Important: The admin sidebar uses cached badge counts with 60s TTL. After deployment, the old cached values (including legacy `slots_consumed >= slots` counts) will naturally expire. `cache:clear` forces immediate refresh.

## Initial data setup

### Option A: Fresh start (recommended)

If no finance data has been recorded yet, all balances start at 0. Begin using the system:

1. Record client payments via `/admin/finance/client-payments`
2. Credits are automatically added when payments are recorded
3. Order uploads debit credits automatically
4. Vendor earnings are created on order delivery
5. Approve vendor earnings via `/admin/finance/vendor-earnings`
6. Record vendor payouts via `/admin/finance/payouts`

### Option B: Clean slate reset

If you need to clear test/dummy finance data:

```bash
php artisan finance:reset-clean-slate --confirm
```

This preserves users, clients, and orders but clears all finance ledger entries and resets balances to 0.

## Key admin workflows

| Action | Route |
|--------|-------|
| Record client payment | `/admin/finance/client-payments` (click "Record Payment") |
| View credit ledger | `/admin/finance/client-credit-transactions` |
| Approve vendor earnings | `/admin/finance/vendor-earnings` |
| Record vendor payout | `/admin/finance/payouts` |
| Record business expense | `/admin/finance/expenses` |
| View dashboard | `/admin/finance/dashboard` |
| Generate reports/CSV | `/admin/finance/reports` |
| Void a payment | `/admin/finance/client-payments/{id}` (click "Void") |

## Monitoring

### Structured log events

All finance operations emit structured log entries:

| Event | Meaning |
|-------|---------|
| `client.payment_recorded` | New payment created + credits added |
| `client.credits_debited` | Order upload consumed credits |
| `client.credits_refunded` | Order cancellation/refund restored credits |
| `vendor.earning_created` | Pending earning from order delivery |
| `vendor.earning_approved` | Admin approved vendor earning |
| `vendor.payout_recorded` | Vendor payout disbursed |
| `business.expense_recorded` | Business expense logged |
| `finance.void.*` | Void/reversal operations |

### Balance integrity checks

After the system has been running, verify balance consistency:

```sql
-- Client credit_balance should equal sum of all credit transactions
SELECT c.id, c.name, c.credit_balance,
       COALESCE(SUM(t.credits_delta), 0) as computed_balance
FROM clients c
LEFT JOIN client_credit_transactions t ON t.client_id = c.id
GROUP BY c.id
HAVING c.credit_balance != computed_balance;

-- Vendor approved_payable_balance should equal sum of earning transactions
SELECT u.id, u.name, u.approved_payable_balance,
       COALESCE(SUM(CASE WHEN t.type IN ('approve_earning', 'payout', 'payout_reversal') THEN t.amount_delta ELSE 0 END), 0) as computed
FROM users u
LEFT JOIN vendor_earning_transactions t ON t.vendor_id = u.id
WHERE u.role = 'vendor'
GROUP BY u.id
HAVING u.approved_payable_balance != computed;
```

Both queries should return zero rows if balances are consistent.

## Rollback plan

All migrations are additive. To rollback:

1. The old `slots`/`slots_consumed` columns are still present and frozen at their last values.
2. Reverting code to pre-finance branch restores old slot-based behavior.
3. New finance tables can be dropped if needed, but this loses all recorded finance data.
4. **Recommendation**: Do not rollback once real finance data has been recorded. Fix forward instead.

## Legacy notes

- `clients.slots` and `clients.slots_consumed` — frozen, not used for credit decisions
- Topup nav link hidden from admin sidebar (Phase 10C)
- Client self-service topup disabled (returns error)
- Old admin topup approval page still accessible at `/admin/topup` for legacy requests
- All credit decisions now use `clients.credit_balance` exclusively
