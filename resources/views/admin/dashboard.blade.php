<x-admin-layout>

    {{-- ═══════════════════════════════════════════
         PAGE HEADER
    ═══════════════════════════════════════════ --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard</h1>
            <p class="text-[11px] text-slate-400 font-mono uppercase tracking-[0.2em] mt-0.5">
                {{ now()->format('l, d M Y') }}
            </p>
        </div>
        <button
            onclick="document.getElementById('create-account-modal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors">
            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
            Issue Account
        </button>
    </div>

    {{-- ═══════════════════════════════════════════
         ORDER STAT CARDS
    ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <a href="{{ route('admin.billing.index') }}"
           class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl p-5 hover:border-emerald-500/40 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Processed today</span>
                <div class="w-7 h-7 bg-emerald-500/10 rounded-lg flex items-center justify-center text-emerald-500">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white font-mono">{{ $stats['total_processed_today'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">files delivered</p>
        </a>

        <a href="{{ route('admin.matrix.index') }}"
           class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl p-5 hover:border-amber-500/40 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pending pool</span>
                <div class="w-7 h-7 bg-amber-500/10 rounded-lg flex items-center justify-center text-amber-500">
                    <i data-lucide="inbox" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white font-mono">{{ $stats['pending_pool'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">awaiting claim</p>
        </a>

        <a href="{{ route('admin.accounts.index') }}?tab=vendors"
           class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl p-5 hover:border-indigo-500/40 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active vendors</span>
                <div class="w-7 h-7 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white font-mono">{{ $stats['active_vendors'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">of {{ $stats['total_vendors'] }} vendors</p>
        </a>

        <a href="{{ route('admin.matrix.index') }}"
           class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl p-5 hover:border-purple-500/40 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">New clients</span>
                <div class="w-7 h-7 bg-purple-500/10 rounded-lg flex items-center justify-center text-purple-500">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white font-mono">{{ $stats['new_clients_today'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">{{ $stats['total_clients'] }} total</p>
        </a>

    </div>

    {{-- ═══════════════════════════════════════════
         CLIENT HEALTH ROW
    ═══════════════════════════════════════════ --}}
    @php
        $suspendedClients  = \App\Models\Client::where('status','suspended')->count();
        $frozenClientUsers = \App\Models\User::where('role','client')->where('status','frozen')->count();
        $pendingTopupCount = \App\Models\TopupRequest::where('status','pending')->count();
        $lowCreditCount    = \App\Models\Client::whereRaw('slots_consumed >= slots')->count();
        $pendingRefundCount = \App\Models\RefundRequest::where('status','pending')->count();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Client health --}}
        <div class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <div class="w-6 h-6 bg-purple-500/10 rounded-md flex items-center justify-center text-purple-500">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-gray-900 dark:text-white">Client health</span>
                <a href="{{ route('admin.accounts.index') }}?tab=clients"
                   class="ml-auto text-[10px] font-bold text-indigo-400 hover:text-indigo-300 uppercase tracking-widest">
                    Manage →
                </a>
            </div>
            <div class="grid grid-cols-4 gap-3">
                <a href="{{ route('admin.accounts.index') }}?tab=clients"
                   class="text-center p-3 bg-slate-50 dark:bg-white/[0.03] rounded-xl hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors">
                    <p class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $stats['total_clients'] }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Total</p>
                </a>
                <a href="{{ route('admin.accounts.index') }}?tab=clients&filter=frozen"
                   class="text-center p-3 rounded-xl transition-colors {{ $frozenClientUsers > 0 ? 'bg-red-500/10 hover:bg-red-500/15' : 'bg-slate-50 dark:bg-white/[0.03] hover:bg-slate-100 dark:hover:bg-white/[0.06]' }}">
                    <p class="text-lg font-bold font-mono {{ $frozenClientUsers > 0 ? 'text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $frozenClientUsers }}</p>
                    <p class="text-[10px] {{ $frozenClientUsers > 0 ? 'text-red-400/70' : 'text-slate-400' }} mt-0.5">Frozen</p>
                </a>
                <a href="{{ route('admin.matrix.index') }}"
                   class="text-center p-3 rounded-xl transition-colors {{ $suspendedClients > 0 ? 'bg-amber-500/10 hover:bg-amber-500/15' : 'bg-slate-50 dark:bg-white/[0.03] hover:bg-slate-100 dark:hover:bg-white/[0.06]' }}">
                    <p class="text-lg font-bold font-mono {{ $suspendedClients > 0 ? 'text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $suspendedClients }}</p>
                    <p class="text-[10px] {{ $suspendedClients > 0 ? 'text-amber-400/70' : 'text-slate-400' }} mt-0.5">Suspended</p>
                </a>
                <a href="{{ route('admin.topup.index') }}"
                   class="text-center p-3 rounded-xl transition-colors {{ $pendingTopupCount > 0 ? 'bg-emerald-500/10 hover:bg-emerald-500/15' : 'bg-slate-50 dark:bg-white/[0.03] hover:bg-slate-100 dark:hover:bg-white/[0.06]' }}">
                    <p class="text-lg font-bold font-mono {{ $pendingTopupCount > 0 ? 'text-emerald-400' : 'text-gray-900 dark:text-white' }}">{{ $pendingTopupCount }}</p>
                    <p class="text-[10px] {{ $pendingTopupCount > 0 ? 'text-emerald-400/70' : 'text-slate-400' }} mt-0.5">Top-ups</p>
                </a>
            </div>

            {{-- Low credit alert --}}
            @if($lowCreditCount > 0 || $pendingRefundCount > 0)
                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-white/[0.04] flex items-center gap-4">
                    @if($lowCreditCount > 0)
                        <a href="{{ route('admin.matrix.index') }}" class="flex items-center gap-1.5 text-[11px] font-semibold text-amber-400 hover:text-amber-300">
                            <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                            {{ $lowCreditCount }} client{{ $lowCreditCount !== 1 ? 's' : '' }} out of credits
                        </a>
                    @endif
                    @if($pendingRefundCount > 0)
                        <a href="{{ route('admin.refunds.index') }}" class="flex items-center gap-1.5 text-[11px] font-semibold text-amber-400 hover:text-amber-300">
                            <i data-lucide="refresh-ccw" class="w-3 h-3"></i>
                            {{ $pendingRefundCount }} refund{{ $pendingRefundCount !== 1 ? 's' : '' }} pending
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Vendor health --}}
        @php
            $frozenVendors  = \App\Models\User::where('role','vendor')->where('status','frozen')->count();
            $activeVendors  = $stats['active_vendors'];
            $totalVendors   = $stats['total_vendors'];
        @endphp
        <div class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <div class="w-6 h-6 bg-indigo-500/10 rounded-md flex items-center justify-center text-indigo-500">
                    <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                </div>
                <span class="text-xs font-bold text-gray-900 dark:text-white">Vendor health</span>
                <a href="{{ route('admin.accounts.index') }}?tab=vendors"
                   class="ml-auto text-[10px] font-bold text-indigo-400 hover:text-indigo-300 uppercase tracking-widest">
                    Manage →
                </a>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <a href="{{ route('admin.accounts.index') }}?tab=vendors"
                   class="text-center p-3 bg-slate-50 dark:bg-white/[0.03] rounded-xl hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors">
                    <p class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $totalVendors }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Total</p>
                </a>
                <div class="text-center p-3 {{ $activeVendors > 0 ? 'bg-emerald-500/10' : 'bg-slate-50 dark:bg-white/[0.03]' }} rounded-xl">
                    <p class="text-lg font-bold font-mono {{ $activeVendors > 0 ? 'text-emerald-400' : 'text-gray-900 dark:text-white' }}">{{ $activeVendors }}</p>
                    <p class="text-[10px] {{ $activeVendors > 0 ? 'text-emerald-400/70' : 'text-slate-400' }} mt-0.5">Working now</p>
                </div>
                <a href="{{ route('admin.accounts.index') }}?tab=vendors&filter=frozen"
                   class="text-center p-3 rounded-xl transition-colors {{ $frozenVendors > 0 ? 'bg-red-500/10 hover:bg-red-500/15' : 'bg-slate-50 dark:bg-white/[0.03] hover:bg-slate-100 dark:hover:bg-white/[0.06]' }}">
                    <p class="text-lg font-bold font-mono {{ $frozenVendors > 0 ? 'text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $frozenVendors }}</p>
                    <p class="text-[10px] {{ $frozenVendors > 0 ? 'text-red-400/70' : 'text-slate-400' }} mt-0.5">Frozen</p>
                </a>
            </div>

            {{-- Vendor performance bar --}}
            @if($totalVendors > 0)
                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-white/[0.04]">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] text-slate-400">Active today</span>
                        <span class="text-[10px] font-mono text-slate-400">{{ $activeVendors }}/{{ $totalVendors }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 dark:bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all"
                             style="width: {{ $totalVendors > 0 ? round(($activeVendors / $totalVendors) * 100) : 0 }}%"></div>
                    </div>
                </div>
            @endif
        </div>

    </div>

    {{-- ═══════════════════════════════════════════
         ACTIVE ORDERS + RECENT ACTIVITY
    ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Active orders 2/3 --}}
        <div class="lg:col-span-2 bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.04]">
                <div class="flex items-center gap-2.5">
                    <div class="w-6 h-6 bg-amber-500/10 rounded-md flex items-center justify-center text-amber-500">
                        <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">Active orders</span>
                </div>
                @if($activeOrders->count() > 0)
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-amber-500/10 text-amber-500 border border-amber-500/20">
                        {{ $activeOrders->count() }} in progress
                    </span>
                @endif
            </div>

            @if($activeOrders->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-9 h-9 bg-slate-100 dark:bg-white/5 rounded-xl flex items-center justify-center mb-2">
                        <i data-lucide="inbox" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <p class="text-sm font-medium text-slate-400">No active orders</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] text-slate-400 font-bold uppercase tracking-widest bg-slate-50/50 dark:bg-white/[0.02]">
                                <th class="px-5 py-3">File / Client</th>
                                <th class="py-3">Vendor</th>
                                <th class="py-3">Elapsed</th>
                                <th class="py-3 pr-5 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            @foreach($activeOrders as $order)
                                @php
                                    $minutes  = $order->claimed_at ? $order->claimed_at->diffInMinutes(now()) : 0;
                                    $isStalled = $minutes > 60;
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors">
                                    <td class="px-5 py-3.5">
                                        <p class="text-[12px] font-semibold text-gray-800 dark:text-slate-200 truncate max-w-[180px]">
                                            {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Order #'.$order->id }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $order->client?->name ?? 'Unknown' }}</p>
                                    </td>
                                    <td class="py-3.5">
                                        <span class="text-[12px] font-medium text-slate-600 dark:text-indigo-300">
                                            {{ $order->vendor?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5">
                                        <span class="text-[11px] font-mono {{ $isStalled ? 'text-red-400 font-bold' : 'text-slate-400' }}">
                                            @if($minutes >= 60) {{ floor($minutes/60) }}h {{ $minutes%60 }}m
                                            @else {{ $minutes }}m @endif
                                        </span>
                                    </td>
                                    <td class="py-3.5 pr-5 text-right">
                                        @if($isStalled)
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold px-2 py-1 rounded-md bg-red-500/10 text-red-400 border border-red-500/20 uppercase">
                                                <i data-lucide="alert-triangle" class="w-2.5 h-2.5"></i> Stalled
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold px-2 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase">
                                                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span> Working
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Recent activity 1/3 --}}
        <div class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.04]">
                <span class="text-sm font-bold text-gray-900 dark:text-white">Recent activity</span>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-white/[0.04] max-h-[360px] overflow-y-auto">
                @forelse($recentOrders as $order)
                    <div class="px-5 py-3 flex items-start gap-3">
                        <div class="mt-1 flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center
                            @if($order->status->value==='delivered') bg-emerald-500/15
                            @elseif($order->status->value==='processing') bg-indigo-500/15
                            @elseif($order->status->value==='pending') bg-amber-500/15
                            @else bg-slate-200 dark:bg-white/5 @endif">
                            <div class="w-1.5 h-1.5 rounded-full
                                @if($order->status->value==='delivered') bg-emerald-400
                                @elseif($order->status->value==='processing') bg-indigo-400
                                @elseif($order->status->value==='pending') bg-amber-400
                                @else bg-slate-400 @endif"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-medium text-gray-800 dark:text-slate-200 truncate">{{ $order->client?->name ?? 'Unknown' }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ ucfirst($order->status->value) }} · {{ $order->files_count }} file{{ $order->files_count!==1?'s':'' }}</p>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono flex-shrink-0 mt-0.5">{{ $order->created_at->diffForHumans(null,true,true) }}</span>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="text-sm text-slate-400">No orders yet</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════
         VENDOR PERFORMANCE
    ═══════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-[#0d0d0f] border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.04]">
            <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 bg-red-500/10 rounded-md flex items-center justify-center text-red-500">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                </div>
                <div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">Vendor performance</span>
                    <span class="ml-2 text-[10px] text-slate-400">today</span>
                </div>
            </div>
            <a href="{{ route('admin.accounts.index') }}?tab=vendors"
               class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 uppercase tracking-widest">View all →</a>
        </div>

        @if($vendorPerformance->isEmpty())
            <div class="py-10 text-center">
                <p class="text-sm text-slate-400">No vendor activity today</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] text-slate-400 font-bold uppercase tracking-widest bg-slate-50/50 dark:bg-white/[0.02]">
                            <th class="px-5 py-3">Vendor</th>
                            <th class="py-3 text-center">Today</th>
                            <th class="py-3 text-center">Lifetime</th>
                            <th class="py-3 pr-5 text-right">Share today</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                        @php $totalToday = $vendorPerformance->sum('today_jobs'); @endphp
                        @foreach($vendorPerformance as $vendor)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-6 h-6 bg-indigo-500/10 rounded-md flex items-center justify-center text-indigo-400 text-[9px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($vendor->name,0,1)) }}
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-gray-800 dark:text-slate-200">{{ $vendor->name }}</p>
                                            <p class="text-[10px] text-slate-400 truncate max-w-[160px]">{{ $vendor->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 text-center">
                                    <span class="text-[13px] font-bold font-mono {{ $vendor->today_jobs > 0 ? 'text-emerald-400' : 'text-slate-500' }}">
                                        {{ $vendor->today_jobs }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-center">
                                    <span class="text-[12px] font-mono text-slate-400">{{ number_format($vendor->total_jobs) }}</span>
                                </td>
                                <td class="py-3.5 pr-5">
                                    @php $share = $totalToday > 0 ? round(($vendor->today_jobs/$totalToday)*100) : 0; @endphp
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 h-1.5 bg-slate-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full" style="width:{{ $share }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-mono text-slate-400 w-7 text-right">{{ $share }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════
         ISSUE ACCOUNT MODAL
    ═══════════════════════════════════════════ --}}
    <div id="create-account-modal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0a0a0c] border border-slate-200 dark:border-white/10 rounded-2xl w-full max-w-md p-7 shadow-2xl"
            onclick="event.stopPropagation()">

            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500">
                        <i data-lucide="user-plus" class="w-4.5 h-4.5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">Issue Account</h3>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5">Create new access</p>
                    </div>
                </div>
                <button onclick="document.getElementById('create-account-modal').classList.add('hidden')"
                    class="text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('admin.accounts.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Account type</label>
                    <select name="role" id="modal-role" onchange="toggleRoleFields()" required
                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/60 transition-all appearance-none">
                        @can('create-admin')
                            <option value="admin" {{ old('role')==='admin'?'selected':'' }}>System Admin</option>
                        @endcan
                        <option value="vendor" {{ old('role')==='vendor'?'selected':'' }}>Vendor</option>
                        <option value="client" {{ old('role')==='client'?'selected':'' }}>Client</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Jane Smith"
                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500/60 transition-all">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="jane@example.com"
                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500/60 transition-all">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Temporary password</label>
                    <input type="password" name="password" required placeholder="Min. 8 characters"
                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500/60 transition-all">
                </div>

                <div id="client-fields" class="hidden space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Organisation name</label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}" placeholder="Acme Corp"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500/60 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Initial slots</label>
                        <input type="number" name="slots" value="{{ old('slots', 10) }}" min="1"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/60 transition-all">
                    </div>
                </div>

                @if($errors->any())
                    <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3">
                        @foreach($errors->all() as $error)
                            <p class="text-xs text-red-400">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <button type="submit"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-colors">
                    Create Account
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('modal-role').value;
            document.getElementById('client-fields').classList.toggle('hidden', role !== 'client');
        }
        toggleRoleFields();
    </script>

</x-admin-layout>
