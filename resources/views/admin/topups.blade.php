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
    @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-6 py-4 rounded-2xl text-sm font-bold mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight">Top-up Requests</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] font-mono mt-0.5">Slot Purchases &amp; Approvals</p>
        </div>
        <a href="{{ route('admin.matrix.index') }}"
           class="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-400 text-xs font-bold uppercase tracking-widest rounded-xl border border-white/10 transition-all">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Matrix
        </a>
    </div>

    <div class="space-y-10">

        {{-- ── Pending Requests ────────────────────────────────────────────── --}}
        <div>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="text-sm font-bold text-white uppercase tracking-widest">Pending</h2>
                @if($pending->count() > 0)
                    <span class="px-2.5 py-0.5 bg-amber-500/10 text-amber-400 text-[9px] font-bold font-mono rounded-full border border-amber-500/20 animate-pulse">
                        {{ $pending->count() }}
                    </span>
                @endif
            </div>

            @forelse($pending as $topup)
                <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-6 mb-4 hover:border-amber-500/20 transition-all">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-6">

                        {{-- Client Info --}}
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-slate-400 border border-white/5 flex-shrink-0">
                                <i data-lucide="building" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-200">{{ $topup->client->name }}</h4>
                                <p class="text-[10px] text-slate-500 font-mono mt-0.5">{{ $topup->client->email ?? '—' }}</p>
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="flex items-center gap-8 text-center flex-shrink-0">
                            <div>
                                <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">Slots</p>
                                <p class="text-sm font-bold text-white font-mono">+{{ $topup->amount_requested }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">Value</p>
                                <p class="text-sm font-bold text-white font-mono">₹{{ number_format($topup->client->price_per_file * $topup->amount_requested, 0) }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">UTR / Txn ID</p>
                                <p class="text-xs font-mono text-indigo-400">{{ $topup->transaction_id ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">Submitted</p>
                                <p class="text-xs text-slate-500 font-mono">{{ $topup->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-start gap-3 flex-shrink-0">
                            {{-- Approve --}}
                            <form method="POST" action="{{ route('admin.topup.approve', $topup) }}">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-green-500/20 transition-all flex items-center gap-1.5 whitespace-nowrap">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                                </button>
                            </form>

                            {{-- Reject with optional note --}}
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open"
                                    class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all flex items-center gap-1.5 whitespace-nowrap">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                                </button>
                                <div x-show="open" x-transition
                                    class="absolute right-0 top-10 z-20 w-72 bg-[#0a0a0c] border border-white/10 rounded-2xl p-5 shadow-2xl">
                                    <form method="POST" action="{{ route('admin.topup.reject', $topup) }}">
                                        @csrf
                                        <label class="block text-[9px] text-slate-500 font-bold uppercase tracking-widest mb-2">Rejection Note (optional)</label>
                                        <textarea name="notes" rows="3" maxlength="500"
                                            class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300 placeholder-slate-600 resize-none focus:outline-none focus:border-red-500/40"
                                            placeholder="e.g. UTR not found in bank records…"></textarea>
                                        <button type="submit"
                                            class="mt-3 w-full px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all">
                                            Confirm Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl px-6 py-10 text-center text-xs text-slate-600">
                    No pending top-up requests.
                </div>
            @endforelse
        </div>

        {{-- ── Resolved Requests ───────────────────────────────────────────── --}}
        @if($resolved->count() > 0)
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-widest mb-5">Recent Activity</h2>
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-slate-600 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                            <th class="px-6 py-4">Client</th>
                            <th class="px-4 py-4">Slots</th>
                            <th class="px-4 py-4">UTR / Txn ID</th>
                            <th class="px-4 py-4">Reviewed</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-6 py-4">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        @foreach($resolved as $topup)
                            <tr class="hover:bg-white/[0.01] transition-all">
                                <td class="px-6 py-4 text-sm font-bold text-slate-300">{{ $topup->client->name }}</td>
                                <td class="px-4 py-4 text-sm font-mono text-slate-400">+{{ $topup->amount_requested }}</td>
                                <td class="px-4 py-4 text-xs font-mono text-indigo-400">{{ $topup->transaction_id ?? '—' }}</td>
                                <td class="px-4 py-4 text-[10px] text-slate-500 font-mono">
                                    {{ $topup->reviewed_at ? $topup->reviewed_at->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    @if($topup->status === 'approved')
                                        <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold border border-green-500/10">Approved</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-[9px] font-bold border border-red-500/10">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-[10px] text-slate-500 max-w-xs truncate">{{ $topup->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Alpine.js for reject dropdown (if not already loaded globally) --}}
    @push('scripts')
        @if(!app()->environment('testing'))
            <script>
                // Ensure lucide icons are re-initialised for dynamically rendered content
                if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            </script>
        @endif
    @endpush

</x-admin-layout>
