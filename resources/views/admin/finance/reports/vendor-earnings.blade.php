<x-admin-layout>

    @php $csvUrl = route('admin.finance.reports.vendor-earnings.csv') . '?' . http_build_query(array_filter(request()->only(['from','to','vendor_id','type','status']))); @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.finance.reports.index') }}" class="text-[10px] text-indigo-400 hover:underline font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Reports
            </a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Vendor Earnings Report</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">{{ $transactions->total() }} records</p>
        </div>
        <a href="{{ $csvUrl }}" class="flex items-center gap-2 px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-green-500/15 transition-all">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
        </a>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('admin.finance.reports.vendor-earnings') }}" class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 mb-6 flex flex-wrap items-end gap-3">
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
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Vendor</label>
            <select name="vendor_id" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All vendors</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ $filters['vendor_id'] == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Type</label>
            <select name="type" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All types</option>
                @foreach(['pending_order_earning','approve_earning','payout','reversal','manual_adjustment','correction'] as $t)
                    <option value="{{ $t }}" {{ $filters['type'] === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Status</label>
            <select name="status" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All</option>
                <option value="posted" {{ $filters['status'] === 'posted' ? 'selected' : '' }}>Posted</option>
                <option value="voided" {{ $filters['status'] === 'voided' ? 'selected' : '' }}>Voided</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">Filter</button>
        @if(array_filter([$filters['from'],$filters['to'],$filters['vendor_id'],$filters['type'],$filters['status']]))
            <a href="{{ route('admin.finance.reports.vendor-earnings') }}" class="px-4 py-2 text-gray-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        @if($transactions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Vendor</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Delta</th>
                            <th class="px-4 py-3 text-right">Pending After</th>
                            <th class="px-4 py-3 text-right">Approved After</th>
                            <th class="px-4 py-3 text-center">Order</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($transactions as $tx)
                            @php
                                $positive = $tx->amount_delta >= 0;
                                $typeColors = [
                                    'pending_order_earning' => 'text-amber-400 bg-amber-500/10',
                                    'approve_earning'       => 'text-green-400 bg-green-500/10',
                                    'payout'                => 'text-blue-400 bg-blue-500/10',
                                    'reversal'              => 'text-red-400 bg-red-500/10',
                                    'manual_adjustment'     => 'text-violet-400 bg-violet-500/10',
                                    'correction'            => 'text-gray-400 bg-gray-500/10',
                                ];
                                $typeColor = $typeColors[$tx->type] ?? 'text-gray-400 bg-gray-500/10';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 font-mono text-gray-400 dark:text-slate-500">{{ $tx->id }}</td>
                                <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">{{ $tx->vendor?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-lg {{ $typeColor }}">{{ str_replace('_',' ',$tx->type) }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-lg {{ $tx->status === 'voided' ? 'text-red-400 bg-red-500/10' : 'text-green-400 bg-green-500/10' }}">{{ $tx->status }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold {{ $positive ? 'text-green-500' : 'text-red-400' }}">
                                    {{ $positive ? '+' : '' }}₹{{ number_format($tx->amount_delta,2) }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-500 dark:text-slate-400">₹{{ number_format($tx->pending_balance_after,2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-500 dark:text-slate-400">₹{{ number_format($tx->approved_balance_after,2) }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-400 dark:text-slate-500 text-[9px]">{{ $tx->order_id ? '#'.$tx->order_id : '—' }}</td>
                                <td class="px-4 py-2.5 font-mono text-gray-500 dark:text-slate-400">{{ $tx->created_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 dark:border-white/[0.04]">{{ $transactions->links() }}</div>
        @else
            <div class="py-16 text-center"><p class="text-sm text-gray-400 dark:text-slate-500">No transactions match the selected filters.</p></div>
        @endif
    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
