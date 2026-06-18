<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Business Expenses</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                Track non-vendor outgoings · salary, software, hosting, fees
            </p>
        </div>
        <button onclick="document.getElementById('add-expense-modal').classList.remove('hidden')"
            class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-[0.25em] rounded-xl border border-red-500/20 transition-all">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Add Expense
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
            <div class="w-8 h-8 bg-red-500/10 rounded-xl flex items-center justify-center text-red-400 mb-2">
                <i data-lucide="trending-down" class="w-4 h-4"></i>
            </div>
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em]">Total Expenses</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">₹{{ number_format($total, 2) }}</h2>
            <p class="text-[10px] text-gray-400 dark:text-slate-500">All recorded business expenses</p>
        </div>
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mb-3">By Category</p>
            @php $categories = \App\Models\BusinessExpense::categories(); @endphp
            @if(count($byCategory))
                <div class="space-y-1.5">
                    @foreach($categories as $key => $label)
                        @if(isset($byCategory[$key]) && $byCategory[$key] > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-gray-500 dark:text-slate-400">{{ $label }}</span>
                                <span class="text-[10px] font-mono font-bold text-gray-700 dark:text-slate-300">₹{{ number_format($byCategory[$key], 2) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-[10px] text-gray-400 dark:text-slate-500">No expenses yet.</p>
            @endif
        </div>
    </div>

    {{-- ── Expenses Table ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.04] flex items-center gap-3">
            <div class="w-8 h-8 bg-red-500/10 rounded-xl flex items-center justify-center text-red-400">
                <i data-lucide="list" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Expense history</h2>
                <p class="text-[9px] text-gray-400 dark:text-slate-500 uppercase tracking-widest">All recorded business expenses</p>
            </div>
        </div>

        @if($expenses->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.2em] border-b border-gray-100 dark:border-white/[0.04]">
                            <th class="px-6 py-4">Category</th>
                            <th class="px-4 py-4 text-right">Amount</th>
                            <th class="px-4 py-4 text-center">Mode</th>
                            <th class="px-4 py-4 text-center">Date</th>
                            <th class="px-4 py-4 text-center">Recorded by</th>
                            <th class="px-6 py-4 text-right">Ref / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($expenses as $expense)
                            @php
                                $categoryLabel = \App\Models\BusinessExpense::categories()[$expense->category] ?? $expense->category;
                                $categoryColors = [
                                    'staff_salary'     => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/10',
                                    'software'         => 'text-blue-400 bg-blue-500/10 border-blue-500/10',
                                    'razorpay_charges' => 'text-amber-400 bg-amber-500/10 border-amber-500/10',
                                    'hosting'          => 'text-violet-400 bg-violet-500/10 border-violet-500/10',
                                    'internet'         => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/10',
                                    'domain'           => 'text-teal-400 bg-teal-500/10 border-teal-500/10',
                                    'office'           => 'text-orange-400 bg-orange-500/10 border-orange-500/10',
                                    'refund_loss'      => 'text-red-400 bg-red-500/10 border-red-500/10',
                                    'other'            => 'text-gray-400 bg-gray-500/10 border-gray-500/10',
                                ];
                                $catColor = $categoryColors[$expense->category] ?? 'text-gray-400 bg-gray-500/10 border-gray-500/10';
                                $modeLabels = [
                                    'upi'           => ['label' => 'UPI',          'color' => 'text-violet-400 bg-violet-500/10 border-violet-500/10'],
                                    'bank_transfer' => ['label' => 'Bank',         'color' => 'text-blue-400 bg-blue-500/10 border-blue-500/10'],
                                    'cash'          => ['label' => 'Cash',         'color' => 'text-green-400 bg-green-500/10 border-green-500/10'],
                                    'card'          => ['label' => 'Card',         'color' => 'text-pink-400 bg-pink-500/10 border-pink-500/10'],
                                    'auto_deducted' => ['label' => 'Auto',         'color' => 'text-gray-400 bg-gray-500/10 border-gray-500/10'],
                                ];
                                $mode = $expense->payment_mode ? ($modeLabels[$expense->payment_mode] ?? ['label' => $expense->payment_mode, 'color' => 'text-gray-400 bg-gray-500/10 border-gray-500/10']) : null;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 {{ $catColor }} border rounded-lg text-[9px] font-bold uppercase tracking-wider">{{ $categoryLabel }}</span>
                                    @if($expense->notes)
                                        <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5 truncate max-w-[160px]">{{ $expense->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-bold font-mono text-red-500">−₹{{ number_format($expense->amount, 2) }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($mode)
                                        <span class="px-2 py-0.5 {{ $mode['color'] }} border rounded-lg text-[9px] font-bold uppercase tracking-wider">{{ $mode['label'] }}</span>
                                    @else
                                        <span class="text-[9px] text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs font-mono text-gray-500 dark:text-slate-400">{{ $expense->expense_date->format('d M Y') }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ $expense->createdBy?->name ?? '—' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($expense->reference_id)
                                            <span class="text-[9px] font-mono text-gray-400 dark:text-slate-500" title="{{ $expense->reference_id }}">
                                                {{ Str::limit($expense->reference_id, 10) }}
                                            </span>
                                        @endif
                                        <a href="{{ route('admin.finance.expenses.show', $expense) }}"
                                            class="px-2.5 py-1 bg-white/5 dark:bg-white/[0.04] hover:bg-red-500/10 hover:text-red-400 text-gray-400 dark:text-slate-500 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-gray-200 dark:border-white/[0.05] transition-all">
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
                {{ $expenses->links() }}
            </div>
        @else
            <div class="py-20 text-center">
                <div class="w-14 h-14 bg-white/[0.03] dark:bg-white/[0.02] rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-white/[0.04]">
                    <i data-lucide="trending-down" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
                </div>
                <p class="text-sm font-bold text-gray-400 dark:text-slate-400">No expenses recorded yet</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">Use "Add Expense" to record the first business expense.</p>
            </div>
        @endif
    </div>

    {{-- ── Add Expense Modal ────────────────────────────────────────────────── --}}
    <div id="add-expense-modal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-md p-8 shadow-2xl" onclick="event.stopPropagation()">

            {{-- Modal header --}}
            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center text-red-400 border border-red-500/20">
                        <i data-lucide="trending-down" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">Record Business Expense</h3>
                        <p class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Salary, software, hosting, fees, etc.</p>
                    </div>
                </div>
                <button onclick="document.getElementById('add-expense-modal').classList.add('hidden')"
                    class="text-gray-400 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.finance.expenses.store') }}" class="space-y-4">
                @csrf

                {{-- Amount + Category --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Amount (₹)</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400 dark:text-slate-500">₹</span>
                            <input type="number" name="amount" value="{{ old('amount') }}"
                                min="0.01" step="0.01" max="9999999.99" required
                                class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl pl-8 pr-3 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-red-500/50 transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Category</label>
                        <select name="category" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-red-500/50 transition-colors">
                            <option value="">Select…</option>
                            @foreach(\App\Models\BusinessExpense::categories() as $value => $label)
                                <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Payment mode --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Payment Mode <span class="text-gray-300 dark:text-slate-600 normal-case font-normal">(optional)</span></label>
                    <select name="payment_mode"
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-red-500/50 transition-colors">
                        <option value="">Select…</option>
                        <option value="upi"           {{ old('payment_mode') === 'upi'           ? 'selected' : '' }}>UPI</option>
                        <option value="bank_transfer" {{ old('payment_mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cash"          {{ old('payment_mode') === 'cash'          ? 'selected' : '' }}>Cash</option>
                        <option value="card"          {{ old('payment_mode') === 'card'          ? 'selected' : '' }}>Card</option>
                        <option value="auto_deducted" {{ old('payment_mode') === 'auto_deducted' ? 'selected' : '' }}>Auto-deducted</option>
                    </select>
                </div>

                {{-- Reference ID + Date --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Reference ID <span class="text-gray-300 dark:text-slate-600 normal-case font-normal">(optional)</span></label>
                        <input type="text" name="reference_id" value="{{ old('reference_id') }}"
                            maxlength="255" placeholder="UPI ref / invoice…"
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-slate-600 focus:outline-none focus:border-red-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Expense Date</label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}"
                            required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-red-500/50 transition-colors">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-1.5">Notes <span class="text-gray-300 dark:text-slate-600 normal-case font-normal">(optional)</span></label>
                    <textarea name="notes" rows="2" maxlength="2000"
                        placeholder="Vendor name, invoice details, month…"
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-300 dark:placeholder-slate-600 focus:outline-none focus:border-red-500/50 transition-colors resize-none">{{ old('notes') }}</textarea>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-red-500/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Record Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        @if($errors->any())
            document.getElementById('add-expense-modal').classList.remove('hidden');
        @endif
    </script>

</x-admin-layout>
