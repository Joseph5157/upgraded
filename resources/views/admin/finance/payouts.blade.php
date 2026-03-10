<x-admin-layout>

    @if(session('success'))
        <div
            class="bg-green-500/10 border border-green-500/20 text-green-500 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 mb-6">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight">Vendor Payouts</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] font-mono mt-0.5">
                Balance Ledger · Rate: ₹{{ number_format($payoutRate, 0) }}/order
            </p>
        </div>
    </div>

    <div class="space-y-8">

        {{-- Vendor Balance Table --}}
        <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-500">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Vendor Balance Sheet</h2>
                    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">Earnings vs Paid
                        Out</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                            <th class="pb-6 px-4">Vendor</th>
                            <th class="pb-6 text-center">Payout Rate</th>
                            <th class="pb-6 text-center">Delivered</th>
                            <th class="pb-6 text-center">Total Earned</th>
                            <th class="pb-6 text-center">Total Paid</th>
                            <th class="pb-6 text-center">Balance Due</th>
                            <th class="pb-6 text-right px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        @forelse($vendorData as $row)
                            <tr class="group hover:bg-white/[0.01] transition-all">
                                <td class="py-6 px-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 bg-white/5 rounded-xl flex items-center justify-center text-xs font-bold text-indigo-400 border border-white/5 flex-shrink-0">
                                            {{ strtoupper(substr($row['vendor']->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-200">{{ $row['vendor']->name }}</p>
                                            <p class="text-[9px] text-slate-600 font-mono">{{ $row['vendor']->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-6 text-center text-sm font-mono text-slate-400">
                                    ₹{{ number_format($payoutRate, 0) }}
                                </td>
                                <td class="py-6 text-center">
                                    <span
                                        class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-lg text-xs font-bold font-mono border border-amber-500/10">
                                        {{ $row['delivered'] }}
                                    </span>
                                </td>
                                <td class="py-6 text-center text-sm font-bold text-white font-mono">
                                    ₹{{ number_format($row['earned'], 0) }}
                                </td>
                                <td class="py-6 text-center text-sm font-mono text-green-500">
                                    ₹{{ number_format($row['paid'], 0) }}
                                </td>
                                <td class="py-6 text-center">
                                    @if($row['balance'] > 0)
                                        <span
                                            class="px-3 py-1 bg-red-500/10 text-red-400 rounded-lg text-sm font-bold font-mono border border-red-500/10">
                                            ₹{{ number_format($row['balance'], 0) }}
                                        </span>
                                    @elseif($row['balance'] < 0)
                                        <span
                                            class="px-3 py-1 bg-amber-500/10 text-amber-400 rounded-lg text-sm font-bold font-mono border border-amber-500/10">
                                            ₹{{ number_format($row['balance'], 0) }}
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-green-500/10 text-green-500 rounded-lg text-sm font-bold font-mono border border-green-500/10">
                                            Settled
                                        </span>
                                    @endif
                                </td>
                                <td class="py-6 text-right px-4">
                                    <button
                                        onclick="openPayModal({{ $row['vendor']->id }}, '{{ addslashes($row['vendor']->name) }}', {{ $row['balance'] }})"
                                        class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 rounded-xl border border-indigo-500/15 transition-all flex items-center gap-1.5">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i> Pay
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-slate-600 text-sm font-bold">No vendor
                                    accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payout History --}}
        <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-500">
                    <i data-lucide="history" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Payout History</h2>
                    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">All recorded
                        payments</p>
                </div>
            </div>

            @if($payoutHistory->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                                <th class="pb-6 px-4">Vendor</th>
                                <th class="pb-6 text-center">Amount</th>
                                <th class="pb-6 text-center">UPI / Reference</th>
                                <th class="pb-6 text-center">Notes</th>
                                <th class="pb-6 text-right px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            @foreach($payoutHistory as $payout)
                                <tr class="hover:bg-white/[0.01] transition-all">
                                    <td class="py-5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-7 h-7 bg-white/5 rounded-lg flex items-center justify-center text-[9px] font-bold text-indigo-400 border border-white/5 flex-shrink-0">
                                                {{ strtoupper(substr($payout->vendor->name ?? 'V', 0, 2)) }}
                                            </div>
                                            <span
                                                class="text-sm font-bold text-slate-300">{{ $payout->vendor->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-5 text-center">
                                        <span
                                            class="text-sm font-bold text-green-500 font-mono">₹{{ number_format($payout->amount, 0) }}</span>
                                    </td>
                                    <td class="py-5 text-center">
                                        @if($payout->reference_id)
                                            <span
                                                class="text-xs font-mono text-indigo-400 bg-indigo-500/5 border border-indigo-500/10 px-2.5 py-1 rounded-lg">
                                                {{ $payout->reference_id }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-500 font-bold">—</span>
                                        @endif
                                    </td>
                                    <td class="py-5 text-center text-xs text-slate-500 max-w-[200px] truncate">
                                        {{ $payout->notes ?? '—' }}
                                    </td>
                                    <td class="py-5 text-right px-4">
                                        <span
                                            class="text-xs text-slate-500 font-mono">{{ $payout->paid_at->format('d M Y, H:i') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-16 text-center">
                    <div
                        class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/5">
                        <i data-lucide="history" class="w-7 h-7 text-slate-500"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-400">No payouts recorded yet</p>
                    <p class="text-[10px] text-slate-600 mt-1 font-bold">Use the "Pay" button above to record your first
                        payout.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Pay Vendor Modal --}}
    <div id="pay-modal"
        class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#0a0a0c] border border-white/10 rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500 border border-indigo-500/20">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold" id="pay-modal-title">Pay Vendor</h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5">Record UPI Payment</p>
                    </div>
                </div>
                <button onclick="document.getElementById('pay-modal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="pay-form" action="{{ route('admin.finance.payouts.store') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="user_id" id="pay-user-id">

                <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl">
                    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-1">Balance Due</p>
                    <p class="text-2xl font-bold text-red-400 font-mono" id="pay-modal-balance">₹0</p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Amount
                        (₹)</label>
                    <input type="number" name="amount" id="pay-amount" required min="0.01" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-indigo-500/50 transition-colors"
                        placeholder="e.g. 1500">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">UPI
                        Reference / Transaction ID</label>
                    <input type="text" name="reference_id"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-indigo-500/50 transition-colors"
                        placeholder="e.g. UTR123456789">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Notes
                        (optional)</label>
                    <textarea name="notes" rows="2"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500/50 transition-colors resize-none"
                        placeholder="e.g. March week 1 settlement"></textarea>
                </div>

                <div class="pt-4 mt-4 border-t border-white/5">
                    <button type="submit"
                        class="w-full py-4 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPayModal(userId, name, balance) {
            document.getElementById('pay-user-id').value = userId;
            document.getElementById('pay-modal-title').textContent = 'Pay ' + name;
            document.getElementById('pay-modal-balance').textContent = '₹' + Math.max(0, balance).toLocaleString('en-IN');
            document.getElementById('pay-amount').value = Math.max(0, balance).toFixed(0);
            document.getElementById('pay-modal').classList.remove('hidden');
        }
    </script>

</x-admin-layout>