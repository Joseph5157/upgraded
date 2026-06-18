<x-admin-layout>

    @php $csvUrl = route('admin.finance.reports.order-profit.csv') . '?' . http_build_query(array_filter(request()->only(['from','to','client_id','vendor_id']))); @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.finance.reports.index') }}" class="text-[10px] text-indigo-400 hover:underline font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Reports
            </a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Order Profit Report</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                {{ $orders->total() }} orders · Revenue ₹{{ number_format($totals['client_amount'],2) }} · Cost ₹{{ number_format($totals['vendor_amount'],2) }} · Profit ₹{{ number_format($totals['gross_profit'],2) }}
            </p>
        </div>
        <a href="{{ $csvUrl }}" class="flex items-center gap-2 px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-green-500/15 transition-all">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
        </a>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('admin.finance.reports.order-profit') }}" class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 mb-6 flex flex-wrap items-end gap-3">
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
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Client</label>
            <select name="client_id" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All clients</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ $filters['client_id'] == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Vendor</label>
            <select name="vendor_id" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All vendors</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ $filters['vendor_id'] == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">Filter</button>
        @if(array_filter([$filters['from'],$filters['to'],$filters['client_id'],$filters['vendor_id']]))
            <a href="{{ route('admin.finance.reports.order-profit') }}" class="px-4 py-2 text-gray-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        @if($orders->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Vendor</th>
                            <th class="px-4 py-3 text-center">Files</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                            <th class="px-4 py-3 text-right">Cost</th>
                            <th class="px-4 py-3 text-right">Profit</th>
                            <th class="px-4 py-3">Approved</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($orders as $o)
                            @php $profit = ($o->gross_profit ?? ($o->client_amount - $o->vendor_amount)); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 font-mono text-gray-400 dark:text-slate-500">{{ $o->id }}</td>
                                <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">{{ $o->client?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-gray-700 dark:text-slate-300">{{ $o->vendor?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-500 dark:text-slate-400">{{ $o->credits_consumed ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-green-500 font-bold">₹{{ number_format($o->client_amount ?? 0, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-red-400">₹{{ number_format($o->vendor_amount ?? 0, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold {{ $profit >= 0 ? 'text-teal-400' : 'text-red-400' }}">₹{{ number_format($profit, 2) }}</td>
                                <td class="px-4 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $o->vendor_approved_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200 dark:border-white/[0.08]">
                        <tr class="text-[9px] font-bold uppercase text-gray-500 dark:text-slate-400">
                            <td colspan="4" class="px-4 py-2.5 text-right">Page totals:</td>
                            <td class="px-4 py-2.5 text-right font-mono text-green-500">₹{{ number_format($orders->sum('client_amount'),2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-red-400">₹{{ number_format($orders->sum('vendor_amount'),2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-teal-400">₹{{ number_format($orders->sum('gross_profit') ?: ($orders->sum('client_amount') - $orders->sum('vendor_amount')),2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 dark:border-white/[0.04]">{{ $orders->links() }}</div>
        @else
            <div class="py-16 text-center"><p class="text-sm text-gray-400 dark:text-slate-500">No orders match the selected filters.</p></div>
        @endif
    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
