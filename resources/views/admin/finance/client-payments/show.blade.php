<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-[10px] text-gray-400 dark:text-slate-500 mb-1 font-mono">
                <a href="{{ route('admin.finance.client-payments.index') }}" class="hover:text-indigo-400 transition-colors">Client Payments</a>
                <span>/</span>
                <span class="text-gray-600 dark:text-slate-300">Payment #{{ $clientPayment->id }}</span>
            </div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Payment Detail</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Audit record for payment #{{ $clientPayment->id }}</p>
        </div>
        <a href="{{ route('admin.finance.client-payments.index') }}"
            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-white/[0.04] hover:bg-gray-50 dark:hover:bg-white/[0.07] text-gray-600 dark:text-slate-300 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-gray-200 dark:border-white/[0.08] transition-all">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Left column: Payment details ─────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Payment card --}}
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                        <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white">Payment information</h2>
                        <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">Recorded payment details</p>
                    </div>
                    @if($clientPayment->status === 'confirmed')
                        <span class="ml-auto px-2.5 py-1 bg-green-500/10 text-green-400 border border-green-500/10 rounded-lg text-[9px] font-bold uppercase tracking-wider">Confirmed</span>
                    @elseif($clientPayment->status === 'voided')
                        <span class="ml-auto px-2.5 py-1 bg-red-500/10 text-red-400 border border-red-500/10 rounded-lg text-[9px] font-bold uppercase tracking-wider">Voided</span>
                    @endif
                </div>
                <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @php
                        $modeLabels = [
                            'upi'           => ['label' => 'UPI',          'color' => 'text-violet-400 bg-violet-500/10 border-violet-500/10'],
                            'bank_transfer' => ['label' => 'Bank Transfer','color' => 'text-blue-400 bg-blue-500/10 border-blue-500/10'],
                            'cash'          => ['label' => 'Cash',         'color' => 'text-green-400 bg-green-500/10 border-green-500/10'],
                            'razorpay'      => ['label' => 'Razorpay',     'color' => 'text-amber-400 bg-amber-500/10 border-amber-500/10'],
                        ];
                        $mode = $modeLabels[$clientPayment->payment_mode] ?? ['label' => $clientPayment->payment_mode, 'color' => 'text-gray-400 bg-gray-500/10 border-gray-500/10'];
                    @endphp
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Payment ID</p>
                        <p class="text-sm font-mono font-bold text-gray-900 dark:text-white">#{{ $clientPayment->id }}</p>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Amount Received</p>
                        <p class="text-sm font-mono font-bold text-gray-900 dark:text-white">₹{{ number_format($clientPayment->amount_received, 2) }}</p>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Credits Added</p>
                        <p class="text-sm font-mono font-bold text-indigo-500 dark:text-indigo-400">+{{ $clientPayment->credits_added }}</p>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Rate per Credit</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white">₹{{ number_format($clientPayment->rate_per_credit, 2) }}</p>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Payment Mode</p>
                        <span class="inline-flex w-fit px-2.5 py-1 {{ $mode['color'] }} border rounded-lg text-[9px] font-bold uppercase tracking-wider">{{ $mode['label'] }}</span>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Transaction ID</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white break-all">{{ $clientPayment->transaction_id ?? '—' }}</p>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Received At</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white">{{ $clientPayment->received_at->format('d M Y') }}</p>
                    </div>
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Recorded By</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $clientPayment->createdBy?->name ?? '—' }}</p>
                    </div>
                    @if($clientPayment->notes)
                        <div class="px-6 py-3.5">
                            <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Notes</p>
                            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $clientPayment->notes }}</p>
                        </div>
                    @endif
                    <div class="grid grid-cols-2 px-6 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Record Created</p>
                        <p class="text-xs font-mono text-gray-400 dark:text-slate-500">{{ $clientPayment->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Void details (shown only when voided) --}}
            @if($clientPayment->status === 'voided' && $clientPayment->voided_at)
                <div class="bg-red-500/5 border border-red-500/15 rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-red-400 mb-3">Voided</h2>
                    <dl class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-slate-500">Voided at</dt>
                            <dd class="font-mono text-red-400">{{ $clientPayment->voided_at->format('d M Y, H:i') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-slate-500">Voided by</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $clientPayment->voidedBy?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-slate-500 mb-1">Reason</dt>
                            <dd class="text-gray-700 dark:text-slate-300">{{ $clientPayment->void_reason }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            {{-- Void action (shown only when not yet voided) --}}
            @if($clientPayment->status === 'confirmed')
                <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-red-400 mb-1">Void this payment</h2>
                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mb-4">This will not delete the record. It will create a reversal and keep audit history.</p>
                    <form method="POST" action="{{ route('admin.finance.client-payments.void', $clientPayment) }}" onsubmit="return confirm('Are you sure you want to void this payment? This will reverse the credits added.');">
                        @csrf
                        <textarea name="void_reason" rows="2" required placeholder="Reason for voiding this payment..."
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none mb-3"></textarea>
                        @error('void_reason') <p class="text-[10px] text-red-400 mb-2">{{ $message }}</p> @enderror
                        <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all">
                            Void Payment
                        </button>
                    </form>
                </div>
            @endif

            {{-- Linked credit transactions --}}
            @if($clientPayment->creditTransactions->count() > 0)
                <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-500/10 rounded-xl flex items-center justify-center text-green-400">
                            <i data-lucide="list" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Credit ledger entries</h2>
                            <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">Transactions generated by this payment</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-4 py-3 text-center">Delta</th>
                                    <th class="px-4 py-3 text-center">Balance After</th>
                                    <th class="px-4 py-3 text-right">Order</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                                @foreach($clientPayment->creditTransactions as $tx)
                                    <tr>
                                        <td class="px-6 py-3 text-xs font-mono text-gray-700 dark:text-slate-300">{{ str_replace('_', ' ', $tx->type) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-sm font-bold font-mono {{ $tx->credits_delta >= 0 ? 'text-green-500' : 'text-red-400' }}">
                                                {{ $tx->credits_delta >= 0 ? '+' : '' }}{{ $tx->credits_delta }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm font-mono text-gray-900 dark:text-white">{{ $tx->balance_after }}</td>
                                        <td class="px-4 py-3 text-right text-xs font-mono text-gray-400 dark:text-slate-500">
                                            {{ $tx->order_id ? "#order {$tx->order_id}" : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        {{-- ── Right column: Client info ──────────────────────────────────── --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr($clientPayment->client->name ?? '?', 0, 2)) }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $clientPayment->client->name ?? 'Unknown' }}</p>
                        @if($clientPayment->client?->user)
                            <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">Portal #{{ $clientPayment->client->user->portal_number }}</p>
                        @endif
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Credit Balance</span>
                        <span class="text-sm font-bold font-mono text-gray-900 dark:text-white">{{ $clientPayment->client?->credit_balance ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">Rate / File</span>
                        <span class="text-sm font-mono text-gray-500 dark:text-slate-400">₹{{ number_format($clientPayment->client?->price_per_file ?? 0, 0) }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <a href="{{ route('admin.finance.client-balances.index') }}"
                        class="flex items-center justify-center gap-2 w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-indigo-500/20 transition-all">
                        <i data-lucide="bar-chart-2" class="w-3.5 h-3.5"></i>
                        View All Balances
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>lucide.createIcons();</script>
</x-admin-layout>
