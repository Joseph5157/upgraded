<x-admin-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-[#1E1B4B] tracking-tight">Vendor Earnings — Pending Approval</h1>
            <p class="text-[10px] text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Delivered orders awaiting earning approval</p>
        </div>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold mb-6">
            <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold mb-6">
            <i data-lucide="alert-circle" class="w-4 h-4"></i> {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-[#DDD6FE] rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-[#DDD6FE]">
                        <th class="pb-4 px-6 pt-5">Vendor</th>
                        <th class="pb-4 px-4 pt-5">Client</th>
                        <th class="pb-4 px-4 pt-5">Order</th>
                        <th class="pb-4 px-4 pt-5">Files</th>
                        <th class="pb-4 px-4 pt-5">Rate / File</th>
                        <th class="pb-4 px-4 pt-5">Amount</th>
                        <th class="pb-4 px-4 pt-5">Delivered</th>
                        <th class="pb-4 px-6 pt-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF2FF]">
                    @forelse($pendingOrders as $order)
                        @php
                            $earningTx = $order->vendorEarningTransactions->first();
                        @endphp
                        <tr class="hover:bg-[#F5F3FF]/40 transition-all">
                            <td class="px-6 py-4">
                                <p class="text-xs font-bold text-[#1E1B4B]">{{ $order->vendor?->name ?? '—' }}</p>
                                <p class="text-[9px] text-slate-500 font-mono">{{ $order->vendor?->email ?? '' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-xs text-slate-700">{{ $order->client?->name ?? 'Deleted client' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-[10px] font-mono text-slate-400">#{{ $order->id }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-xs font-semibold text-slate-700">{{ $earningTx?->files_count ?? $order->files_count }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-xs text-slate-600 font-mono">₹{{ number_format((float) ($earningTx?->rate_per_file ?? $order->vendor_rate_per_file), 2) }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-sm font-bold text-indigo-600 font-mono">₹{{ number_format((float) ($earningTx?->amount_delta ?? $order->vendor_amount), 2) }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-[10px] text-slate-500 font-mono">
                                    {{ $order->delivered_at?->diffForHumans() ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.finance.vendor-earnings.approve', $order) }}">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500/20 text-green-600 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-green-500/20 transition-all">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.finance.vendor-earnings.reject', $order) }}">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Reject vendor earning for order #{{ $order->id }}? This will reverse ₹{{ number_format((float) ($earningTx?->amount_delta ?? 0), 2) }} from their pending balance.')"
                                            class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-14 text-center text-xs text-slate-500">
                                No vendor earnings pending approval.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-admin-layout>
