<x-admin-layout>

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/20 text-green-500 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 mb-6">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 mb-6">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-[#1E1B4B] tracking-tight">Vendor Payouts</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] font-mono mt-0.5">
                Approved payable balances &amp; payout history
            </p>
        </div>
    </div>

    {{-- Warning banner --}}
    <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl mb-8 text-amber-700 text-xs">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
        <span>Only <strong>approved payable balance</strong> can be paid out. Pending earnings must be approved by admin first before they become payable.</span>
    </div>

    <div class="space-y-8">

        {{-- Vendor Payable Summary --}}
        <div class="bg-white border border-[#DDD6FE] rounded-xl p-8 shadow-sm">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 bg-indigo-500/15 rounded-2xl flex items-center justify-center text-indigo-500">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-[#1E1B4B]">Vendor payable summary</h2>
                    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">Approved payable balances — ready to pay</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.25em] border-b border-[#DDD6FE]">
                            <th class="pb-5 px-4">Vendor</th>
                            <th class="pb-5 text-center">Pending earning</th>
                            <th class="pb-5 text-center">Approved payable</th>
                            <th class="pb-5 text-center">Total paid</th>
                            <th class="pb-5 text-center">Last payout</th>
                            <th class="pb-5 text-right px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F3F4F6]">
                        @forelse($vendors as $row)
                            <tr class="hover:bg-[#F9F8FF] transition-all">
                                <td class="py-5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-[#F8FAFF] rounded-xl flex items-center justify-center text-xs font-bold text-indigo-400 border border-[#DDD6FE] flex-shrink-0">
                                            {{ strtoupper(substr($row['vendor']->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-[#1E1B4B]">{{ $row['vendor']->name }}</p>
                                            <p class="text-[9px] text-slate-500 font-mono">#{{ $row['vendor']->portal_number }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5 text-center">
                                    @if($row['pending_earning'] > 0)
                                        <span class="px-2.5 py-1 bg-amber-500/10 text-amber-600 rounded-lg text-xs font-bold font-mono border border-amber-500/10">
                                            ₹{{ number_format($row['pending_earning'], 0) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400 font-mono">₹0</span>
                                    @endif
                                </td>
                                <td class="py-5 text-center">
                                    @if($row['approved_payable'] > 0)
                                        <span class="px-2.5 py-1 bg-red-500/10 text-red-500 rounded-lg text-sm font-bold font-mono border border-red-500/10">
                                            ₹{{ number_format($row['approved_payable'], 0) }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 bg-green-500/10 text-green-500 rounded-lg text-xs font-bold border border-green-500/10">Settled</span>
                                    @endif
                                </td>
                                <td class="py-5 text-center text-sm font-mono text-slate-600">
                                    ₹{{ number_format($row['total_paid'], 0) }}
                                </td>
                                <td class="py-5 text-center text-[10px] text-slate-500 font-mono">
                                    {{ $row['last_payout_at'] ? \Carbon\Carbon::parse($row['last_payout_at'])->format('d M Y') : '—' }}
                                </td>
                                <td class="py-5 text-right px-4">
                                    @if($row['approved_payable'] > 0)
                                        @can('create', \App\Models\VendorPayout::class)
                                        <button
                                            onclick="openPayModal({{ $row['vendor']->id }}, '{{ addslashes($row['vendor']->name) }}', {{ $row['approved_payable'] }})"
                                            class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-indigo-500 bg-indigo-500/10 hover:bg-indigo-500/20 rounded-xl border border-indigo-500/15 transition-all flex items-center gap-1.5">
                                            <i data-lucide="send" class="w-3.5 h-3.5"></i> Pay
                                        </button>
                                        @endcan
                                    @else
                                        <span class="text-[10px] text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-16 text-center text-slate-500 text-sm font-bold">No vendor accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payout History --}}
        <div class="bg-white border border-[#DDD6FE] rounded-xl p-8 shadow-sm">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 bg-green-500/15 rounded-2xl flex items-center justify-center text-green-500">
                    <i data-lucide="history" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-[#1E1B4B]">Payout history</h2>
                    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">All recorded payments</p>
                </div>
            </div>

            @if($payoutHistory->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.25em] border-b border-[#DDD6FE]">
                                <th class="pb-5 px-4">Vendor</th>
                                <th class="pb-5 text-center">Amount</th>
                                <th class="pb-5 text-center">Mode</th>
                                <th class="pb-5 text-center">Transaction ID</th>
                                <th class="pb-5 text-center">Paid by</th>
                                <th class="pb-5 text-center">Notes</th>
                                <th class="pb-5 text-right px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F3F4F6]">
                            @foreach($payoutHistory as $payout)
                                <tr class="hover:bg-[#F9F8FF] transition-all">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-[#F8FAFF] rounded-lg flex items-center justify-center text-[9px] font-bold text-indigo-400 border border-[#DDD6FE] flex-shrink-0">
                                                {{ strtoupper(substr($payout->vendor->name ?? 'V', 0, 2)) }}
                                            </div>
                                            <span class="text-sm font-bold text-[#1E1B4B]">{{ $payout->vendor->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="text-sm font-bold text-green-600 font-mono">₹{{ number_format($payout->amount, 0) }}</span>
                                    </td>
                                    <td class="py-4 text-center">
                                        @if($payout->payment_mode)
                                            <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-500 rounded text-[9px] font-bold uppercase tracking-widest border border-indigo-500/10">
                                                {{ str_replace('_', ' ', $payout->payment_mode) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-center">
                                        @if($payout->reference_id)
                                            <span class="text-xs font-mono text-indigo-500 bg-indigo-500/5 border border-indigo-500/10 px-2.5 py-1 rounded-lg">{{ $payout->reference_id }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-center text-xs text-slate-500">
                                        {{ $payout->paidBy?->name ?? '—' }}
                                    </td>
                                    <td class="py-4 text-center text-xs text-slate-500 max-w-[150px] truncate">
                                        {{ $payout->notes ?? '—' }}
                                    </td>
                                    <td class="py-4 text-right px-4">
                                        <span class="text-xs text-slate-500 font-mono">{{ $payout->paid_at->format('d M Y, H:i') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-16 text-center">
                    <div class="w-14 h-14 bg-[#F8FAFF] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-[#DDD6FE]">
                        <i data-lucide="history" class="w-7 h-7 text-slate-400"></i>
                    </div>
                    <p class="text-sm font-bold text-[#1E1B4B]">No payouts recorded yet</p>
                    <p class="text-[10px] text-slate-500 mt-1">Use the "Pay" button above to record the first payout.</p>
                </div>
            @endif
        </div>

    </div>

    {{-- Pay Vendor Modal --}}
    <div id="pay-modal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white border border-[#DDD6FE] rounded-xl w-full max-w-sm p-8 shadow-lg"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500 border border-indigo-500/20">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-[#1E1B4B] font-bold" id="pay-modal-title">Pay Vendor</h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5">Record payout</p>
                    </div>
                </div>
                <button onclick="document.getElementById('pay-modal').classList.add('hidden')"
                    class="text-slate-400 hover:text-[#1E1B4B] transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="pay-form" action="{{ route('admin.finance.payouts.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="vendor_id" id="pay-vendor-id">

                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-[9px] font-bold text-amber-700 uppercase tracking-widest mb-1">Approved Payable Balance</p>
                    <p class="text-2xl font-bold text-amber-600 font-mono" id="pay-modal-balance">₹0</p>
                    <p class="text-[9px] text-amber-600 mt-1">Only this amount can be paid out</p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Amount (₹)</label>
                    <input type="number" name="amount" id="pay-amount" required min="0.01" step="0.01"
                        class="w-full bg-[#F8FAFF] border border-[#DDD6FE] rounded-xl px-4 py-3 text-sm text-[#1E1B4B] font-mono focus:outline-none focus:border-indigo-500/50 transition-colors"
                        placeholder="e.g. 1500">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Payment Mode</label>
                    <select name="payment_mode" required
                        class="w-full bg-[#F8FAFF] border border-[#DDD6FE] rounded-xl px-4 py-3 text-sm text-[#1E1B4B] focus:outline-none focus:border-indigo-500/50 transition-colors">
                        <option value="">Select mode…</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Transaction / UTR ID <span class="text-slate-400 normal-case font-normal">(required for UPI / bank)</span></label>
                    <input type="text" name="transaction_id"
                        class="w-full bg-[#F8FAFF] border border-[#DDD6FE] rounded-xl px-4 py-3 text-sm text-[#1E1B4B] font-mono focus:outline-none focus:border-indigo-500/50 transition-colors"
                        placeholder="e.g. UTR123456789">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Paid date <span class="text-slate-400 normal-case font-normal">(defaults to today)</span></label>
                    <input type="date" name="paid_at"
                        class="w-full bg-[#F8FAFF] border border-[#DDD6FE] rounded-xl px-4 py-3 text-sm text-[#1E1B4B] focus:outline-none focus:border-indigo-500/50 transition-colors">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Notes <span class="text-slate-400 normal-case font-normal">(optional)</span></label>
                    <textarea name="notes" rows="2"
                        class="w-full bg-[#F8FAFF] border border-[#DDD6FE] rounded-xl px-4 py-3 text-sm text-[#1E1B4B] focus:outline-none focus:border-indigo-500/50 transition-colors resize-none"
                        placeholder="e.g. June settlement"></textarea>
                </div>

                <div class="pt-3 mt-2 border-t border-[#DDD6FE]">
                    <button type="submit"
                        class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl transition-all flex justify-center items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPayModal(vendorId, name, approvedBalance) {
            document.getElementById('pay-vendor-id').value = vendorId;
            document.getElementById('pay-modal-title').textContent = 'Pay ' + name;
            document.getElementById('pay-modal-balance').textContent = '₹' + Math.max(0, approvedBalance).toLocaleString('en-IN', {minimumFractionDigits: 0});
            document.getElementById('pay-amount').value = Math.max(0, approvedBalance).toFixed(0);
            document.getElementById('pay-amount').max = approvedBalance;
            document.getElementById('pay-modal').classList.remove('hidden');
        }
    </script>

</x-admin-layout>
