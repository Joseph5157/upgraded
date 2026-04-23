<x-vendor-layout title="My Earnings">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-lg font-bold text-white">Payout Summary</h2>
            <p class="text-xs text-slate-500 mt-0.5">Your completed work and payment history</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-[#0f0f14] border border-white/[0.06] rounded-2xl p-5">
            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-1">Total Orders</p>
            <p class="text-3xl font-extrabold text-white font-mono">{{ $totalOrders }}</p>
            <p class="text-[10px] text-slate-500 mt-1">All time completed</p>
        </div>
        <div class="bg-[#0f0f14] border border-white/[0.06] rounded-2xl p-5">
            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-1">Total Earned</p>
            <p class="text-3xl font-extrabold text-emerald-400 font-mono">₹{{ number_format($totalEarned, 0) }}</p>
            <p class="text-[10px] text-slate-500 mt-1">₹{{ number_format($totalPaid, 0) }} already paid</p>
        </div>
        <div class="bg-[#0f0f14] border border-white/[0.06] rounded-2xl p-5">
                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 mb-1">Awaiting release</p>
            <p class="text-3xl font-extrabold font-mono {{ $pendingPayout > 0 ? 'text-amber-400' : 'text-slate-600' }}">
                ₹{{ number_format($pendingPayout, 0) }}
            </p>
            <p class="text-[10px] text-slate-500 mt-1">Waiting for admin release</p>
        </div>
    </div>

    {{-- Statement Table --}}
    <div class="bg-[#0f0f14] border border-white/[0.06] rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[0.06] flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="text-sm font-bold text-white">Payout history</h3>
        </div>

        @if($statement->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-12 h-12 bg-white/[0.03] rounded-2xl flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-500">No transactions yet</p>
                <p class="text-xs text-slate-600 mt-1">Your earnings will appear here after your first delivery</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/[0.04]">
                            <th class="text-left px-5 py-3 text-[9px] font-black uppercase tracking-widest text-slate-600">Date</th>
                            <th class="text-left px-5 py-3 text-[9px] font-black uppercase tracking-widest text-slate-600">Description</th>
                            <th class="text-right px-5 py-3 text-[9px] font-black uppercase tracking-widest text-slate-600">Earned</th>
                            <th class="text-right px-5 py-3 text-[9px] font-black uppercase tracking-widest text-slate-600">Paid Out</th>
                            <th class="text-right px-5 py-3 text-[9px] font-black uppercase tracking-widest text-slate-600">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        @foreach($statement as $row)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-5 py-3.5 text-xs text-slate-400 font-mono whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        @if($row['type'] === 'earned')
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                                            <span class="text-xs text-slate-300">{{ $row['description'] }}</span>
                                        @else
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                                            <span class="text-xs text-slate-300">{{ $row['description'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($row['credit'])
                                        <span class="text-xs font-bold text-emerald-400 font-mono">+₹{{ number_format($row['credit'], 0) }}</span>
                                    @else
                                        <span class="text-xs text-slate-700">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($row['debit'])
                                        <span class="text-xs font-bold text-indigo-400 font-mono">-₹{{ number_format($row['debit'], 0) }}</span>
                                    @else
                                        <span class="text-xs text-slate-700">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="text-xs font-bold font-mono {{ $row['balance'] > 0 ? 'text-amber-400' : 'text-slate-500' }}">
                                        ₹{{ number_format($row['balance'], 0) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-vendor-layout>
