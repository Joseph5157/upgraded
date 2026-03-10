<x-admin-layout>
    <div class="min-h-screen bg-[#111113] text-slate-400">

        {{-- Header --}}
        <div class="px-10 py-8 border-b border-white/[0.04] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">Ledger History</h1>
                <p class="text-[10px] text-slate-600 uppercase tracking-[0.25em] mt-0.5">Daily P&L Snapshots</p>
            </div>
            <p class="text-[9px] font-mono text-slate-600">Run <code
                    class="bg-white/5 px-1.5 py-0.5 rounded">php artisan app:close-day</code> to save today</p>
        </div>

        <main class="px-10 py-8 space-y-8">

            {{-- Today's Live Snapshot --}}
            <div>
                <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-600 mb-5">Live Snapshot —
                    {{ now()->format('d M Y') }}</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-[#0c0c0e] border border-white/[0.04] rounded-2xl p-6 space-y-2">
                        <div class="w-8 h-8 bg-green-500/10 rounded-xl flex items-center justify-center text-green-500">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Revenue</p>
                        <h2 class="text-2xl font-bold text-white">₹{{ number_format($todayRevenue, 0) }}</h2>
                        <p class="text-[10px] text-slate-600">{{ $todayOrders->count() }} orders</p>
                    </div>

                    <div class="bg-[#0c0c0e] border border-white/[0.04] rounded-2xl p-6 space-y-2">
                        <div class="w-8 h-8 bg-red-500/10 rounded-xl flex items-center justify-center text-red-400">
                            <i data-lucide="arrow-down-circle" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Vendor Payouts</p>
                        <h2 class="text-2xl font-bold text-white">₹{{ number_format($todayPayouts, 0) }}</h2>
                        <p class="text-[10px] text-slate-600">@
                            ₹{{ number_format(config('services.portal.vendor_payout_per_order'), 0) }}/order
                        </p>
                    </div>

                    <div class="bg-[#0c0c0e] border border-white/[0.04] rounded-2xl p-6 space-y-2">
                        <div class="w-8 h-8 bg-slate-500/10 rounded-xl flex items-center justify-center text-slate-500">
                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Op. Costs</p>
                        <h2 class="text-2xl font-bold text-white">₹0</h2>
                        <p class="text-[10px] text-slate-600">None configured</p>
                    </div>

                    <div
                        class="bg-[#0c0c0e] border {{ $todayProfit >= 0 ? 'border-green-500/20' : 'border-red-500/20' }} rounded-2xl p-6 space-y-2">
                        <div
                            class="w-8 h-8 {{ $todayProfit >= 0 ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' }} rounded-xl flex items-center justify-center">
                            <i data-lucide="{{ $todayProfit >= 0 ? 'check-circle' : 'alert-circle' }}"
                                class="w-4 h-4"></i>
                        </div>
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Net Profit</p>
                        <h2 class="text-2xl font-bold {{ $todayProfit >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            ₹{{ number_format($todayProfit, 0) }}</h2>
                        <p class="text-[10px] text-slate-600">After all expenses</p>
                    </div>
                </div>
            </div>

            {{-- Ledger Table --}}
            <div class="bg-[#0c0c0e] border border-white/[0.04] rounded-3xl overflow-hidden">
                <div class="px-8 py-6 border-b border-white/[0.04] flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white">Historical Ledger</h2>
                        <p class="text-[9px] text-slate-600 uppercase tracking-widest">Daily closed snapshots</p>
                    </div>
                </div>

                @if($ledgers->count() > 0)
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-white/[0.04]">
                                <th class="py-5 px-8">Date</th>
                                <th class="py-5 text-center">Orders</th>
                                <th class="py-5 text-center">Revenue</th>
                                <th class="py-5 text-center">Payouts</th>
                                <th class="py-5 text-center">Net Profit</th>
                                <th class="py-5 text-right px-8">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.03]">
                            @foreach($ledgers as $ledger)
                                <tr class="hover:bg-white/[0.01] transition-all">
                                    <td class="py-5 px-8 text-sm font-bold text-slate-300">{{ $ledger->date->format('d M Y') }}
                                    </td>
                                    <td class="py-5 text-center">
                                        <span
                                            class="px-2.5 py-1 bg-amber-500/10 text-amber-400 rounded-lg text-xs font-bold border border-amber-500/10">{{ $ledger->total_orders }}</span>
                                    </td>
                                    <td class="py-5 text-center text-sm font-bold text-green-400">
                                        ₹{{ number_format($ledger->total_revenue, 0) }}</td>
                                    <td class="py-5 text-center text-sm text-red-400">
                                        ₹{{ number_format($ledger->vendor_payouts, 0) }}</td>
                                    <td class="py-5 text-center">
                                        <span
                                            class="text-sm font-bold {{ $ledger->net_profit >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                            {{ $ledger->net_profit >= 0 ? '+' : '' }}₹{{ number_format($ledger->net_profit, 0) }}
                                        </span>
                                    </td>
                                    <td class="py-5 px-8 text-right">
                                        <a href="{{ route('admin.billing.show', $ledger) }}"
                                            class="px-3 py-1.5 bg-white/5 hover:bg-indigo-500/10 hover:text-indigo-400 text-slate-500 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-white/5 transition-all">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-8 py-5 border-t border-white/[0.04]">
                        {{ $ledgers->links() }}
                    </div>
                @else
                    <div class="py-20 text-center">
                        <div
                            class="w-14 h-14 bg-white/[0.03] rounded-full flex items-center justify-center mx-auto mb-4 border border-white/[0.04]">
                            <i data-lucide="book-open" class="w-7 h-7 text-slate-500"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-600">No ledger entries yet</p>
                        <p class="text-[10px] text-slate-500 mt-1">Run <code
                                class="bg-white/5 px-1.5 py-0.5 rounded font-mono">php artisan app:close-day</code> to
                            create the first snapshot.</p>
                    </div>
                @endif
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</x-admin-layout>