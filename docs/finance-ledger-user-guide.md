# Finance Ledger — Admin User Guide

Last updated: 2026-06-18

---

## Overview

The Finance Ledger tracks all money flow through the portal:

- **Client payments** (money received from clients)
- **Client credits** (upload allowances linked to payments)
- **Vendor earnings** (per-file payments owed to vendors)
- **Vendor payouts** (money paid out to vendors)
- **Business expenses** (operational costs)

All financial records are **immutable** — they are never edited or deleted. Corrections are made through **void/reversal** entries that preserve the audit trail.

---

## 1. Recording a Client Payment

**Route:** Admin > Clients > Client Payments > Record Payment

**Steps:**
1. Select the client from the dropdown
2. Enter the amount received (in INR)
3. Enter the number of credits to add
4. Select payment mode (UPI, Bank Transfer, Cash)
5. Enter the transaction/reference ID (for non-cash payments)
6. Set the received date
7. Add optional notes
8. Click "Record Payment"

**What happens:**
- A `client_payments` record is created with status `confirmed`
- A `payment_credit` transaction is added to the client's credit ledger
- The client's `credit_balance` increases by the credits added
- If the client was suspended (zero balance), they are reactivated

**Rules:**
- Amount must be greater than zero
- Credits must be at least 1
- Duplicate transaction IDs are blocked (same payment_mode + reference_id)
- Cash payments can share reference IDs

---

## 2. Client Credit Flow

Credits are consumed automatically when a client (or guest link user) uploads a file:

```
Payment recorded  →  credits added to balance
Client uploads    →  1 credit deducted per file
Balance hits 0    →  client suspended (cannot upload)
Order cancelled   →  credits refunded (if debited via ledger)
```

**View the credit ledger:** Admin > Clients > Credit Ledger

Transaction types:
| Type | Meaning |
|------|---------|
| `payment_credit` | Credits added from a payment |
| `order_debit` | Credits consumed by a file upload |
| `refund_credit` | Credits restored from cancellation |
| `manual_adjustment` | Admin adjustment (positive or negative) |
| `correction` | Reversal from voided payment |

---

## 3. Vendor Earnings

When a vendor completes an order (uploads AI + plagiarism reports), a pending earning is created automatically.

**Approval queue:** Admin > Vendors > Earnings

**Steps:**
1. Review the pending earnings list
2. Click "Approve" to confirm the earning, or "Reject" to reverse it
3. Approved earnings move from `pending_earning_balance` to `approved_payable_balance`

**Earning calculation:**
```
vendor_amount = files_count x vendor_payout_rate (at time of delivery)
```

The rate is snapshotted at delivery time — changing the vendor's rate later does not affect already-created earnings.

---

## 4. Recording a Vendor Payout

**Route:** Admin > Vendors > Payouts

**Steps:**
1. Find the vendor in the table
2. Click "Pay" — the modal shows the approved payable balance
3. Enter the payout amount (cannot exceed approved balance)
4. Select payment mode and enter transaction ID
5. Click "Record Payout"

**What happens:**
- A `vendor_payouts` record is created with status `paid`
- A `payout` earning transaction decreases the vendor's `approved_payable_balance`
- The vendor's approved balance is reduced by the payout amount

**Rules:**
- Cannot pay more than `approved_payable_balance`
- Duplicate transaction IDs are blocked for non-cash modes
- Payouts never touch `pending_earning_balance`

---

## 5. Recording a Business Expense

**Route:** Admin > Vendors > Expenses

**Steps:**
1. Click "Add Expense"
2. Select category (Staff Salary, Software, Hosting, etc.)
3. Enter amount, payment mode, reference ID
4. Set the expense date
5. Add optional notes
6. Click "Record Expense"

**Categories available:**
Staff Salary, Software, Razorpay Charges, Hosting, Internet, Domain, Office, Refund Loss, Other

**Expenses do not affect any client or vendor balances.** They are used for profit/loss reporting only.

---

## 6. Voiding Records

If a payment, payout, or expense was recorded incorrectly, you can **void** it instead of deleting it.

### Voiding a Client Payment

**Route:** Admin > Client Payments > click payment > Void

- Enter a reason for voiding
- The system creates a `correction` credit transaction that reverses the credits
- The client's `credit_balance` is decreased
- The original payment is marked as `voided` with metadata

**Blocking rule:** If the client has already used the credits (balance < credits_added), the void is blocked. You must handle this case manually (e.g., add credits first, then void).

### Voiding a Vendor Payout

**Route:** Admin > Payouts > click payout > Void

- Enter a reason for voiding
- The system creates a `payout_reversal` earning transaction
- The vendor's `approved_payable_balance` is restored
- The original payout is marked as `voided`

### Voiding a Business Expense

**Route:** Admin > Expenses > click expense > Void

- Enter a reason for voiding
- The expense status changes to `voided`
- No balance changes (expenses don't affect balances)
- Voided expenses are excluded from dashboard totals and reports

**All void operations are idempotent** — voiding an already-voided record has no effect.

---

## 7. Finance Dashboard

**Route:** Admin > Overview > Finance

The dashboard shows all key metrics in real time:

| Section | Metrics |
|---------|---------|
| Cash & Profit | Total Received, Vendor Paid, Expenses, Cash Balance |
| Revenue | Revenue Earned, Vendor Cost, Gross Profit, Net Profit |
| Credits | Added, Used, Refunded, Remaining |
| Vendor Dues | Pending, Payable, Files Completed |

**Date range filter:** Use the from/to inputs to filter transaction-based metrics by period. Note: balance fields (Credits Remaining, Vendor Pending, Vendor Payable) always show current values regardless of the date range.

**Formulas:**
```
Gross Profit  = Revenue Earned - Vendor Cost
Net Profit    = Gross Profit - Business Expenses
Cash Balance  = Money Received - Vendor Paid - Business Expenses
```

---

## 8. Reports and CSV Export

**Route:** Admin > Overview > Reports

Seven report types are available, each with HTML view and CSV download:

| Report | What it shows |
|--------|--------------|
| Client Payments | All recorded payments with status/mode filters |
| Client Credit Ledger | All credit transactions with type filter |
| Vendor Earnings | All earning transactions with status filter |
| Vendor Payouts | All payouts with mode/status filters |
| Business Expenses | All expenses with category filter |
| Order Profit | Per-order revenue, cost, and profit |
| Monthly Summary | Aggregated monthly view with all metrics |

**CSV export:** Click the CSV button on any report. Files are streamed — suitable for large datasets. The filename includes the date range and export date.

**Voided records appear in report tables** (for audit purposes) but are excluded from header totals.

---

## 9. Key Rules

1. **Never edit or delete** financial records — use void/reversal instead
2. **Credits come from payments only** — the old top-up system is disabled
3. **Vendor payouts only reduce approved balance** — not pending balance
4. **Expenses are standalone** — they don't affect any user balances
5. **All balance mutations use database locks** — no race conditions
6. **Every operation is logged** — check Laravel logs for `client.*`, `vendor.*`, and `business.*` events
