<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Client Balances</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                Live credit balance and payment summary per client
            </p>
        </div>
        <a href="{{ route('admin.finance.client-payments.index') }}"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-indigo-600/20 transition-all">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Add Payment
        </a>
    </div>

    {{-- ── Summary totals ───────────────────────────────────────────────────── --}}
    @php
        $totalBalance   = $clients->sum('credit_balance');
        $totalReceived  = $clients->sum('total_received');
        $totalUsed      = $clients->sum('credits_used');
    @endphp
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 space-y-1">
            <div class="w-7 h-7 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-400 mb-2">
                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
            </div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Total Credits Remaining</p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($totalBalance) }}</h2>
        </div>
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 space-y-1">
            <div class="w-7 h-7 bg-green-500/10 rounded-lg flex items-center justify-center text-green-400 mb-2">
                <i data-lucide="indian-rupee" class="w-3.5 h-3.5"></i>
            </div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Total Received</p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white font-mono">₹{{ number_format($totalReceived, 2) }}</h2>
        </div>
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-5 space-y-1">
            <div class="w-7 h-7 bg-red-500/10 rounded-lg flex items-center justify-center text-red-400 mb-2">
                <i data-lucide="minus-circle" class="w-3.5 h-3.5"></i>
            </div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Total Credits Used</p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($totalUsed) }}</h2>
        </div>
    </div>

    {{-- ── Client balances table ────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                <i data-lucide="users" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Client-wise balance</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ $clients->count() }} clients</p>
            </div>
        </div>

        @if($clients->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                            <th class="px-6 py-4">Client</th>
                            <th class="px-4 py-4 text-center">Balance</th>
                            <th class="px-4 py-4 text-right">Total Received</th>
                            <th class="px-4 py-4 text-center">Credits Added</th>
                            <th class="px-4 py-4 text-center">Credits Used</th>
                            <th class="px-4 py-4 text-center">Refunded</th>
                            <th class="px-6 py-4 text-right">Last Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($clients as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($row['client']->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $row['client']->name }}</p>
                                            @if($row['client']->user)
                                                <p class="text-[9px] font-mono text-gray-400 dark:text-slate-500">#{{ $row['client']->user->portal_number }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="px-2.5 py-1 {{ $row['credit_balance'] > 0 ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/10' : 'bg-gray-100 dark:bg-white/[0.04] text-gray-400 dark:text-slate-500 border-gray-200 dark:border-white/[0.05]' }} border rounded-lg text-sm font-bold font-mono">
                                        {{ $row['credit_balance'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-mono font-bold text-gray-900 dark:text-white">
                                        {{ $row['total_received'] > 0 ? '₹'.number_format($row['total_received'], 2) : '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center text-xs font-mono text-gray-500 dark:text-slate-400">
                                    {{ $row['credits_added'] ?: '—' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($row['credits_used'] > 0)
                                        <span class="text-xs font-mono text-red-400">−{{ $row['credits_used'] }}</span>
                                    @else
                                        <span class="text-xs text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($row['credits_refunded'] > 0)
                                        <span class="text-xs font-mono text-amber-400">+{{ $row['credits_refunded'] }}</span>
                                    @else
                                        <span class="text-xs text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-xs font-mono text-gray-400 dark:text-slate-500">
                                    {{ $row['last_payment_at'] ? \Carbon\Carbon::parse($row['last_payment_at'])->format('d M Y') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-20 text-center">
                <div class="w-14 h-14 bg-white/[0.02] rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-white/[0.04]">
                    <i data-lucide="users" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                </div>
                <p class="text-sm font-bold text-gray-400 dark:text-slate-400">No clients found</p>
            </div>
        @endif
    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
