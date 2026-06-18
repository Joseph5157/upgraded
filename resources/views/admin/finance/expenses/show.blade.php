<x-admin-layout>

    <div class="mb-6">
        <a href="{{ route('admin.finance.expenses.index') }}" class="text-[10px] text-red-400 hover:underline font-bold uppercase tracking-widest flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3 h-3"></i> Back to Expenses
        </a>
    </div>

    @php
        $categoryLabel = \App\Models\BusinessExpense::categories()[$businessExpense->category] ?? $businessExpense->category;
        $categoryColors = [
            'staff_salary'     => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/15',
            'software'         => 'text-blue-400 bg-blue-500/10 border-blue-500/15',
            'razorpay_charges' => 'text-amber-400 bg-amber-500/10 border-amber-500/15',
            'hosting'          => 'text-violet-400 bg-violet-500/10 border-violet-500/15',
            'internet'         => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/15',
            'domain'           => 'text-teal-400 bg-teal-500/10 border-teal-500/15',
            'office'           => 'text-orange-400 bg-orange-500/10 border-orange-500/15',
            'refund_loss'      => 'text-red-400 bg-red-500/10 border-red-500/15',
            'other'            => 'text-gray-400 bg-gray-500/10 border-gray-500/15',
        ];
        $catColor = $categoryColors[$businessExpense->category] ?? 'text-gray-400 bg-gray-500/10 border-gray-500/15';
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-[#1E1B4B] dark:text-white tracking-tight">Expense #{{ $businessExpense->id }}</h1>
            <p class="text-[10px] text-slate-500 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Business expense record</p>
        </div>
        <div class="flex items-center gap-2">
            @if(($businessExpense->status ?? 'active') === 'voided')
                <span class="px-3 py-1.5 bg-red-500/10 text-red-400 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-red-500/15">Voided</span>
            @endif
            <span class="px-3 py-1.5 {{ $catColor }} rounded-lg text-[10px] font-bold uppercase tracking-widest border">
                {{ $categoryLabel }}
            </span>
        </div>
    </div>

    <div class="bg-white border border-[#DDD6FE] rounded-xl p-6 shadow-sm max-w-lg">
        <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.25em] mb-5">Expense details</h2>
        <dl class="space-y-3">
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Amount</dt>
                <dd class="text-sm font-bold font-mono text-red-500">−₹{{ number_format($businessExpense->amount, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Category</dt>
                <dd class="text-xs font-bold uppercase {{ $catColor }} px-2 py-0.5 rounded-lg border">{{ $categoryLabel }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Payment mode</dt>
                <dd class="text-xs font-bold uppercase text-indigo-500">{{ str_replace('_', ' ', $businessExpense->payment_mode ?? '—') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Reference ID</dt>
                <dd class="text-xs font-mono text-slate-700">{{ $businessExpense->reference_id ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Expense date</dt>
                <dd class="text-xs font-mono text-slate-700">{{ $businessExpense->expense_date->format('d M Y') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Recorded by</dt>
                <dd class="text-xs text-slate-700">{{ $businessExpense->createdBy?->name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-xs text-slate-500">Recorded at</dt>
                <dd class="text-xs font-mono text-slate-500">{{ $businessExpense->created_at->format('d M Y, H:i') }}</dd>
            </div>
            @if($businessExpense->notes)
                <div class="pt-2 border-t border-[#DDD6FE]">
                    <dt class="text-xs text-slate-500 mb-1">Notes</dt>
                    <dd class="text-xs text-slate-600">{{ $businessExpense->notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Void details --}}
    @if(($businessExpense->status ?? 'active') === 'voided' && $businessExpense->voided_at)
        <div class="bg-red-500/5 border border-red-500/15 rounded-xl p-6 mt-6 max-w-lg">
            <h2 class="text-sm font-bold text-red-400 mb-3">Voided</h2>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Voided at</dt>
                    <dd class="font-mono text-red-400">{{ $businessExpense->voided_at->format('d M Y, H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Voided by</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $businessExpense->voidedByUser?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 mb-1">Reason</dt>
                    <dd class="text-slate-600 dark:text-slate-300">{{ $businessExpense->void_reason }}</dd>
                </div>
            </dl>
        </div>
    @endif

    {{-- Void action --}}
    @if(($businessExpense->status ?? 'active') !== 'voided')
        <div class="bg-white dark:bg-[#0d0d10] border border-[#DDD6FE] dark:border-white/[0.05] rounded-xl p-6 mt-6 max-w-lg">
            <h2 class="text-sm font-bold text-red-400 mb-1">Void this expense</h2>
            <p class="text-[10px] text-slate-500 mb-4">This will not delete the record. It will mark it as voided and keep audit history.</p>
            <form method="POST" action="{{ route('admin.finance.expenses.void', $businessExpense) }}" onsubmit="return confirm('Are you sure you want to void this expense?');">
                @csrf
                <textarea name="void_reason" rows="2" required placeholder="Reason for voiding this expense..."
                    class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none mb-3"></textarea>
                @error('void_reason') <p class="text-[10px] text-red-400 mb-2">{{ $message }}</p> @enderror
                <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all">
                    Void Expense
                </button>
            </form>
        </div>
    @endif

    <script>lucide.createIcons();</script>

</x-admin-layout>
