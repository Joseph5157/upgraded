<x-admin-layout>

    {{-- ── Page Header ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Finance Dashboard</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                @if($from || $to)
                    {{ $from?->format('d M Y') ?? '…' }} — {{ $to?->format('d M Y') ?? 'today' }}
                @else
                    All-time summary
                @endif
            </p>
        </div>
        {{-- Date range filter --}}
        <form method="GET" action="{{ route('admin.finance.dashboard') }}" class="flex items-center gap-2">
            <input type="date" name="from" value="{{ $from?->toDateString() ?? old('from') }}"
                class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50">
            <span class="text-[10px] text-gray-400">to</span>
            <input type="date" name="to" value="{{ $to?->toDateString() ?? old('to') }}"
                class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50">
            <button type="submit"
                class="px-3 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">
                Filter
            </button>
            @if($from || $to)
                <a href="{{ route('admin.finance.dashboard') }}"
                    class="px-3 py-2 bg-white dark:bg-white/[0.04] text-gray-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] hover:text-gray-900 dark:hover:text-white transition-all">
                    Clear
                </a>
            @endif
        </form>
    </div>

    @if($from || $to)
        <div class="flex items-start gap-3 p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-amber-500 text-xs font-semibold mb-6">
            <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <span>Date filter is active. <strong>Current balance fields</strong> (Credits Remaining, Vendor Pending, Vendor Payable) always show the live balance and are not affected by the date range.</span>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 1 — Cash & Profit                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-3">Cash &amp; Profit</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

        @php
        $cashCards = [
            ['label' => 'Total Received',  'value' => '₹'.number_format($total_money_received, 2), 'icon' => 'indian-rupee',   'color' => 'text-green-400 bg-green-500/10',  'note' => 'Confirmed client payments'],
            ['label' => 'Vendor Paid',     'value' => '₹'.number_format($vendor_paid, 2),           'icon' => 'send',           'color' => 'text-indigo-400 bg-indigo-500/10', 'note' => 'Paid out to vendors'],
            ['label' => 'Business Expenses','value' => '₹'.number_format($business_expenses, 2),    'icon' => 'trending-down',  'color' => 'text-red-400 bg-red-500/10',      'note' => 'Salaries, software, fees…'],
            ['label' => 'Cash Balance',    'value' => '₹'.number_format($cash_balance, 2),          'icon' => 'wallet',         'color' => ($cash_balance >= 0 ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10'), 'note' => 'Received − paid − expenses'],
        ];
        @endphp

        @foreach($cashCards as $card)
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 space-y-1.5">
                <div class="w-8 h-8 {{ $card['color'] }} rounded-xl flex items-center justify-center mb-2">
                    <i data-lucide="{{ $card['icon'] }}" class="w-4 h-4"></i>
                </div>
                <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em]">{{ $card['label'] }}</p>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white font-mono">{{ $card['value'] }}</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500">{{ $card['note'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Revenue row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @php
        $profitCards = [
            ['label' => 'Revenue Earned', 'value' => '₹'.number_format($revenue_earned, 2),  'icon' => 'trending-up',      'color' => 'text-blue-400 bg-blue-500/10',     'note' => 'Client amount (approved orders)'],
            ['label' => 'Vendor Cost',    'value' => '₹'.number_format($vendor_cost, 2),      'icon' => 'user-check',       'color' => 'text-violet-400 bg-violet-500/10', 'note' => 'Vendor amount (approved orders)'],
            ['label' => 'Gross Profit',   'value' => '₹'.number_format($gross_profit, 2),     'icon' => 'bar-chart-2',      'color' => ($gross_profit >= 0 ? 'text-teal-400 bg-teal-500/10' : 'text-red-400 bg-red-500/10'),   'note' => 'Revenue − vendor cost'],
            ['label' => 'Net Profit',     'value' => '₹'.number_format($net_profit, 2),       'icon' => 'activity',         'color' => ($net_profit >= 0 ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10'), 'note' => 'Gross profit − expenses'],
        ];
        @endphp
        @foreach($profitCards as $card)
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 space-y-1.5">
                <div class="w-8 h-8 {{ $card['color'] }} rounded-xl flex items-center justify-center mb-2">
                    <i data-lucide="{{ $card['icon'] }}" class="w-4 h-4"></i>
                </div>
                <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em]">{{ $card['label'] }}</p>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white font-mono">{{ $card['value'] }}</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500">{{ $card['note'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 2 — Credits & Files                                             --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        {{-- Credits --}}
        <div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-3">Credits</p>
            <div class="grid grid-cols-2 gap-4">
                @php
                $creditCards = [
                    ['label' => 'Credits Added',    'value' => number_format($credits_added),    'icon' => 'plus-circle',   'color' => 'text-green-400 bg-green-500/10',    'note' => 'Via confirmed payments'],
                    ['label' => 'Credits Used',     'value' => number_format($credits_used),     'icon' => 'minus-circle',  'color' => 'text-orange-400 bg-orange-500/10',  'note' => 'Consumed by orders'],
                    ['label' => 'Credits Refunded', 'value' => number_format($credits_refunded), 'icon' => 'refresh-ccw',   'color' => 'text-amber-400 bg-amber-500/10',    'note' => 'Refund / cancel credits'],
                    ['label' => 'Credits Remaining','value' => number_format($credits_remaining),'icon' => 'database',      'color' => 'text-indigo-400 bg-indigo-500/10',  'note' => 'Live balance (all clients)'],
                ];
                @endphp
                @foreach($creditCards as $card)
                    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-4 space-y-1">
                        <div class="w-7 h-7 {{ $card['color'] }} rounded-lg flex items-center justify-center mb-1.5">
                            <i data-lucide="{{ $card['icon'] }}" class="w-3.5 h-3.5"></i>
                        </div>
                        <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em]">{{ $card['label'] }}</p>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white font-mono">{{ $card['value'] }}</h3>
                        <p class="text-[9px] text-gray-400 dark:text-slate-500">{{ $card['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Files & Vendor dues --}}
        <div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-3">Files &amp; Vendor dues</p>
            <div class="grid grid-cols-2 gap-4">
                @php
                $fileCards = [
                    ['label' => 'Files Uploaded',  'value' => number_format($files_uploaded),  'icon' => 'upload',        'color' => 'text-cyan-400 bg-cyan-500/10',      'note' => 'Non-cancelled orders'],
                    ['label' => 'Files Completed', 'value' => number_format($files_completed), 'icon' => 'check-circle',  'color' => 'text-teal-400 bg-teal-500/10',      'note' => 'Vendor-approved orders'],
                    ['label' => 'Pending Review',  'value' => '₹'.number_format($vendor_pending, 2),  'icon' => 'clock',  'color' => 'text-amber-400 bg-amber-500/10',    'note' => 'Awaiting admin approval'],
                    ['label' => 'Vendor Payable',  'value' => '₹'.number_format($vendor_payable, 2),  'icon' => 'coins',  'color' => 'text-indigo-400 bg-indigo-500/10',  'note' => 'Approved, ready to pay'],
                ];
                @endphp
                @foreach($fileCards as $card)
                    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-4 space-y-1">
                        <div class="w-7 h-7 {{ $card['color'] }} rounded-lg flex items-center justify-center mb-1.5">
                            <i data-lucide="{{ $card['icon'] }}" class="w-3.5 h-3.5"></i>
                        </div>
                        <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.2em]">{{ $card['label'] }}</p>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white font-mono">{{ $card['value'] }}</h3>
                        <p class="text-[9px] text-gray-400 dark:text-slate-500">{{ $card['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 3 — Vendor table                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
            <div class="w-8 h-8 bg-violet-500/10 rounded-xl flex items-center justify-center text-violet-400">
                <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Vendor summary</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">Live balances + all-time paid</p>
            </div>
        </div>
        @if($vendor_summaries->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                            <th class="px-6 py-3">Vendor</th>
                            <th class="px-4 py-3 text-right">Pending (₹)</th>
                            <th class="px-4 py-3 text-right">Payable (₹)</th>
                            <th class="px-4 py-3 text-right">Total Paid (₹)</th>
                            <th class="px-4 py-3 text-right">Files Done</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($vendor_summaries as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-violet-500/10 text-violet-400 flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($row['vendor']->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $row['vendor']->name }}</p>
                                            <p class="text-[9px] text-gray-400 dark:text-slate-500 font-mono">#{{ $row['vendor']->portal_number }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono {{ $row['pending_earning'] > 0 ? 'text-amber-500' : 'text-gray-400 dark:text-slate-500' }}">{{ number_format($row['pending_earning'], 2) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono {{ $row['approved_payable'] > 0 ? 'text-indigo-400' : 'text-gray-400 dark:text-slate-500' }} font-bold">{{ number_format($row['approved_payable'], 2) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono text-gray-700 dark:text-slate-300">{{ number_format($row['total_paid'], 2) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono text-gray-500 dark:text-slate-400">{{ number_format($row['files_completed']) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-10 text-center">
                <p class="text-sm text-gray-400 dark:text-slate-500">No vendors yet.</p>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 4 — Client table                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-400">
                <i data-lucide="users" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Client summary</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">All-time payments · live credit balance</p>
            </div>
        </div>
        @if($client_summaries->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                            <th class="px-6 py-3">Client</th>
                            <th class="px-4 py-3 text-right">Total Paid (₹)</th>
                            <th class="px-4 py-3 text-right">Credits Added</th>
                            <th class="px-4 py-3 text-right">Credits Used</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($client_summaries as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($row['client']->name, 0, 2)) }}
                                        </div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $row['client']->name }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono text-green-500">{{ number_format($row['total_paid'], 2) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono text-gray-700 dark:text-slate-300">{{ number_format($row['credits_added']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono text-gray-700 dark:text-slate-300">{{ number_format($row['credits_used']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="px-2 py-0.5 text-[9px] font-bold font-mono rounded-lg {{ $row['credit_balance'] > 0 ? 'text-indigo-400 bg-indigo-500/10 border border-indigo-500/10' : 'text-red-400 bg-red-500/10 border border-red-500/10' }}">
                                        {{ number_format($row['credit_balance']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-10 text-center">
                <p class="text-sm text-gray-400 dark:text-slate-500">No clients yet.</p>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 5 — Expenses & Recent activity                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        {{-- Expense by category --}}
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 bg-red-500/10 rounded-xl flex items-center justify-center text-red-400">
                    <i data-lucide="trending-down" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">Expenses by category</h2>
                    <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">Total: ₹{{ number_format($business_expenses, 2) }}</p>
                </div>
            </div>
            @php $allCategories = \App\Models\BusinessExpense::categories(); @endphp
            @if(count($expense_by_category))
                <div class="space-y-2">
                    @foreach($allCategories as $key => $label)
                        @if(isset($expense_by_category[$key]))
                            @php $pct = $business_expenses > 0 ? round(($expense_by_category[$key] / $business_expenses) * 100) : 0; @endphp
                            <div>
                                <div class="flex justify-between items-center mb-0.5">
                                    <span class="text-[10px] text-gray-600 dark:text-slate-400">{{ $label }}</span>
                                    <span class="text-[10px] font-mono font-bold text-gray-900 dark:text-white">₹{{ number_format($expense_by_category[$key], 2) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-white/[0.05] rounded-full h-1">
                                    <div class="bg-red-400 h-1 rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-[10px] text-gray-400 dark:text-slate-500 py-4 text-center">No expenses recorded{{ ($from || $to) ? ' in this period' : '' }}.</p>
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Recent activity</h2>
            </div>

            @if($recent_payments->count() || $recent_payouts->count() || $recent_expenses->count())
                <div class="space-y-2">
                    @foreach($recent_payments as $p)
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-white/[0.04]">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 flex-shrink-0"></span>
                                <span class="text-[10px] text-gray-600 dark:text-slate-400">{{ $p->client?->name ?? '—' }}</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-green-500">+₹{{ number_format($p->amount_received, 0) }}</span>
                        </div>
                    @endforeach
                    @foreach($recent_payouts as $p)
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-white/[0.04]">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                                <span class="text-[10px] text-gray-600 dark:text-slate-400">{{ $p->vendor?->name ?? '—' }} (payout)</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-indigo-400">−₹{{ number_format($p->amount, 0) }}</span>
                        </div>
                    @endforeach
                    @foreach($recent_expenses as $e)
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-white/[0.04] last:border-0">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                                <span class="text-[10px] text-gray-600 dark:text-slate-400">{{ \App\Models\BusinessExpense::categories()[$e->category] ?? $e->category }}</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-red-400">−₹{{ number_format($e->amount, 0) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-[10px] text-gray-400 dark:text-slate-500 py-4 text-center">No activity yet.</p>
            @endif
        </div>

    </div>

    {{-- Formula reference --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
        <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-4">Formula reference</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-2 text-[10px] font-mono text-gray-500 dark:text-slate-500">
            <div>Gross Profit = Revenue Earned − Vendor Cost</div>
            <div>Net Profit = Gross Profit − Business Expenses</div>
            <div>Cash Balance = Total Received − Vendor Paid − Expenses</div>
            <div>Vendor Pending = sum(pending_earning_balance)</div>
            <div>Vendor Payable = sum(approved_payable_balance)</div>
            <div>Credits Remaining = sum(clients.credit_balance) [live]</div>
        </div>
    </div>

    <script>lucide.createIcons();</script>

</x-admin-layout>
