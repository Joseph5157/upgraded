<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Credit Ledger</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                All client credit transactions — payments, debits, refunds, adjustments
            </p>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.finance.client-credit-transactions.index') }}"
        class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Client</label>
                <select name="client_id"
                    class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    <option value="">All clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Type</label>
                <select name="type"
                    class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    <option value="">All types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
            </div>
            <div>
                <label class="block text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
            </div>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <button type="submit"
                class="px-4 py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-500/20 transition-all">
                Apply Filters
            </button>
            @if(request()->hasAny(['client_id', 'type', 'date_from', 'date_to']))
                <a href="{{ route('admin.finance.client-credit-transactions.index') }}"
                    class="px-4 py-2 bg-gray-100 dark:bg-white/[0.04] hover:bg-gray-200 dark:hover:bg-white/[0.07] text-gray-500 dark:text-slate-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- ── Transaction Table ────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                <i data-lucide="book-open" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Credit transactions</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">
                    {{ $transactions->total() }} {{ Str::plural('entry', $transactions->total()) }}
                    @if(request()->hasAny(['client_id', 'type', 'date_from', 'date_to']))
                        <span class="text-indigo-400">(filtered)</span>
                    @endif
                </p>
            </div>
        </div>

        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                            <th class="px-6 py-4">Client</th>
                            <th class="px-4 py-4">Type</th>
                            <th class="px-4 py-4 text-center">Delta</th>
                            <th class="px-4 py-4 text-center">Balance After</th>
                            <th class="px-4 py-4 text-center">Rate</th>
                            <th class="px-4 py-4 text-center">Value</th>
                            <th class="px-4 py-4 text-center">Linked</th>
                            <th class="px-4 py-4 text-center">By</th>
                            <th class="px-6 py-4 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($transactions as $tx)
                            @php
                                $typeColors = [
                                    'opening_balance'   => 'text-blue-400 bg-blue-500/10 border-blue-500/10',
                                    'payment_credit'    => 'text-green-400 bg-green-500/10 border-green-500/10',
                                    'order_debit'       => 'text-red-400 bg-red-500/10 border-red-500/10',
                                    'refund_credit'     => 'text-amber-400 bg-amber-500/10 border-amber-500/10',
                                    'manual_adjustment' => 'text-violet-400 bg-violet-500/10 border-violet-500/10',
                                    'correction'        => 'text-gray-400 bg-gray-500/10 border-gray-500/10',
                                ];
                                $typeLabels = [
                                    'opening_balance'   => 'Opening',
                                    'payment_credit'    => 'Payment',
                                    'order_debit'       => 'Debit',
                                    'refund_credit'     => 'Refund',
                                    'manual_adjustment' => 'Adjustment',
                                    'correction'        => 'Correction',
                                ];
                                $color = $typeColors[$tx->type] ?? 'text-gray-400 bg-gray-500/10 border-gray-500/10';
                                $label = $typeLabels[$tx->type] ?? $tx->type;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($tx->client->name ?? '?', 0, 2)) }}
                                        </div>
                                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $tx->client->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 {{ $color }} border rounded-lg text-[9px] font-bold uppercase tracking-wider">{{ $label }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-bold font-mono {{ $tx->credits_delta >= 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                        {{ $tx->credits_delta >= 0 ? '+' : '' }}{{ $tx->credits_delta }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">{{ $tx->balance_after }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-mono text-gray-400 dark:text-slate-500">
                                    {{ $tx->rate_per_credit ? '₹'.number_format($tx->rate_per_credit, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-mono text-gray-400 dark:text-slate-500">
                                    {{ $tx->money_value ? '₹'.number_format($tx->money_value, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($tx->client_payment_id)
                                        <a href="{{ route('admin.finance.client-payments.show', $tx->client_payment_id) }}"
                                            class="text-[9px] font-mono text-indigo-400 hover:text-indigo-300 transition-colors">
                                            Pay #{{ $tx->client_payment_id }}
                                        </a>
                                    @elseif($tx->order_id)
                                        <span class="text-[9px] font-mono text-gray-400 dark:text-slate-500">Order #{{ $tx->order_id }}</span>
                                    @else
                                        <span class="text-[9px] text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-[10px] text-gray-400 dark:text-slate-500">
                                    {{ $tx->createdBy?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-right text-[10px] font-mono text-gray-400 dark:text-slate-500 whitespace-nowrap">
                                    {{ $tx->created_at->format('d M Y') }}
                                </td>
                            </tr>
                            @if($tx->notes)
                                <tr class="bg-gray-50/50 dark:bg-white/[0.01]">
                                    <td colspan="9" class="px-6 py-2 text-[10px] text-gray-400 dark:text-slate-500 italic">
                                        Note: {{ $tx->notes }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.04]">
                {{ $transactions->links() }}
            </div>
        @else
            <div class="py-20 text-center">
                <div class="w-14 h-14 bg-white/[0.02] rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-white/[0.04]">
                    <i data-lucide="book-open" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                </div>
                <p class="text-sm font-bold text-gray-400 dark:text-slate-400">No transactions found</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">
                    @if(request()->hasAny(['client_id', 'type', 'date_from', 'date_to']))
                        Try adjusting your filters.
                    @else
                        Transactions will appear here when payments are recorded or credits are consumed.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
