<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Client Payments</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                Record money received &amp; add credits to client accounts
            </p>
        </div>
        <button onclick="document.getElementById('add-payment-modal').classList.remove('hidden')"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-indigo-600/20 transition-all">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Add Payment
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold mb-6">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold mb-6">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold mb-6">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ── Summary Cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6 space-y-1.5">
            <div class="w-8 h-8 bg-green-500/10 rounded-xl flex items-center justify-center text-green-500 mb-2">
                <i data-lucide="indian-rupee" class="w-4 h-4"></i>
            </div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em]">Total Received</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">₹{{ number_format($totals['amount'], 2) }}</h2>
            <p class="text-[10px] text-gray-400 dark:text-slate-500">Confirmed payments only</p>
        </div>
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6 space-y-1.5">
            <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 mb-2">
                <i data-lucide="zap" class="w-4 h-4"></i>
            </div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em]">Total Credits Added</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($totals['credits']) }}</h2>
            <p class="text-[10px] text-gray-400 dark:text-slate-500">Via confirmed payments</p>
        </div>
    </div>

    {{-- ── Payments Table ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                <i data-lucide="list" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Payment history</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">All recorded client payments</p>
            </div>
        </div>

        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                            <th class="px-6 py-4">Client</th>
                            <th class="px-4 py-4 text-right">Amount</th>
                            <th class="px-4 py-4 text-center">Credits</th>
                            <th class="px-4 py-4 text-center">Rate / Credit</th>
                            <th class="px-4 py-4 text-center">Mode</th>
                            <th class="px-4 py-4 text-center">Date</th>
                            <th class="px-4 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Ref / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($payment->client->name ?? '?', 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $payment->client->name ?? '—' }}</p>
                                            @if($payment->createdBy)
                                                <p class="text-[9px] text-gray-400 dark:text-slate-500 font-mono">by {{ $payment->createdBy->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">₹{{ number_format($payment->amount_received, 2) }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/10 rounded-lg text-xs font-bold font-mono">
                                        +{{ $payment->credits_added }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs font-mono text-gray-500 dark:text-slate-400">₹{{ number_format($payment->rate_per_credit, 2) }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @php
                                        $modeLabels = [
                                            'upi'           => ['label' => 'UPI',           'color' => 'text-violet-400 bg-violet-500/10 border-violet-500/10'],
                                            'bank_transfer' => ['label' => 'Bank',          'color' => 'text-blue-400 bg-blue-500/10 border-blue-500/10'],
                                            'cash'          => ['label' => 'Cash',          'color' => 'text-green-400 bg-green-500/10 border-green-500/10'],
                                            'razorpay'      => ['label' => 'Razorpay',      'color' => 'text-amber-400 bg-amber-500/10 border-amber-500/10'],
                                        ];
                                        $mode = $modeLabels[$payment->payment_mode] ?? ['label' => $payment->payment_mode, 'color' => 'text-gray-400 bg-gray-500/10 border-gray-500/10'];
                                    @endphp
                                    <span class="px-2 py-0.5 {{ $mode['color'] }} border rounded-lg text-[9px] font-bold uppercase tracking-wider">{{ $mode['label'] }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs font-mono text-gray-500 dark:text-slate-400">{{ $payment->received_at->format('d M Y') }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($payment->status === 'confirmed')
                                        <span class="px-2 py-0.5 bg-green-500/10 text-green-400 border border-green-500/10 rounded-lg text-[9px] font-bold uppercase">Confirmed</span>
                                    @elseif($payment->status === 'voided')
                                        <span class="px-2 py-0.5 bg-red-500/10 text-red-400 border border-red-500/10 rounded-lg text-[9px] font-bold uppercase">Voided</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-500/10 text-gray-400 border border-gray-500/10 rounded-lg text-[9px] font-bold uppercase">{{ $payment->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($payment->transaction_id)
                                            <span class="text-[9px] font-mono text-gray-400 dark:text-slate-500" title="{{ $payment->transaction_id }}">
                                                {{ Str::limit($payment->transaction_id, 10) }}
                                            </span>
                                        @endif
                                        <a href="{{ route('admin.finance.client-payments.show', $payment) }}"
                                            class="px-2.5 py-1 bg-white/5 dark:bg-white/[0.04] hover:bg-indigo-500/10 hover:text-indigo-400 text-gray-400 dark:text-slate-500 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-gray-200 dark:border-white/[0.05] transition-all">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.04]">
                {{ $payments->links() }}
            </div>
        @else
            <div class="py-20 text-center">
                <div class="w-14 h-14 bg-white/[0.03] dark:bg-white/[0.02] rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-white/[0.04]">
                    <i data-lucide="indian-rupee" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                </div>
                <p class="text-sm font-bold text-gray-400 dark:text-slate-400">No payments recorded yet</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">Use "Add Payment" to record the first client payment.</p>
            </div>
        @endif
    </div>

    {{-- ── Add Payment Modal ────────────────────────────────────────────────── --}}
    <div id="add-payment-modal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-md p-8 shadow-2xl" onclick="event.stopPropagation()">

            {{-- Modal header --}}
            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i data-lucide="indian-rupee" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">Record Client Payment</h3>
                        <p class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Add money received &amp; credit client account</p>
                    </div>
                </div>
                <button onclick="document.getElementById('add-payment-modal').classList.add('hidden')"
                    class="text-gray-400 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.finance.client-payments.store') }}" class="space-y-4">
                @csrf

                {{-- Client --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Client</label>
                    <select name="client_id" required
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                        <option value="">Select client…</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }} ({{ $client->credit_balance }} credits)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Amount + Credits in two columns --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Amount Received (₹)</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400 dark:text-slate-500">₹</span>
                            <input type="number" name="amount_received" value="{{ old('amount_received') }}"
                                min="0.01" step="0.01" max="9999999.99" required
                                class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl pl-8 pr-3 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Credits Added</label>
                        <input type="number" name="credits_added" value="{{ old('credits_added') }}"
                            min="1" step="1" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                </div>

                {{-- Payment mode --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Payment Mode</label>
                    <select name="payment_mode" required
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                        <option value="">Select mode…</option>
                        <option value="upi"           {{ old('payment_mode') === 'upi'           ? 'selected' : '' }}>UPI</option>
                        <option value="bank_transfer" {{ old('payment_mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cash"          {{ old('payment_mode') === 'cash'          ? 'selected' : '' }}>Cash</option>
                        <option value="razorpay"      {{ old('payment_mode') === 'razorpay'      ? 'selected' : '' }}>Razorpay</option>
                    </select>
                </div>

                {{-- Transaction ID + Date --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Transaction ID <span class="text-gray-300 dark:text-slate-600 normal-case font-normal">(optional)</span></label>
                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}"
                            maxlength="255" placeholder="UPI ref / UTR…"
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Received Date</label>
                        <input type="date" name="received_at" value="{{ old('received_at', today()->toDateString()) }}"
                            required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Notes <span class="text-gray-300 dark:text-slate-600 normal-case font-normal">(optional)</span></label>
                    <textarea name="notes" rows="2" maxlength="1000"
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors resize-none">{{ old('notes') }}</textarea>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Record Payment &amp; Add Credits
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        {{-- Re-open modal if there are validation errors --}}
        @if($errors->any())
            document.getElementById('add-payment-modal').classList.remove('hidden');
        @endif
    </script>

</x-admin-layout>
