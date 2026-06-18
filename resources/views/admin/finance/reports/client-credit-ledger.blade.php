<x-admin-layout>

    @php $csvUrl = route('admin.finance.reports.client-credit-ledger.csv') . '?' . http_build_query(array_filter(request()->only(['from','to','client_id','type']))); @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.finance.reports.index') }}" class="text-[10px] text-indigo-400 hover:underline font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Reports
            </a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Client Credit Ledger</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">{{ $transactions->total() }} records</p>
        </div>
        <a href="{{ $csvUrl }}" class="flex items-center gap-2 px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-green-500/15 transition-all">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
        </a>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('admin.finance.reports.client-credit-ledger') }}" class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 mb-6 flex flex-wrap items-end gap-3">
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
            <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Type</label>
            <select name="type" class="bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none">
                <option value="">All types</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" {{ $filters['type'] === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">Filter</button>
        @if(array_filter([$filters['from'],$filters['to'],$filters['client_id'],$filters['type']]))
            <a href="{{ route('admin.finance.reports.client-credit-ledger') }}" class="px-4 py-2 text-gray-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">Clear</a>
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
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-right">Delta</th>
                            <th class="px-4 py-3 text-right">Balance After</th>
                            <th class="px-4 py-3 text-right">Money Value</th>
                            <th class="px-4 py-3 text-center">Ref</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($transactions as $tx)
                            @php
                                $positive = $tx->credits_delta >= 0;
                                $typeColors = [
                                    'payment_credit'    => 'text-green-400 bg-green-500/10',
                                    'order_debit'       => 'text-red-400 bg-red-500/10',
                                    'refund_credit'     => 'text-teal-400 bg-teal-500/10',
                                    'manual_adjustment' => 'text-amber-400 bg-amber-500/10',
                                    'opening_balance'   => 'text-indigo-400 bg-indigo-500/10',
                                    'correction'        => 'text-violet-400 bg-violet-500/10',
                                ];
                                $typeColor = $typeColors[$tx->type] ?? 'text-gray-400 bg-gray-500/10';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 font-mono text-gray-400 dark:text-slate-500">{{ $tx->id }}</td>
                                <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">{{ $tx->client?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-lg {{ $typeColor }}">{{ str_replace('_',' ',$tx->type) }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold {{ $positive ? 'text-green-500' : 'text-red-400' }}">
                                    {{ $positive ? '+' : '' }}{{ $tx->credits_delta }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-700 dark:text-slate-300">{{ $tx->balance_after }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-gray-500 dark:text-slate-400">
                                    {{ $tx->money_value ? '₹'.number_format($tx->money_value,2) : '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-400 dark:text-slate-500 text-[9px]">
                                    @if($tx->client_payment_id) P#{{ $tx->client_payment_id }}
                                    @elseif($tx->order_id) O#{{ $tx->order_id }}
                                    @else —
                                    @endif
                                </td>
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
