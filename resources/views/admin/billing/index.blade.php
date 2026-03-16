<x-admin-layout>
    @if(session('success'))
        <div
            class="bg-green-500/10 border border-green-500/20 text-green-500 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 mb-6">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Nav Header Area --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight">Financial Matrix</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] font-mono mt-0.5">Billing & Ledger
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[9px] font-bold text-slate-600 uppercase tracking-widest font-mono">Vendor Rate:
                ₹{{ number_format($payoutRate, 0) }}/order</span>
        </div>
    </div>

    <div class="space-y-8">
        {{-- Today's Performance --}}
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-600 mb-6 font-mono">Today's
                Performance
                &mdash; {{ now()->format('d M Y') }}</p>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">

                <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl space-y-3">
                    <div class="w-10 h-10 bg-green-500/15 rounded-2xl flex items-center justify-center text-green-500">
                        <i data-lucide="trending-up" class="w-5 h-5"></i>
                    </div>
                    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Revenue</p>
                    <h2 class="text-3xl font-bold text-white font-mono">₹{{ number_format($todayRevenue, 0) }}</h2>
                    <p class="text-[10px] text-slate-600 font-mono">{{ $todayOrders->count() }} orders today</p>
                </div>

                <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl space-y-3">
                    <div class="w-10 h-10 bg-red-500/15 rounded-2xl flex items-center justify-center text-red-500">
                        <i data-lucide="arrow-down-circle" class="w-5 h-5"></i>
                    </div>
                    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Vendor Payouts</p>
                    <h2 class="text-3xl font-bold text-white font-mono">₹{{ number_format($todayPayouts, 0) }}</h2>
                    <p class="text-[10px] text-slate-600 font-mono">@
                        ₹{{ number_format($payoutRate, 0) }}/order
                    </p>
                </div>

                <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl space-y-3">
                    <div class="w-10 h-10 bg-slate-500/10 rounded-2xl flex items-center justify-center text-slate-400">
                        <i data-lucide="settings-2" class="w-5 h-5"></i>
                    </div>
                    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Operational Costs</p>
                    <h2 class="text-3xl font-bold text-white font-mono">₹0</h2>
                    <p class="text-[10px] text-slate-600 font-mono">None configured</p>
                </div>

                <div
                    class="bg-[#0d0d0f] border {{ $todayProfit >= 0 ? 'border-green-500/20' : 'border-red-500/20' }} rounded-2xl p-8 shadow-2xl space-y-3">
                    <div
                        class="w-10 h-10 {{ $todayProfit >= 0 ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }} rounded-2xl flex items-center justify-center">
                        <i data-lucide="{{ $todayProfit >= 0 ? 'check-circle' : 'alert-circle' }}" class="w-5 h-5"></i>
                    </div>
                    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Net Profit</p>
                    <h2
                        class="text-3xl font-bold {{ $todayProfit >= 0 ? 'text-green-400' : 'text-red-400' }} font-mono">
                        ₹{{ number_format($todayProfit, 0) }}</h2>
                    <p class="text-[10px] text-slate-600 font-mono">After all expenses</p>
                </div>
            </div>
        </div>

        {{-- Today's Client Breakdown --}}
        @if($todayClientBreakdown->count() > 0)
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 bg-purple-500/15 rounded-2xl flex items-center justify-center text-purple-500">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Today's Client Breakdown</h2>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">Revenue per client
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                                <th class="pb-6 px-4">Client</th>
                                <th class="pb-6 text-center">Orders Delivered</th>
                                <th class="pb-6 text-center">Rate / File</th>
                                <th class="pb-6 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            @foreach($todayClientBreakdown as $entry)
                                <tr class="hover:bg-white/[0.01] transition-all">
                                    <td class="py-6 px-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 bg-white/5 rounded-xl flex items-center justify-center text-slate-400 border border-white/5">
                                                <i data-lucide="building" class="w-4 h-4"></i>
                                            </div>
                                            <span class="text-sm font-bold text-slate-300">{{ $entry['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-6 text-center">
                                        <span
                                            class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-lg text-xs font-bold font-mono border border-amber-500/10">{{ $entry['orders'] }}</span>
                                    </td>
                                    <td class="py-6 text-center text-sm text-slate-400 font-mono">
                                        ₹{{ number_format($entry['revenue'] / max(1, $entry['orders']), 0) }}
                                    </td>
                                    <td class="py-6 text-right text-sm font-bold text-green-400 font-mono">
                                        ₹{{ number_format($entry['revenue'], 0) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Ledger History --}}
        <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-500/15 rounded-2xl flex items-center justify-center text-indigo-500">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Ledger History</h2>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">Past daily
                            snapshots</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <form action="/admin/billing/close" method="POST" class="inline">
                        @csrf
                    </form>
                    <p class="text-[9px] text-slate-600 font-bold mt-2">Run <code
                            class="bg-white/5 px-1.5 py-0.5 rounded font-mono text-indigo-400 border border-white/10">php artisan app:close-day</code>
                        to save today's snapshot.</p>
                </div>
            </div>

            @if($ledgers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                                <th class="pb-6 px-4">Date</th>
                                <th class="pb-6 text-center">Orders</th>
                                <th class="pb-6 text-center">Revenue</th>
                                <th class="pb-6 text-center">Payouts</th>
                                <th class="pb-6 text-center">Net Profit</th>
                                <th class="pb-6 text-right px-4">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            @foreach($ledgers as $ledger)
                                <tr class="hover:bg-white/[0.01] transition-all group">
                                    <td class="py-6 px-4">
                                        <span
                                            class="text-sm font-bold text-slate-300 font-mono">{{ $ledger->date->format('d M Y') }}</span>
                                    </td>
                                    <td class="py-6 text-center">
                                        <span
                                            class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-lg text-xs font-bold font-mono border border-amber-500/10">{{ $ledger->total_orders }}</span>
                                    </td>
                                    <td class="py-6 text-center text-sm font-bold text-green-500 font-mono">
                                        ₹{{ number_format($ledger->total_revenue, 0) }}</td>
                                    <td class="py-6 text-center text-sm text-red-500 font-mono">
                                        ₹{{ number_format($ledger->vendor_payouts, 0) }}</td>
                                    <td class="py-6 text-center">
                                        <span
                                            class="text-sm font-bold {{ $ledger->net_profit >= 0 ? 'text-green-500' : 'text-red-500' }} font-mono">
                                            ₹{{ number_format($ledger->net_profit, 0) }}
                                        </span>
                                    </td>
                                    <td class="py-6 text-right px-4">
                                        <a href="{{ route('admin.billing.show', $ledger) }}"
                                            class="px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-400 text-[10px] font-bold uppercase tracking-widest rounded-xl transition-all border border-white/5">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-8">
                    {{ $ledgers->links() }}
                </div>
            @else
                <div class="py-20 text-center">
                    <div
                        class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/5">
                        <i data-lucide="book-open" class="w-8 h-8 text-slate-700"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-300">No ledger entries yet</p>
                    <p class="text-[10px] text-slate-600 mt-1 font-bold">Run <code
                            class="bg-white/5 px-1.5 py-0.5 rounded font-mono text-indigo-400 border border-white/10">php artisan app:close-day</code>
                        to create the first snapshot.</p>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>