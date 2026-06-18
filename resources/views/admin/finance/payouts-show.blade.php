<x-admin-layout>

    <div class="mb-6">
        <a href="{{ route('admin.finance.payouts.index') }}" class="text-[10px] text-indigo-500 hover:underline font-bold uppercase tracking-widest flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3 h-3"></i> Back to Payouts
        </a>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-[#1E1B4B] dark:text-white tracking-tight">Payout #{{ $vendorPayout->id }}</h1>
            <p class="text-[10px] text-slate-500 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Vendor payout record</p>
        </div>
        <span class="px-3 py-1.5 {{ $vendorPayout->status === 'voided' ? 'bg-red-500/10 text-red-400 border-red-500/15' : 'bg-green-500/10 text-green-600 border-green-500/15' }} rounded-lg text-[10px] font-bold uppercase tracking-widest border">
            {{ strtoupper($vendorPayout->status) }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-white border border-[#DDD6FE] rounded-xl p-6 shadow-sm">
            <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.25em] mb-5">Payment details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Amount</dt>
                    <dd class="text-sm font-bold font-mono text-[#1E1B4B]">₹{{ number_format($vendorPayout->amount, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Payment mode</dt>
                    <dd class="text-xs font-bold uppercase text-indigo-500">{{ str_replace('_', ' ', $vendorPayout->payment_mode ?? '—') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Transaction ID</dt>
                    <dd class="text-xs font-mono text-slate-700">{{ $vendorPayout->reference_id ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Paid at</dt>
                    <dd class="text-xs font-mono text-slate-700">{{ $vendorPayout->paid_at->format('d M Y, H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Paid by</dt>
                    <dd class="text-xs text-slate-700">{{ $vendorPayout->paidBy?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Notes</dt>
                    <dd class="text-xs text-slate-500">{{ $vendorPayout->notes ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white border border-[#DDD6FE] rounded-xl p-6 shadow-sm">
            <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.25em] mb-5">Vendor</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Name</dt>
                    <dd class="text-sm font-bold text-[#1E1B4B]">{{ $vendorPayout->vendor?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Portal #</dt>
                    <dd class="text-xs font-mono text-slate-600">{{ $vendorPayout->vendor?->portal_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Pending earning</dt>
                    <dd class="text-xs font-mono text-amber-600">₹{{ number_format($vendorPayout->vendor?->pending_earning_balance ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-500">Approved payable (now)</dt>
                    <dd class="text-xs font-mono text-indigo-600 font-bold">₹{{ number_format($vendorPayout->vendor?->approved_payable_balance ?? 0, 2) }}</dd>
                </div>
            </dl>
        </div>

    </div>

    {{-- Void details --}}
    @if($vendorPayout->status === 'voided' && $vendorPayout->voided_at)
        <div class="bg-red-500/5 border border-red-500/15 rounded-xl p-6 mt-6">
            <h2 class="text-sm font-bold text-red-400 mb-3">Voided</h2>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Voided at</dt>
                    <dd class="font-mono text-red-400">{{ $vendorPayout->voided_at->format('d M Y, H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Voided by</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $vendorPayout->voidedBy?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 mb-1">Reason</dt>
                    <dd class="text-slate-600 dark:text-slate-300">{{ $vendorPayout->void_reason }}</dd>
                </div>
            </dl>
        </div>
    @endif

    {{-- Void action --}}
    @if($vendorPayout->status === 'paid')
        <div class="bg-white dark:bg-[#0d0d10] border border-[#DDD6FE] dark:border-white/[0.05] rounded-xl p-6 mt-6">
            <h2 class="text-sm font-bold text-red-400 mb-1">Void this payout</h2>
            <p class="text-[10px] text-slate-500 mb-4">This will not delete the record. It will create a reversal and keep audit history.</p>
            <form method="POST" action="{{ route('admin.finance.payouts.void', $vendorPayout) }}" onsubmit="return confirm('Are you sure you want to void this payout? The vendor balance will be restored.');">
                @csrf
                <textarea name="void_reason" rows="2" required placeholder="Reason for voiding this payout..."
                    class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none mb-3"></textarea>
                @error('void_reason') <p class="text-[10px] text-red-400 mb-2">{{ $message }}</p> @enderror
                <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all">
                    Void Payout
                </button>
            </form>
        </div>
    @endif

    @if($vendorPayout->earningTransactions->isNotEmpty())
        <div class="bg-white border border-[#DDD6FE] rounded-xl p-6 shadow-sm mt-6">
            <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.25em] mb-5">Ledger entry</h2>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[9px] font-bold uppercase tracking-widest text-slate-500 border-b border-[#DDD6FE]">
                        <th class="pb-3">Type</th>
                        <th class="pb-3">Amount delta</th>
                        <th class="pb-3">Pending after</th>
                        <th class="pb-3">Approved after</th>
                        <th class="pb-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorPayout->earningTransactions as $tx)
                        <tr>
                            <td class="py-3 text-xs font-mono font-bold text-slate-600">{{ $tx->type }}</td>
                            <td class="py-3 text-xs font-mono {{ $tx->amount_delta < 0 ? 'text-red-500' : 'text-green-600' }}">
                                {{ $tx->amount_delta >= 0 ? '+' : '' }}₹{{ number_format($tx->amount_delta, 2) }}
                            </td>
                            <td class="py-3 text-xs font-mono text-slate-600">₹{{ number_format($tx->pending_balance_after, 2) }}</td>
                            <td class="py-3 text-xs font-mono text-slate-600">₹{{ number_format($tx->approved_balance_after, 2) }}</td>
                            <td class="py-3 text-[10px] text-slate-500 font-mono">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-admin-layout>
