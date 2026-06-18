<x-admin-layout>

    @php $csvUrl = route('admin.finance.reports.expenses.csv') . '?' . http_build_query(array_filter(request()->only(['from','to','category','payment_mode']))); @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.finance.reports.index') }}" class="text-[10px] text-indigo-400 hover:underline font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Reports
            </a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Business Expenses Report</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">{{ $expenses->total() }} records · Total: ₹{{ number_format($total, 2) }}</p>
        </div>
        <a href="{{ $csvUrl }}" class="flex items-center gap-2 px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-green-500/15 transition-all">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
        </a>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('admin.finance.reports.expenses') }}" class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 mb-6 flex flex-wrap items-end gap-3">
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
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Category</label>
            <select name="category" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All categories</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $filters['category'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Mode</label>
            <select name="payment_mode" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All modes</option>
                @foreach(['upi','bank_transfer','cash','card','auto_deducted'] as $mode)
                    <option value="{{ $mode }}" {{ $filters['payment_mode'] === $mode ? 'selected' : '' }}>{{ strtoupper(str_replace('_',' ',$mode)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">Filter</button>
        @if(array_filter([$filters['from'],$filters['to'],$filters['category'],$filters['payment_mode']]))
            <a href="{{ route('admin.finance.reports.expenses') }}" class="px-4 py-2 text-gray-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        @if($expenses->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3 text-center">Category</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-center">Mode</th>
                            <th class="px-4 py-3 text-center">Reference</th>
                            <th class="px-4 py-3 text-center">Recorded By</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($expenses as $e)
                            @php
                                $catColors = [
                                    'staff_salary'     => 'text-indigo-400 bg-indigo-500/10',
                                    'software'         => 'text-violet-400 bg-violet-500/10',
                                    'razorpay_charges' => 'text-orange-400 bg-orange-500/10',
                                    'hosting'          => 'text-blue-400 bg-blue-500/10',
                                    'internet'         => 'text-cyan-400 bg-cyan-500/10',
                                    'domain'           => 'text-teal-400 bg-teal-500/10',
                                    'office'           => 'text-amber-400 bg-amber-500/10',
                                    'refund_loss'      => 'text-red-400 bg-red-500/10',
                                    'other'            => 'text-gray-400 bg-gray-500/10',
                                ];
                                $catColor = $catColors[$e->category] ?? 'text-gray-400 bg-gray-500/10';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 font-mono text-gray-400 dark:text-slate-500">{{ $e->id }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-lg {{ $catColor }}">{{ $categories[$e->category] ?? $e->category }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-red-400 font-bold">₹{{ number_format($e->amount,2) }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-500 dark:text-slate-400 uppercase">{{ str_replace('_',' ',$e->payment_mode??'—') }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-400 dark:text-slate-500 text-[9px]">{{ \Illuminate\Support\Str::limit($e->reference_id??'—',14) }}</td>
                                <td class="px-4 py-2.5 text-center text-gray-500 dark:text-slate-400">{{ $e->createdBy?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $e->expense_date?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 dark:border-white/[0.04]">{{ $expenses->links() }}</div>
        @else
            <div class="py-16 text-center"><p class="text-sm text-gray-400 dark:text-slate-500">No expenses match the selected filters.</p></div>
        @endif
    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
