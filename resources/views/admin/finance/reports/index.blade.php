<x-admin-layout>

    <div class="mb-6">
        <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Finance Reports</h1>
        <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Download or preview finance data as CSV</p>
    </div>

    @php
    $reports = [
        [
            'name'        => 'Client Payments',
            'desc'        => 'All money received from clients — amount, credits added, mode, transaction ID.',
            'icon'        => 'indian-rupee',
            'color'       => 'text-green-400 bg-green-500/10 border-green-500/15',
            'view_route'  => 'admin.finance.reports.client-payments',
            'csv_route'   => 'admin.finance.reports.client-payments.csv',
        ],
        [
            'name'        => 'Client Credit Ledger',
            'desc'        => 'Every credit transaction — payments, debits, refunds, adjustments.',
            'icon'        => 'book-open',
            'color'       => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/15',
            'view_route'  => 'admin.finance.reports.client-credit-ledger',
            'csv_route'   => 'admin.finance.reports.client-credit-ledger.csv',
        ],
        [
            'name'        => 'Vendor Earnings',
            'desc'        => 'Vendor earning transactions — pending, approved, reversed, payout rows.',
            'icon'        => 'user-check',
            'color'       => 'text-violet-400 bg-violet-500/10 border-violet-500/15',
            'view_route'  => 'admin.finance.reports.vendor-earnings',
            'csv_route'   => 'admin.finance.reports.vendor-earnings.csv',
        ],
        [
            'name'        => 'Vendor Payouts',
            'desc'        => 'All payouts made to vendors — amount, mode, reference ID, date.',
            'icon'        => 'send',
            'color'       => 'text-blue-400 bg-blue-500/10 border-blue-500/15',
            'view_route'  => 'admin.finance.reports.vendor-payouts',
            'csv_route'   => 'admin.finance.reports.vendor-payouts.csv',
        ],
        [
            'name'        => 'Business Expenses',
            'desc'        => 'Staff salary, software, hosting, domain, Razorpay fees, and other costs.',
            'icon'        => 'trending-down',
            'color'       => 'text-red-400 bg-red-500/10 border-red-500/15',
            'view_route'  => 'admin.finance.reports.expenses',
            'csv_route'   => 'admin.finance.reports.expenses.csv',
        ],
        [
            'name'        => 'Order Profit',
            'desc'        => 'Per-order client amount, vendor cost, and gross profit for approved orders.',
            'icon'        => 'bar-chart-2',
            'color'       => 'text-teal-400 bg-teal-500/10 border-teal-500/15',
            'view_route'  => 'admin.finance.reports.order-profit',
            'csv_route'   => 'admin.finance.reports.order-profit.csv',
        ],
        [
            'name'        => 'Monthly Summary',
            'desc'        => 'Month-by-month totals: received, credits, files, revenue, expenses, profit.',
            'icon'        => 'calendar',
            'color'       => 'text-amber-400 bg-amber-500/10 border-amber-500/15',
            'view_route'  => 'admin.finance.reports.monthly-summary',
            'csv_route'   => 'admin.finance.reports.monthly-summary.csv',
        ],
    ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($reports as $report)
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 {{ $report['color'] }} border rounded-xl flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $report['icon'] }}" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white">{{ $report['name'] }}</h2>
                        <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">{{ $report['desc'] }}</p>

                        <form method="GET" action="{{ route($report['view_route']) }}" class="mt-3 flex items-center gap-2">
                            <input type="date" name="from" placeholder="From"
                                class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-lg px-2.5 py-1.5 text-[10px] font-mono text-gray-900 dark:text-white focus:outline-none">
                            <input type="date" name="to" placeholder="To"
                                class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-lg px-2.5 py-1.5 text-[10px] font-mono text-gray-900 dark:text-white focus:outline-none">
                            <button type="submit"
                                class="px-3 py-1.5 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-indigo-600/20 transition-all whitespace-nowrap">
                                View
                            </button>
                            <button type="submit" formaction="{{ route($report['csv_route']) }}"
                                class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-green-500/15 transition-all whitespace-nowrap flex items-center gap-1">
                                <i data-lucide="download" class="w-3 h-3"></i> CSV
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>lucide.createIcons();</script>

</x-admin-layout>
