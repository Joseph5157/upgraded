<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Models\VendorPayout;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FinanceReportService — Phase 10A
 *
 * Each query method returns a Builder; callers decide whether to paginate
 * (HTML views) or chunkById/cursor (CSV exports).
 *
 * Filter array keys accepted by all methods:
 *   from        Carbon|null  — start of period (already startOfDay)
 *   to          Carbon|null  — end of period (already endOfDay)
 *
 * Additional per-report keys:
 *   client_id   int|null
 *   vendor_id   int|null
 *   payment_mode string|null
 *   status      string|null
 *   type        string|null
 *   category    string|null
 */
class FinanceReportService
{
    // ─────────────────────────────────────────────────────────────────────
    // 1. Client Payments
    // ─────────────────────────────────────────────────────────────────────

    public function clientPaymentsQuery(array $filters): Builder
    {
        return ClientPayment::with(['client', 'createdBy'])
            ->when($filters['from'] ?? null, fn ($q) => $q->where('received_at', '>=', $filters['from']))
            ->when($filters['to']   ?? null, fn ($q) => $q->where('received_at', '<=', $filters['to']))
            ->when($filters['client_id']    ?? null, fn ($q) => $q->where('client_id', $filters['client_id']))
            ->when($filters['payment_mode'] ?? null, fn ($q) => $q->where('payment_mode', $filters['payment_mode']))
            ->when($filters['status']       ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Client Credit Ledger
    // ─────────────────────────────────────────────────────────────────────

    public function clientCreditLedgerQuery(array $filters): Builder
    {
        return ClientCreditTransaction::with(['client', 'createdBy'])
            ->when($filters['from'] ?? null, fn ($q) => $q->where('created_at', '>=', $filters['from']))
            ->when($filters['to']   ?? null, fn ($q) => $q->where('created_at', '<=', $filters['to']))
            ->when($filters['client_id'] ?? null, fn ($q) => $q->where('client_id', $filters['client_id']))
            ->when($filters['type']      ?? null, fn ($q) => $q->where('type', $filters['type']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Vendor Earnings
    // ─────────────────────────────────────────────────────────────────────

    public function vendorEarningsQuery(array $filters): Builder
    {
        return VendorEarningTransaction::with(['vendor', 'createdBy'])
            ->when($filters['from']      ?? null, fn ($q) => $q->where('created_at', '>=', $filters['from']))
            ->when($filters['to']        ?? null, fn ($q) => $q->where('created_at', '<=', $filters['to']))
            ->when($filters['vendor_id'] ?? null, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['type']      ?? null, fn ($q) => $q->where('type', $filters['type']))
            ->when($filters['status']    ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Vendor Payouts
    // ─────────────────────────────────────────────────────────────────────

    public function vendorPayoutsQuery(array $filters): Builder
    {
        return VendorPayout::with(['vendor', 'paidBy'])
            ->when($filters['from']         ?? null, fn ($q) => $q->where('paid_at', '>=', $filters['from']))
            ->when($filters['to']           ?? null, fn ($q) => $q->where('paid_at', '<=', $filters['to']))
            ->when($filters['vendor_id']    ?? null, fn ($q) => $q->where('user_id', $filters['vendor_id']))
            ->when($filters['payment_mode'] ?? null, fn ($q) => $q->where('payment_mode', $filters['payment_mode']))
            ->when($filters['status']       ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('paid_at')
            ->orderByDesc('id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Business Expenses
    // ─────────────────────────────────────────────────────────────────────

    public function expensesQuery(array $filters): Builder
    {
        return BusinessExpense::with(['createdBy'])
            ->when($filters['from']         ?? null, fn ($q) => $q->where('expense_date', '>=', ($filters['from'] instanceof Carbon ? $filters['from']->toDateString() : $filters['from'])))
            ->when($filters['to']           ?? null, fn ($q) => $q->where('expense_date', '<=', ($filters['to'] instanceof Carbon ? $filters['to']->toDateString() : $filters['to'])))
            ->when($filters['category']     ?? null, fn ($q) => $q->where('category', $filters['category']))
            ->when($filters['payment_mode'] ?? null, fn ($q) => $q->where('payment_mode', $filters['payment_mode']))
            ->orderByDesc('expense_date')
            ->orderByDesc('id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 6. Order Profit
    // ─────────────────────────────────────────────────────────────────────

    public function orderProfitQuery(array $filters): Builder
    {
        return Order::with(['client', 'vendor'])
            ->whereNotNull('financial_locked_at')
            ->when($filters['from']      ?? null, fn ($q) => $q->where('vendor_approved_at', '>=', $filters['from']))
            ->when($filters['to']        ?? null, fn ($q) => $q->where('vendor_approved_at', '<=', $filters['to']))
            ->when($filters['client_id'] ?? null, fn ($q) => $q->where('client_id', $filters['client_id']))
            ->when($filters['vendor_id'] ?? null, fn ($q) => $q->where('claimed_by', $filters['vendor_id']))
            ->orderByDesc('vendor_approved_at')
            ->orderByDesc('id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 7. Monthly Summary  (aggregated — returns Collection, not Builder)
    // ─────────────────────────────────────────────────────────────────────

    public function monthlySummary(array $filters): Collection
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to']   ?? null;

        // SUBSTR(column, 1, 7) → 'YYYY-MM' works in both SQLite and MySQL
        $monthOf = fn (string $col) => DB::raw("SUBSTR({$col}, 1, 7)");

        $moneyByMonth = ClientPayment::where('status', ClientPayment::STATUS_CONFIRMED)
            ->when($from, fn ($q) => $q->where('received_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('received_at', '<=', $to))
            ->selectRaw("SUBSTR(received_at, 1, 7) as month, SUM(amount_received) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $creditsAddedByMonth = ClientCreditTransaction::where('type', ClientCreditTransaction::TYPE_PAYMENT_CREDIT)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('created_at', '<=', $to))
            ->selectRaw("SUBSTR(created_at, 1, 7) as month, SUM(credits_delta) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $creditsUsedByMonth = ClientCreditTransaction::where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('created_at', '<=', $to))
            ->selectRaw("SUBSTR(created_at, 1, 7) as month, SUM(credits_delta) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $filesUploadedByMonth = Order::where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('created_at', '<=', $to))
            ->selectRaw("SUBSTR(created_at, 1, 7) as month, SUM(credits_consumed) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $filesCompletedByMonth = Order::whereNotNull('vendor_approved_at')
            ->when($from, fn ($q) => $q->where('vendor_approved_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('vendor_approved_at', '<=', $to))
            ->selectRaw("SUBSTR(vendor_approved_at, 1, 7) as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $revenueByMonth = Order::whereNotNull('vendor_approved_at')
            ->when($from, fn ($q) => $q->where('vendor_approved_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('vendor_approved_at', '<=', $to))
            ->selectRaw("SUBSTR(vendor_approved_at, 1, 7) as month, SUM(client_amount) as revenue, SUM(vendor_amount) as cost")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $vendorPaidByMonth = VendorPayout::where('status', 'paid')
            ->when($from, fn ($q) => $q->where('paid_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('paid_at', '<=', $to))
            ->selectRaw("SUBSTR(paid_at, 1, 7) as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $expensesByMonth = BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED)
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from->toDateString()))
            ->when($to,   fn ($q) => $q->where('expense_date', '<=', $to->toDateString()))
            ->selectRaw("SUBSTR(expense_date, 1, 7) as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        // Collect all unique months across all sources
        $allMonths = collect()
            ->merge($moneyByMonth->keys())
            ->merge($creditsAddedByMonth->keys())
            ->merge($creditsUsedByMonth->keys())
            ->merge($filesUploadedByMonth->keys())
            ->merge($filesCompletedByMonth->keys())
            ->merge($revenueByMonth->keys())
            ->merge($vendorPaidByMonth->keys())
            ->merge($expensesByMonth->keys())
            ->unique()
            ->filter()
            ->sort()
            ->values();

        return $allMonths->map(function (string $month) use (
            $moneyByMonth, $creditsAddedByMonth, $creditsUsedByMonth,
            $filesUploadedByMonth, $filesCompletedByMonth, $revenueByMonth,
            $vendorPaidByMonth, $expensesByMonth
        ) {
            $received  = (float) ($moneyByMonth->get($month) ?? 0);
            $revenue   = (float) ($revenueByMonth->get($month)?->revenue ?? 0);
            $cost      = (float) ($revenueByMonth->get($month)?->cost ?? 0);
            $gross     = $revenue - $cost;
            $expenses  = (float) ($expensesByMonth->get($month) ?? 0);
            $paid      = (float) ($vendorPaidByMonth->get($month) ?? 0);

            return [
                'month'             => $month,
                'money_received'    => $received,
                'credits_added'     => (int) ($creditsAddedByMonth->get($month) ?? 0),
                'credits_used'      => abs((int) ($creditsUsedByMonth->get($month) ?? 0)),
                'files_uploaded'    => (int) ($filesUploadedByMonth->get($month) ?? 0),
                'files_completed'   => (int) ($filesCompletedByMonth->get($month) ?? 0),
                'revenue_earned'    => $revenue,
                'vendor_cost'       => $cost,
                'gross_profit'      => $gross,
                'vendor_paid'       => $paid,
                'business_expenses' => $expenses,
                'net_profit'        => $gross - $expenses,
                'cash_balance'      => $received - $paid - $expenses,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Build a CSV filename: "{base}-{from}-to-{to}-{today}.csv"
     */
    public function csvFilename(string $base, array $filters): string
    {
        $parts = [$base];

        if ($filters['from'] ?? null) {
            $parts[] = $filters['from']->format('Y-m-d');
        }
        if ($filters['to'] ?? null) {
            $parts[] = 'to';
            $parts[] = $filters['to']->format('Y-m-d');
        }
        if (! ($filters['from'] ?? null) && ! ($filters['to'] ?? null)) {
            $parts[] = 'all-time';
        }

        $parts[] = now()->format('Ymd');

        return implode('-', $parts) . '.csv';
    }
}
