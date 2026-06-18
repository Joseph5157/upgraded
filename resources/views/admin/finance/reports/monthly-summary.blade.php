<x-admin-layout>

    @php $csvUrl = route('admin.finance.reports.monthly-summary.csv') . '?' . http_build_query(array_filter(request()->only(['from','to']))); @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.finance.reports.index') }}" class="text-[10px] text-indigo-400 hover:underline font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Reports
            </a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Monthly Summary</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">{{ $rows->count() }} months</p>
        </div>
        <a href="{{ $csvUrl }}" class="flex items-center gap-2 px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-green-500/15 transition-all">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
        </a>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('admin.finance.reports.monthly-summary') }}" class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">From</label>
            <input type="date" name="from" value="{{ $filters['from']?->toDateString() }}"
                class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">To</label>
            <input type="date" name="to" value="{{ $filters['to']?->toDateString() }}"
                class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white focus:outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">Filter</button>
        @if($filters['from'] || $filters['to'])
            <a href="{{ route('admin.finance.reports.monthly-summary') }}" class="px-4 py-2 text-gray-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        @if($rows->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                        <tr>
                            <th class="px-4 py-3">Month</th>
                            <th class="px-4 py-3 text-right">Received</th>
                            <th class="px-4 py-3 text-right">Credits +</th>
                            <th class="px-4 py-3 text-right">Credits −</th>
                            <th class="px-4 py-3 text-center">Files</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                            <th class="px-4 py-3 text-right">Cost</th>
                            <th class="px-4 py-3 text-right">Gross Profit</th>
                            <th class="px-4 py-3 text-right">Expenses</th>
                            <th class="px-4 py-3 text-right">Net Profit</th>
                            <th class="px-4 py-3 text-right">Cash Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 font-bold font-mono text-gray-900 dark:text-white">{{ $row['month'] }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-green-500">₹{{ number_format($row['money_received'],2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-indigo-400">+{{ $row['credits_added'] }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-red-400">−{{ $row['credits_used'] }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-500 dark:text-slate-400">{{ $row['files_completed'] }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-700 dark:text-slate-300">₹{{ number_format($row['revenue_earned'],2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-red-400/80">₹{{ number_format($row['vendor_cost'],2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold {{ $row['gross_profit'] >= 0 ? 'text-teal-400' : 'text-red-400' }}">₹{{ number_format($row['gross_profit'],2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-orange-400">₹{{ number_format($row['business_expenses'],2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold {{ $row['net_profit'] >= 0 ? 'text-green-500' : 'text-red-500' }}">₹{{ number_format($row['net_profit'],2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold {{ $row['cash_balance'] >= 0 ? 'text-blue-400' : 'text-red-500' }}">₹{{ number_format($row['cash_balance'],2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    @php
                        $totals = [
                            'money_received'    => $rows->sum('money_received'),
                            'credits_added'     => $rows->sum('credits_added'),
                            'credits_used'      => $rows->sum('credits_used'),
                            'files_completed'   => $rows->sum('files_completed'),
                            'revenue_earned'    => $rows->sum('revenue_earned'),
                            'vendor_cost'       => $rows->sum('vendor_cost'),
                            'gross_profit'      => $rows->sum('gross_profit'),
                            'business_expenses' => $rows->sum('business_expenses'),
                            'net_profit'        => $rows->sum('net_profit'),
                            'cash_balance'      => $rows->sum('cash_balance'),
                        ];
                    @endphp
                    <tfoot class="border-t-2 border-gray-200 dark:border-white/[0.08] bg-gray-50 dark:bg-white/[0.02]">
                        <tr class="text-[9px] font-bold uppercase text-gray-500 dark:text-slate-400">
                            <td class="px-4 py-2.5">Total</td>
                            <td class="px-4 py-2.5 text-right font-mono text-green-500">₹{{ number_format($totals['money_received'],2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-indigo-400">+{{ $totals['credits_added'] }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-red-400">−{{ $totals['credits_used'] }}</td>
                            <td class="px-4 py-2.5 text-center font-mono">{{ $totals['files_completed'] }}</td>
                            <td class="px-4 py-2.5 text-right font-mono">₹{{ number_format($totals['revenue_earned'],2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-red-400/80">₹{{ number_format($totals['vendor_cost'],2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $totals['gross_profit'] >= 0 ? 'text-teal-400' : 'text-red-400' }}">₹{{ number_format($totals['gross_profit'],2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-orange-400">₹{{ number_format($totals['business_expenses'],2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $totals['net_profit'] >= 0 ? 'text-green-500' : 'text-red-500' }}">₹{{ number_format($totals['net_profit'],2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $totals['cash_balance'] >= 0 ? 'text-blue-400' : 'text-red-500' }}">₹{{ number_format($totals['cash_balance'],2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="py-16 text-center"><p class="text-sm text-gray-400 dark:text-slate-500">No data for the selected period.</p></div>
        @endif
    </div>

    {{-- Formula reference --}}
    <div class="mt-4 bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5">
        <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Formula Reference</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-[10px] font-mono text-gray-500 dark:text-slate-400">
            <span>Gross Profit = Revenue − Vendor Cost</span>
            <span>Net Profit = Gross Profit − Expenses</span>
            <span>Cash Balance = Received − Vendor Paid − Expenses</span>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
