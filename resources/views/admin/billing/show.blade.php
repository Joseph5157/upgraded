<x-admin-layout>
    {{-- Nav Header Area --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.billing.index') }}"
            class="w-10 h-10 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center transition-all border border-white/5">
            <i data-lucide="arrow-left" class="w-5 h-5 text-slate-400"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight uppercase">Ledger detail</h1>
            <p class="text-[10px] text-slate-500 font-black uppercase tracking-[0.3em] font-mono mt-0.5">
                {{ $ledger->date->format('d M Y') }}
            </p>
        </div>
    </div>

    <div class="space-y-8">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl space-y-2">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Total Orders</p>
                <h2 class="text-3xl font-bold text-white font-mono">{{ $ledger->total_orders }}</h2>
            </div>
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl space-y-2">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Revenue</p>
                <h2 class="text-3xl font-bold text-green-500 font-mono">₹{{ number_format($ledger->total_revenue, 0) }}
                </h2>
            </div>
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl space-y-2">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Vendor Payouts</p>
                <h2 class="text-3xl font-bold text-red-500 font-mono">₹{{ number_format($ledger->vendor_payouts, 0) }}
                </h2>
            </div>
            <div
                class="bg-[#0d0d0f] border {{ $ledger->net_profit >= 0 ? 'border-green-500/20' : 'border-red-500/20' }} rounded-2xl p-8 shadow-2xl space-y-2">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Net Profit</p>
                <h2
                    class="text-3xl font-bold {{ $ledger->net_profit >= 0 ? 'text-green-500' : 'text-red-500' }} font-mono">
                    ₹{{ number_format($ledger->net_profit, 0) }}</h2>
            </div>
        </div>

        {{-- Client Breakdown --}}
        @if($ledger->client_breakdown && count($ledger->client_breakdown) > 0)
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500">
                        <i data-lucide="building" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Client Breakdown</h2>
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
                                <th class="pb-6 text-center">Orders</th>
                                <th class="pb-6 text-center">Price/File</th>
                                <th class="pb-6 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            @foreach($ledger->client_breakdown as $entry)
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
                                        ₹{{ number_format($entry['price_per_file'] ?? 0, 0) }}</td>
                                    <td class="py-6 text-right text-sm font-bold text-green-500 font-mono">
                                        ₹{{ number_format($entry['revenue'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Vendor Breakdown --}}
        @if($ledger->vendor_breakdown && count($ledger->vendor_breakdown) > 0)
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-500">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Vendor Breakdown</h2>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">Payouts per vendor
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                                <th class="pb-6 px-4">Vendor</th>
                                <th class="pb-6 text-center">Orders Completed</th>
                                <th class="pb-6 text-right">Payout</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            @foreach($ledger->vendor_breakdown as $entry)
                                <tr class="hover:bg-white/[0.01] transition-all">
                                    <td class="py-6 px-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 bg-white/5 rounded-xl flex items-center justify-center text-slate-400 border border-white/5">
                                                <i data-lucide="user" class="w-4 h-4"></i>
                                            </div>
                                            <span class="text-sm font-bold text-slate-300">{{ $entry['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-6 text-center">
                                        <span
                                            class="px-3 py-1 bg-blue-500/10 text-blue-500 rounded-lg text-xs font-bold font-mono border border-blue-500/10">{{ $entry['orders'] }}</span>
                                    </td>
                                    <td class="py-6 text-right text-sm font-bold text-red-500 font-mono">
                                        ₹{{ number_format($entry['payout'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
