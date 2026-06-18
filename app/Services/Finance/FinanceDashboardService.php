<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorPayout;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * FinanceDashboardService — Phase 9 implementation.
 *
 * All monetary fields are floats (₹).
 * All credit fields are ints.
 *
 * Date-range notes:
 *  - Transaction-based fields (money received, credits, vendor paid, expenses,
 *    revenue, vendor cost) are filtered by their natural date column when a
 *    range is supplied.
 *  - Current-balance fields (credits_remaining, vendor_pending, vendor_payable)
 *    always reflect the live balance regardless of date range — callers must
 *    communicate this to the user.
 */
class FinanceDashboardService
{
    /**
     * Return all dashboard metrics as an array.
     *
     * @param  Carbon|null  $from  Start of period (inclusive)
     * @param  Carbon|null  $to    End of period (inclusive)
     */
    public function metrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        // Normalise to start/end of day so date-only inputs work correctly.
        $f = $from?->copy()->startOfDay();
        $t = $to?->copy()->endOfDay();

        // ── Total money received ──────────────────────────────────────────────
        $totalMoneyReceived = (float) ClientPayment::where('status', ClientPayment::STATUS_CONFIRMED)
            ->when($f, fn ($q) => $q->where('received_at', '>=', $f))
            ->when($t, fn ($q) => $q->where('received_at', '<=', $t))
            ->sum('amount_received');

        // ── Credits ───────────────────────────────────────────────────────────
        $creditSum = fn (string $type) => (int) ClientCreditTransaction::where('type', $type)
            ->when($f, fn ($q) => $q->where('created_at', '>=', $f))
            ->when($t, fn ($q) => $q->where('created_at', '<=', $t))
            ->sum('credits_delta');

        $creditsAdded    = $creditSum(ClientCreditTransaction::TYPE_PAYMENT_CREDIT);
        $creditsUsed     = abs($creditSum(ClientCreditTransaction::TYPE_ORDER_DEBIT));
        $creditsRefunded = $creditSum(ClientCreditTransaction::TYPE_REFUND_CREDIT);

        // Current balance — never filtered by date range
        $creditsRemaining = (int) Client::sum('credit_balance');

        // ── Files / orders ────────────────────────────────────────────────────
        $filesUploaded = (int) Order::where('status', '!=', 'cancelled')
            ->when($f, fn ($q) => $q->where('created_at', '>=', $f))
            ->when($t, fn ($q) => $q->where('created_at', '<=', $t))
            ->sum('credits_consumed');

        $filesCompleted = Order::whereNotNull('vendor_approved_at')
            ->when($f, fn ($q) => $q->where('vendor_approved_at', '>=', $f))
            ->when($t, fn ($q) => $q->where('vendor_approved_at', '<=', $t))
            ->count();

        // ── Revenue and vendor cost (approved orders only) ────────────────────
        $approvedQ = fn () => Order::whereNotNull('vendor_approved_at')
            ->when($f, fn ($q) => $q->where('vendor_approved_at', '>=', $f))
            ->when($t, fn ($q) => $q->where('vendor_approved_at', '<=', $t));

        $revenueEarned = (float) $approvedQ()->sum('client_amount');
        $vendorCost    = (float) $approvedQ()->sum('vendor_amount');
        $grossProfit   = $revenueEarned - $vendorCost;

        // ── Vendor balances ───────────────────────────────────────────────────
        // pending / payable are live balances — never date-filtered
        $vendorPending  = (float) User::where('role', 'vendor')->sum('pending_earning_balance');
        $vendorPayable  = (float) User::where('role', 'vendor')->sum('approved_payable_balance');

        $vendorPaid = (float) VendorPayout::where('status', 'paid')
            ->when($f, fn ($q) => $q->where('paid_at', '>=', $f))
            ->when($t, fn ($q) => $q->where('paid_at', '<=', $t))
            ->sum('amount');

        // ── Business expenses ─────────────────────────────────────────────────
        $businessExpenses = (float) BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED)
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from->toDateString()))
            ->when($to,   fn ($q) => $q->where('expense_date', '<=', $to->toDateString()))
            ->sum('amount');

        // ── Derived ───────────────────────────────────────────────────────────
        $netProfit   = $grossProfit - $businessExpenses;
        $cashBalance = $totalMoneyReceived - $vendorPaid - $businessExpenses;

        // ── Expense breakdown by category ─────────────────────────────────────
        $expenseByCategory = BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED)
            ->selectRaw('category, SUM(amount) as total')
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from->toDateString()))
            ->when($to,   fn ($q) => $q->where('expense_date', '<=', $to->toDateString()))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();

        // ── Summaries ─────────────────────────────────────────────────────────
        $clientSummaries = $this->clientBalances();
        $vendorSummaries = $this->vendorBalances();

        // ── Recent activity (always all-time, unfiltered) ─────────────────────
        $recentPayments = ClientPayment::with('client')
            ->where('status', ClientPayment::STATUS_CONFIRMED)
            ->latest('received_at')
            ->limit(5)
            ->get();

        $recentPayouts = VendorPayout::with('vendor')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(5)
            ->get();

        $recentExpenses = BusinessExpense::with('createdBy')
            ->where('status', '!=', BusinessExpense::STATUS_VOIDED)
            ->latest('expense_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'total_money_received' => $totalMoneyReceived,
            'credits_added'        => $creditsAdded,
            'credits_used'         => $creditsUsed,
            'credits_refunded'     => $creditsRefunded,
            'credits_remaining'    => $creditsRemaining,
            'files_uploaded'       => $filesUploaded,
            'files_completed'      => $filesCompleted,
            'revenue_earned'       => $revenueEarned,
            'vendor_cost'          => $vendorCost,
            'gross_profit'         => $grossProfit,
            'vendor_pending'       => $vendorPending,
            'vendor_payable'       => $vendorPayable,
            'vendor_paid'          => $vendorPaid,
            'business_expenses'    => $businessExpenses,
            'net_profit'           => $netProfit,
            'cash_balance'         => $cashBalance,
            'expense_by_category'  => $expenseByCategory,
            'client_summaries'     => $clientSummaries,
            'vendor_summaries'     => $vendorSummaries,
            'recent_payments'      => $recentPayments,
            'recent_payouts'       => $recentPayouts,
            'recent_expenses'      => $recentExpenses,
        ];
    }

    /**
     * Per-client balance summary (all-time, current balances).
     *
     * @return Collection<int, array{
     *   client: Client,
     *   total_paid: float,
     *   credits_added: int,
     *   credits_used: int,
     *   credit_balance: int,
     * }>
     */
    public function clientBalances(): Collection
    {
        $clients = Client::with(['user'])->get();

        // Fetch aggregates in two bulk queries rather than N+1
        $paidByClient = ClientPayment::where('status', ClientPayment::STATUS_CONFIRMED)
            ->selectRaw('client_id, SUM(amount_received) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $creditsByClient = ClientCreditTransaction::selectRaw(
            'client_id, type, SUM(credits_delta) as total'
        )
            ->whereIn('type', [
                ClientCreditTransaction::TYPE_PAYMENT_CREDIT,
                ClientCreditTransaction::TYPE_ORDER_DEBIT,
            ])
            ->groupBy('client_id', 'type')
            ->get()
            ->groupBy('client_id');

        return $clients->map(function (Client $client) use ($paidByClient, $creditsByClient) {
            $txRows = $creditsByClient->get($client->id, collect());

            $creditsAdded = (int) $txRows->firstWhere('type', ClientCreditTransaction::TYPE_PAYMENT_CREDIT)?->total ?? 0;
            $creditsUsed  = abs((int) $txRows->firstWhere('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)?->total ?? 0);

            return [
                'client'        => $client,
                'total_paid'    => (float) ($paidByClient->get($client->id) ?? 0),
                'credits_added' => $creditsAdded,
                'credits_used'  => $creditsUsed,
                'credit_balance'=> $client->credit_balance,
            ];
        })->sortByDesc('total_paid')->values();
    }

    /**
     * Per-vendor balance summary (current balances + all-time paid total).
     *
     * @return Collection<int, array{
     *   vendor: User,
     *   pending_earning: float,
     *   approved_payable: float,
     *   total_paid: float,
     *   files_completed: int,
     * }>
     */
    public function vendorBalances(): Collection
    {
        $vendors = User::where('role', 'vendor')->orderBy('name')->get();

        $paidByVendor = VendorPayout::where('status', 'paid')
            ->selectRaw('user_id, SUM(amount) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $filesByVendor = Order::whereNotNull('vendor_approved_at')
            ->selectRaw('claimed_by, COUNT(*) as total')
            ->groupBy('claimed_by')
            ->pluck('total', 'claimed_by');

        return $vendors->map(fn (User $vendor) => [
            'vendor'           => $vendor,
            'pending_earning'  => (float) $vendor->pending_earning_balance,
            'approved_payable' => (float) $vendor->approved_payable_balance,
            'total_paid'       => (float) ($paidByVendor->get($vendor->id) ?? 0),
            'files_completed'  => (int) ($filesByVendor->get($vendor->id) ?? 0),
        ]);
    }
}
