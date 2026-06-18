<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\User;
use App\Services\Finance\FinanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
    public function __construct(protected FinanceReportService $reportService) {}

    // ─────────────────────────────────────────────────────────────────────
    // Reports index
    // ─────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('admin.finance.reports.index');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. Client Payments
    // ─────────────────────────────────────────────────────────────────────

    public function clientPayments(Request $request): View
    {
        $filters  = $this->parseFilters($request);
        $payments = $this->reportService->clientPaymentsQuery($filters)->paginate(25)->withQueryString();
        $clients  = Client::orderBy('name')->get();
        $total    = (float) $this->reportService->clientPaymentsQuery($filters)
                        ->where('status', ClientPayment::STATUS_CONFIRMED)->sum('amount_received');

        return view('admin.finance.reports.client-payments', compact('payments', 'filters', 'clients', 'total'));
    }

    public function clientPaymentsCsv(Request $request): StreamedResponse
    {
        $filters  = $this->parseFilters($request);
        $headers  = ['Payment ID', 'Client Name', 'Client Portal #', 'Amount Received', 'Credits Added',
                     'Rate Per Credit', 'Payment Mode', 'Transaction ID', 'Received At', 'Created By', 'Notes'];

        return response()->streamDownload(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $this->reportService->clientPaymentsQuery($filters)
                ->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->client?->name ?? '',
                            $row->client?->user?->portal_number ?? '',
                            $row->amount_received,
                            $row->credits_added,
                            $row->rate_per_credit,
                            $row->payment_mode ?? '',
                            $row->transaction_id ?? '',
                            $row->received_at?->format('Y-m-d H:i:s') ?? '',
                            $row->createdBy?->name ?? '',
                            $row->notes ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $this->reportService->csvFilename('client-payments', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Client Credit Ledger
    // ─────────────────────────────────────────────────────────────────────

    public function clientCreditLedger(Request $request): View
    {
        $filters      = $this->parseFilters($request);
        $transactions = $this->reportService->clientCreditLedgerQuery($filters)->paginate(25)->withQueryString();
        $clients      = Client::orderBy('name')->get();
        $types        = [
            ClientCreditTransaction::TYPE_PAYMENT_CREDIT,
            ClientCreditTransaction::TYPE_ORDER_DEBIT,
            ClientCreditTransaction::TYPE_REFUND_CREDIT,
            ClientCreditTransaction::TYPE_MANUAL_ADJUSTMENT,
            ClientCreditTransaction::TYPE_OPENING_BALANCE,
            ClientCreditTransaction::TYPE_CORRECTION,
        ];

        return view('admin.finance.reports.client-credit-ledger', compact('transactions', 'filters', 'clients', 'types'));
    }

    public function clientCreditLedgerCsv(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $headers = ['TX ID', 'Client Name', 'Portal #', 'Type', 'Credits Delta', 'Balance After',
                    'Rate Per Credit', 'Money Value', 'Payment ID', 'Order ID', 'Created By', 'Created At', 'Notes'];

        return response()->streamDownload(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $this->reportService->clientCreditLedgerQuery($filters)
                ->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->client?->name ?? '',
                            $row->client?->user?->portal_number ?? '',
                            $row->type,
                            $row->credits_delta,
                            $row->balance_after,
                            $row->rate_per_credit ?? '',
                            $row->money_value ?? '',
                            $row->client_payment_id ?? '',
                            $row->order_id ?? '',
                            $row->createdBy?->name ?? '',
                            $row->created_at?->format('Y-m-d H:i:s') ?? '',
                            $row->notes ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $this->reportService->csvFilename('client-credit-ledger', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Vendor Earnings
    // ─────────────────────────────────────────────────────────────────────

    public function vendorEarnings(Request $request): View
    {
        $filters      = $this->parseFilters($request);
        $transactions = $this->reportService->vendorEarningsQuery($filters)->paginate(25)->withQueryString();
        $vendors      = User::where('role', 'vendor')->orderBy('name')->get();

        return view('admin.finance.reports.vendor-earnings', compact('transactions', 'filters', 'vendors'));
    }

    public function vendorEarningsCsv(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $headers = ['TX ID', 'Vendor Name', 'Portal #', 'Order ID', 'Type', 'Status',
                    'Amount Delta', 'Pending After', 'Approved After', 'Files Count', 'Rate Per File',
                    'Created At', 'Notes'];

        return response()->streamDownload(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $this->reportService->vendorEarningsQuery($filters)
                ->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->vendor?->name ?? '',
                            $row->vendor?->portal_number ?? '',
                            $row->order_id ?? '',
                            $row->type,
                            $row->status,
                            $row->amount_delta,
                            $row->pending_balance_after,
                            $row->approved_balance_after,
                            $row->files_count ?? '',
                            $row->rate_per_file ?? '',
                            $row->created_at?->format('Y-m-d H:i:s') ?? '',
                            $row->notes ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $this->reportService->csvFilename('vendor-earnings', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Vendor Payouts
    // ─────────────────────────────────────────────────────────────────────

    public function vendorPayouts(Request $request): View
    {
        $filters  = $this->parseFilters($request);
        $payouts  = $this->reportService->vendorPayoutsQuery($filters)->paginate(25)->withQueryString();
        $vendors  = User::where('role', 'vendor')->orderBy('name')->get();
        $total    = (float) $this->reportService->vendorPayoutsQuery($filters)
                        ->where('status', 'paid')->sum('amount');

        return view('admin.finance.reports.vendor-payouts', compact('payouts', 'filters', 'vendors', 'total'));
    }

    public function vendorPayoutsCsv(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $headers = ['Payout ID', 'Vendor Name', 'Portal #', 'Amount', 'Payment Mode',
                    'Transaction ID', 'Paid At', 'Paid By', 'Status', 'Notes'];

        return response()->streamDownload(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $this->reportService->vendorPayoutsQuery($filters)
                ->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->vendor?->name ?? '',
                            $row->vendor?->portal_number ?? '',
                            $row->amount,
                            $row->payment_mode ?? '',
                            $row->reference_id ?? '',
                            $row->paid_at?->format('Y-m-d H:i:s') ?? '',
                            $row->paidBy?->name ?? '',
                            $row->status,
                            $row->notes ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $this->reportService->csvFilename('vendor-payouts', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Business Expenses
    // ─────────────────────────────────────────────────────────────────────

    public function expenses(Request $request): View
    {
        $filters    = $this->parseFilters($request);
        $expenses   = $this->reportService->expensesQuery($filters)->paginate(25)->withQueryString();
        $categories = BusinessExpense::categories();
        $total      = (float) $this->reportService->expensesQuery($filters)
                        ->where('status', '!=', BusinessExpense::STATUS_VOIDED)->sum('amount');

        return view('admin.finance.reports.expenses', compact('expenses', 'filters', 'categories', 'total'));
    }

    public function expensesCsv(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $headers = ['Expense ID', 'Category', 'Amount', 'Payment Mode', 'Reference ID',
                    'Expense Date', 'Notes', 'Created By', 'Created At'];
        $categoryLabels = BusinessExpense::categories();

        return response()->streamDownload(function () use ($filters, $headers, $categoryLabels) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $this->reportService->expensesQuery($filters)
                ->chunkById(500, function ($chunk) use ($handle, $categoryLabels) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $categoryLabels[$row->category] ?? $row->category,
                            $row->amount,
                            $row->payment_mode ?? '',
                            $row->reference_id ?? '',
                            $row->expense_date?->format('Y-m-d') ?? '',
                            $row->notes ?? '',
                            $row->createdBy?->name ?? '',
                            $row->created_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $this->reportService->csvFilename('expenses', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 6. Order Profit
    // ─────────────────────────────────────────────────────────────────────

    public function orderProfit(Request $request): View
    {
        $filters  = $this->parseFilters($request);
        $orders   = $this->reportService->orderProfitQuery($filters)->paginate(25)->withQueryString();
        $clients  = Client::orderBy('name')->get();
        $vendors  = User::where('role', 'vendor')->orderBy('name')->get();
        $profitQuery = $this->reportService->orderProfitQuery($filters);
        $totals   = [
            'client_amount' => (float) (clone $profitQuery)->sum('client_amount'),
            'vendor_amount' => (float) (clone $profitQuery)->sum('vendor_amount'),
            'gross_profit'  => (float) (clone $profitQuery)->sum('gross_profit'),
        ];

        return view('admin.finance.reports.order-profit', compact('orders', 'filters', 'clients', 'vendors', 'totals'));
    }

    public function orderProfitCsv(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $headers = ['Order ID', 'Client Name', 'Vendor Name', 'Credits Consumed',
                    'Client Rate/File', 'Client Amount', 'Vendor Rate/File', 'Vendor Amount',
                    'Gross Profit', 'Financial Locked At', 'Vendor Earning Status',
                    'Order Status', 'Created At', 'Vendor Approved At'];

        return response()->streamDownload(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $this->reportService->orderProfitQuery($filters)
                ->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        // Derive vendor earning status from order timestamps
                        if ($row->vendor_approved_at) {
                            $earningStatus = 'Approved';
                        } elseif ($row->vendor_rejected_at) {
                            $earningStatus = 'Rejected';
                        } elseif ($row->vendor_submitted_at) {
                            $earningStatus = 'Pending';
                        } else {
                            $earningStatus = 'N/A';
                        }

                        fputcsv($handle, [
                            $row->id,
                            $row->client?->name ?? '',
                            $row->vendor?->name ?? '',
                            $row->credits_consumed ?? '',
                            $row->client_rate_per_file ?? '',
                            $row->client_amount ?? '',
                            $row->vendor_rate_per_file ?? '',
                            $row->vendor_amount ?? '',
                            $row->gross_profit ?? '',
                            $row->financial_locked_at?->format('Y-m-d H:i:s') ?? '',
                            $earningStatus,
                            $row->status?->value ?? '',
                            $row->created_at?->format('Y-m-d H:i:s') ?? '',
                            $row->vendor_approved_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $this->reportService->csvFilename('order-profit', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 7. Monthly Summary
    // ─────────────────────────────────────────────────────────────────────

    public function monthlySummary(Request $request): View
    {
        $filters  = $this->parseFilters($request);
        $rows     = $this->reportService->monthlySummary($filters);

        return view('admin.finance.reports.monthly-summary', compact('rows', 'filters'));
    }

    public function monthlySummaryCsv(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $headers = ['Month', 'Money Received', 'Credits Added', 'Credits Used',
                    'Files Uploaded', 'Files Completed', 'Revenue Earned', 'Vendor Cost',
                    'Gross Profit', 'Vendor Paid', 'Business Expenses', 'Net Profit', 'Cash Balance'];

        return response()->streamDownload(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($this->reportService->monthlySummary($filters) as $row) {
                fputcsv($handle, [
                    $row['month'],
                    number_format($row['money_received'], 2, '.', ''),
                    $row['credits_added'],
                    $row['credits_used'],
                    $row['files_uploaded'],
                    $row['files_completed'],
                    number_format($row['revenue_earned'], 2, '.', ''),
                    number_format($row['vendor_cost'], 2, '.', ''),
                    number_format($row['gross_profit'], 2, '.', ''),
                    number_format($row['vendor_paid'], 2, '.', ''),
                    number_format($row['business_expenses'], 2, '.', ''),
                    number_format($row['net_profit'], 2, '.', ''),
                    number_format($row['cash_balance'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $this->reportService->csvFilename('monthly-summary', $filters), ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────

    private function parseFilters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to   = $request->filled('to')   ? Carbon::parse($request->input('to'))->endOfDay()     : null;

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from'         => $from,
            'to'           => $to,
            'client_id'    => $request->filled('client_id')    ? (int) $request->input('client_id')    : null,
            'vendor_id'    => $request->filled('vendor_id')    ? (int) $request->input('vendor_id')    : null,
            'payment_mode' => $request->filled('payment_mode') ? $request->input('payment_mode')       : null,
            'status'       => $request->filled('status')       ? $request->input('status')             : null,
            'type'         => $request->filled('type')         ? $request->input('type')               : null,
            'category'     => $request->filled('category')     ? $request->input('category')           : null,
        ];
    }
}
