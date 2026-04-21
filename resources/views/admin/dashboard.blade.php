<x-admin-layout>

    {{-- ═══════════════════════════════════════════
         PAGE HEADER
    ═══════════════════════════════════════════ --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight topbar-title">Dashboard</h1>
            <p class="text-[11px] uppercase tracking-[0.2em] mt-0.5" style="color:#9CA3AF;font-family:'DM Mono',monospace;">
                {{ now()->format('l, d M Y') }}
            </p>
        </div>
        <button
            onclick="document.getElementById('create-account-modal').classList.remove('hidden')"
            class="btn-primary">
            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
            Issue Account
        </button>
    </div>

    {{-- ═══════════════════════════════════════════
         REVENUE STRIP
    ═══════════════════════════════════════════ --}}
    <div class="rev-strip rounded-xl mb-4" style="border:1px solid #DDD6FE;">
        <div class="rev-item">
            <span class="rev-dot" style="background:#059669"></span>
            <span class="rev-label">Revenue</span>
            <span class="rev-val" style="color:#059669">₹{{ $revenueToday ?? 0 }}</span>
        </div>
        <div class="rev-item">
            <span class="rev-dot" style="background:#DC2626"></span>
            <span class="rev-label">Payout due</span>
            <span class="rev-val" style="color:#DC2626">₹{{ $payoutDue ?? 0 }}</span>
        </div>
        <div class="rev-item">
            <span class="rev-dot" style="background:#9CA3AF"></span>
            <span class="rev-label">Op. costs</span>
            <span class="rev-val" style="color:#4B5563">₹{{ $opCosts ?? 0 }}</span>
        </div>
        <div class="rev-item">
            <span class="rev-dot" style="background:#6D28D9"></span>
            <span class="rev-label">Net profit</span>
            <span class="rev-val" style="color:#6D28D9">₹{{ $netProfit ?? 0 }}</span>
        </div>
        <div class="rev-item" style="margin-left:auto;border-right:none;padding-right:0;margin-right:0;">
            <span class="rev-dot" style="background:#D97706"></span>
            <span class="rev-label">Pending pool</span>
            <span class="rev-val" style="color:#D97706">{{ $stats['pending_pool'] ?? 0 }} awaiting</span>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         ORDER STAT CARDS
    ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <a href="{{ route('admin.billing.index') }}" class="stat-card stat-card-delivered">
            <div class="stat-icon-box" style="background:linear-gradient(135deg,#6D28D9,#8B5CF6);box-shadow:0 4px 14px rgba(109,40,217,0.4);">
                <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <p class="stat-label">Processed today</p>
                <p class="stat-number">{{ $stats['total_processed_today'] }}</p>
                <p class="stat-sub">files delivered</p>
                <span class="stat-badge badge-purple">Today</span>
            </div>
        </a>

        <a href="{{ route('admin.matrix.index') }}" class="stat-card stat-card-pending">
            <div class="stat-icon-box" style="background:linear-gradient(135deg,#D97706,#F59E0B);box-shadow:0 4px 14px rgba(217,119,6,0.4);">
                <i data-lucide="inbox" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <p class="stat-label">Pending pool</p>
                <p class="stat-number">{{ $stats['pending_pool'] }}</p>
                <p class="stat-sub">awaiting claim</p>
                <span class="stat-badge badge-amber">Queued</span>
            </div>
        </a>

        <a href="{{ route('admin.accounts.index') }}?tab=vendors" class="stat-card stat-card-vendors">
            <div class="stat-icon-box" style="background:linear-gradient(135deg,#DB2777,#EC4899);box-shadow:0 4px 14px rgba(219,39,119,0.4);">
                <i data-lucide="shield" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <p class="stat-label">Working now</p>
                <p class="stat-number">{{ $stats['working_vendors_now'] }}</p>
                <p class="stat-sub">{{ $stats['active_vendors_today'] }} active today</p>
                <span class="stat-badge" style="background:#FCE7F3;color:#831843;">Live</span>
            </div>
        </a>

        <a href="{{ route('admin.matrix.index') }}" class="stat-card stat-card-clients">
            <div class="stat-icon-box" style="background:linear-gradient(135deg,#0891B2,#06B6D4);box-shadow:0 4px 14px rgba(8,145,178,0.4);">
                <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <p class="stat-label">New clients</p>
                <p class="stat-number">{{ $stats['new_clients_today'] }}</p>
                <p class="stat-sub">{{ $stats['total_clients'] }} total</p>
                <span class="stat-badge badge-cyan">Today</span>
            </div>
        </a>

    </div>

    {{-- ═══════════════════════════════════════════
         CLIENT HEALTH ROW
    ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Client health --}}
        <div class="dash-card">
            <div class="dash-card-header">
                <span class="dash-card-title">
                    <div class="w-6 h-6 rounded-md flex items-center justify-center" style="background:#EDE9FE;">
                        <i data-lucide="users" class="w-3.5 h-3.5" style="color:#6D28D9;"></i>
                    </div>
                    Client health
                </span>
                <a href="{{ route('admin.accounts.index') }}?tab=clients" class="dash-card-link">Manage →</a>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-4 gap-3">
                    <a href="{{ route('admin.accounts.index') }}?tab=clients"
                       class="text-center p-3 rounded-xl transition-colors" style="background:#F5F3FF;">
                        <p class="text-lg font-bold font-mono" style="color:#1E1B4B;">{{ $stats['total_clients'] }}</p>
                        <p class="text-[10px] mt-0.5" style="color:#9CA3AF;">Total</p>
                    </a>
                    <a href="{{ route('admin.accounts.index') }}?tab=clients&filter=frozen"
                       class="text-center p-3 rounded-xl transition-colors"
                       style="{{ $stats['frozen_client_users'] > 0 ? 'background:#FEE2E2;' : 'background:#F5F3FF;' }}">
                        <p class="text-lg font-bold font-mono" style="{{ $stats['frozen_client_users'] > 0 ? 'color:#DC2626;' : 'color:#1E1B4B;' }}">{{ $stats['frozen_client_users'] }}</p>
                        <p class="text-[10px] mt-0.5" style="{{ $stats['frozen_client_users'] > 0 ? 'color:#DC2626;' : 'color:#9CA3AF;' }}">Frozen</p>
                    </a>
                    <a href="{{ route('admin.matrix.index') }}"
                       class="text-center p-3 rounded-xl transition-colors"
                       style="{{ $stats['suspended_clients'] > 0 ? 'background:#FEF3C7;' : 'background:#F5F3FF;' }}">
                        <p class="text-lg font-bold font-mono" style="{{ $stats['suspended_clients'] > 0 ? 'color:#D97706;' : 'color:#1E1B4B;' }}">{{ $stats['suspended_clients'] }}</p>
                        <p class="text-[10px] mt-0.5" style="{{ $stats['suspended_clients'] > 0 ? 'color:#D97706;' : 'color:#9CA3AF;' }}">Suspended</p>
                    </a>
                    <a href="{{ route('admin.topup.index') }}"
                       class="text-center p-3 rounded-xl transition-colors"
                       style="{{ $stats['pending_topups'] > 0 ? 'background:#D1FAE5;' : 'background:#F5F3FF;' }}">
                        <p class="text-lg font-bold font-mono" style="{{ $stats['pending_topups'] > 0 ? 'color:#059669;' : 'color:#1E1B4B;' }}">{{ $stats['pending_topups'] }}</p>
                        <p class="text-[10px] mt-0.5" style="{{ $stats['pending_topups'] > 0 ? 'color:#059669;' : 'color:#9CA3AF;' }}">Top-ups</p>
                    </a>
                </div>

                {{-- Low credit alert --}}
                @if($stats['out_of_credit_clients'] > 0 || $stats['pending_refunds'] > 0)
                    <div class="mt-3 pt-3 flex items-center gap-4" style="border-top:1px solid #DDD6FE;">
                        @if($stats['out_of_credit_clients'] > 0)
                            <a href="{{ route('admin.matrix.index') }}" class="flex items-center gap-1.5 text-[11px] font-semibold" style="color:#D97706;">
                                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                {{ $stats['out_of_credit_clients'] }} client{{ $stats['out_of_credit_clients'] !== 1 ? 's' : '' }} out of credits
                            </a>
                        @endif
                        @if($stats['pending_refunds'] > 0)
                            <a href="{{ route('admin.refunds.index') }}" class="flex items-center gap-1.5 text-[11px] font-semibold" style="color:#D97706;">
                                <i data-lucide="refresh-ccw" class="w-3 h-3"></i>
                                {{ $stats['pending_refunds'] }} refund{{ $stats['pending_refunds'] !== 1 ? 's' : '' }} pending
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Vendor health --}}
        <div class="dash-card">
            <div class="dash-card-header">
                <span class="dash-card-title">
                    <div class="w-6 h-6 rounded-md flex items-center justify-center" style="background:#EDE9FE;">
                        <i data-lucide="shield" class="w-3.5 h-3.5" style="color:#6D28D9;"></i>
                    </div>
                    Vendor health
                </span>
                <a href="{{ route('admin.accounts.index') }}?tab=vendors" class="dash-card-link">Manage →</a>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-3 gap-3">
                    <a href="{{ route('admin.accounts.index') }}?tab=vendors"
                       class="text-center p-3 rounded-xl transition-colors" style="background:#F5F3FF;">
                        <p class="text-lg font-bold font-mono" style="color:#1E1B4B;">{{ $stats['total_vendors'] }}</p>
                        <p class="text-[10px] mt-0.5" style="color:#9CA3AF;">Total</p>
                    </a>
                    <div class="text-center p-3 rounded-xl"
                         style="{{ $stats['working_vendors_now'] > 0 ? 'background:#D1FAE5;' : 'background:#F5F3FF;' }}">
                        <p class="text-lg font-bold font-mono" style="{{ $stats['working_vendors_now'] > 0 ? 'color:#059669;' : 'color:#1E1B4B;' }}">{{ $stats['working_vendors_now'] }}</p>
                        <p class="text-[10px] mt-0.5" style="{{ $stats['working_vendors_now'] > 0 ? 'color:#059669;' : 'color:#9CA3AF;' }}">Working now</p>
                    </div>
                    <a href="{{ route('admin.accounts.index') }}?tab=vendors&filter=frozen"
                       class="text-center p-3 rounded-xl transition-colors"
                       style="{{ $stats['frozen_vendors'] > 0 ? 'background:#FEE2E2;' : 'background:#F5F3FF;' }}">
                        <p class="text-lg font-bold font-mono" style="{{ $stats['frozen_vendors'] > 0 ? 'color:#DC2626;' : 'color:#1E1B4B;' }}">{{ $stats['frozen_vendors'] }}</p>
                        <p class="text-[10px] mt-0.5" style="{{ $stats['frozen_vendors'] > 0 ? 'color:#DC2626;' : 'color:#9CA3AF;' }}">Frozen</p>
                    </a>
                </div>

                {{-- Vendor performance bar --}}
                @if($stats['total_vendors'] > 0)
                    <div class="mt-3 pt-3" style="border-top:1px solid #DDD6FE;">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px]" style="color:#9CA3AF;">Active today</span>
                            <span class="text-[10px] font-mono" style="color:#9CA3AF;font-family:'DM Mono',monospace;">{{ $stats['active_vendors_today'] }}/{{ $stats['total_vendors'] }}</span>
                        </div>
                        <div class="h-1.5 rounded-full overflow-hidden" style="background:#EDE9FE;">
                            <div class="h-full rounded-full transition-all"
                                 style="width:{{ $stats['total_vendors'] > 0 ? round(($stats['active_vendors_today'] / $stats['total_vendors']) * 100) : 0 }}%;background:linear-gradient(90deg,#6D28D9,#8B5CF6);"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════
         ACTIVE ORDERS + RECENT ACTIVITY
    ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Active orders 2/3 --}}
        <div class="lg:col-span-2 dash-card">
            <div class="dash-card-header">
                <span class="dash-card-title">
                    <div class="w-6 h-6 rounded-md flex items-center justify-center" style="background:#FEF3C7;">
                        <i data-lucide="activity" class="w-3.5 h-3.5" style="color:#D97706;"></i>
                    </div>
                    Active orders
                </span>
                @if($activeOrders->count() > 0)
                    <span class="stat-badge badge-amber">{{ $activeOrders->count() }} in progress</span>
                @endif
            </div>

            @if($activeOrders->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-2" style="background:#EDE9FE;">
                        <i data-lucide="inbox" class="w-4 h-4" style="color:#6D28D9;"></i>
                    </div>
                    <p class="text-sm font-medium" style="color:#9CA3AF;">No active orders</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-bold uppercase tracking-widest" style="color:#9CA3AF;background:#F5F3FF;">
                                <th class="px-5 py-3">File / Client</th>
                                <th class="py-3">Vendor</th>
                                <th class="py-3">Elapsed</th>
                                <th class="py-3 pr-5 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeOrders as $order)
                                @php
                                    $minutes  = $order->claimed_at ? $order->claimed_at->diffInMinutes(now()) : 0;
                                    $isStalled = $minutes > 60;
                                @endphp
                                <tr class="act-item" style="display:table-row;">
                                    <td class="px-5 py-3.5" style="border-bottom:1px solid #DDD6FE;">
                                        <p class="text-[12px] font-semibold truncate max-w-[180px]" style="color:#1E1B4B;">
                                            {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Order #'.$order->id }}
                                        </p>
                                        <p class="text-[10px] mt-0.5" style="color:#9CA3AF;font-family:'DM Mono',monospace;">{{ $order->client?->name ?? 'Unknown' }}</p>
                                    </td>
                                    <td class="py-3.5" style="border-bottom:1px solid #DDD6FE;">
                                        <span class="text-[12px] font-medium" style="color:#6D28D9;">
                                            {{ $order->vendor?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5" style="border-bottom:1px solid #DDD6FE;">
                                        <span class="text-[11px] font-mono" style="{{ $isStalled ? 'color:#DC2626;font-weight:700;' : 'color:#9CA3AF;' }}font-family:'DM Mono',monospace;">
                                            @if($minutes >= 60) {{ floor($minutes/60) }}h {{ $minutes%60 }}m
                                            @else {{ $minutes }}m @endif
                                        </span>
                                    </td>
                                    <td class="py-3.5 pr-5 text-right" style="border-bottom:1px solid #DDD6FE;">
                                        @if($isStalled)
                                            <span class="stat-badge badge-red">
                                                <i data-lucide="alert-triangle" class="w-2.5 h-2.5 mr-1"></i> Stalled
                                            </span>
                                        @elseif($order->status->value === 'claimed')
                                            <span class="stat-badge" style="background:#FEF3C7;color:#92400E;">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1" style="background:#D97706;display:inline-block;"></span> Reserved
                                            </span>
                                        @else
                                            <span class="stat-badge badge-green">
                                                <span class="w-1.5 h-1.5 rounded-full animate-pulse mr-1" style="background:#059669;display:inline-block;"></span> Working
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
        <div class="dash-card">
            <div class="dash-card-header">
                <span class="dash-card-title">
                    <div class="w-6 h-6 rounded-md flex items-center justify-center" style="background:#EDE9FE;">
                        <i data-lucide="clock" class="w-3.5 h-3.5" style="color:#6D28D9;"></i>
                    </div>
                    Recent activity
                </span>
            </div>
            <div class="max-h-[360px] overflow-y-auto">
                @forelse($recentOrders as $order)
                    <div class="act-item">
                        <div class="act-av @if(in_array($order->status->value, ['pending','claimed'])) amber @elseif($order->status->value==='processing') cyan @endif flex-shrink-0">
                            {{ strtoupper(substr($order->client?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="act-name truncate">{{ $order->client?->name ?? 'Unknown' }}</p>
                            <p class="act-detail mt-0.5">{{ ucfirst($order->status->value) }} · {{ $order->files_count }} file{{ $order->files_count!==1?'s':'' }}</p>
                        </div>
                        <span class="act-time flex-shrink-0 mt-0.5">{{ $order->created_at->diffForHumans(null,true,true) }}</span>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="text-sm" style="color:#9CA3AF;">No orders yet</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════
         VENDOR PERFORMANCE
    ═══════════════════════════════════════════ --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <span class="dash-card-title">
                <div class="w-6 h-6 rounded-md flex items-center justify-center" style="background:#FEE2E2;">
                    <i data-lucide="zap" class="w-3.5 h-3.5" style="color:#DC2626;"></i>
                </div>
                Vendor performance
                <span class="text-[10px] font-normal" style="color:#9CA3AF;">today</span>
            </span>
            <a href="{{ route('admin.accounts.index') }}?tab=vendors" class="dash-card-link">View all →</a>
        </div>

        @if($vendorPerformance->isEmpty())
            <div class="py-10 text-center">
                <p class="text-sm" style="color:#9CA3AF;">No vendor activity today</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-bold uppercase tracking-widest" style="color:#9CA3AF;background:#F5F3FF;">
                            <th class="px-5 py-3">Vendor</th>
                            <th class="py-3 text-center">Today</th>
                            <th class="py-3 text-center">Lifetime</th>
                            <th class="py-3 pr-5 text-right">Share today</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalToday = $vendorPerformance->sum('today_jobs'); @endphp
                        @foreach($vendorPerformance as $vendor)
                            <tr class="transition-colors" style="border-bottom:1px solid #DDD6FE;" onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background=''">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="act-av flex-shrink-0" style="width:28px;height:28px;font-size:9px;">
                                            {{ strtoupper(substr($vendor->name,0,1)) }}
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-semibold" style="color:#1E1B4B;">{{ $vendor->name }}</p>
                                            <p class="text-[10px] truncate max-w-[160px]" style="color:#9CA3AF;">{{ $vendor->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 text-center">
                                    <span class="text-[13px] font-bold font-mono" style="{{ $vendor->today_jobs > 0 ? 'color:#059669;' : 'color:#9CA3AF;' }}font-family:'DM Mono',monospace;">
                                        {{ $vendor->today_jobs }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-center">
                                    <span class="text-[12px] font-mono" style="color:#9CA3AF;font-family:'DM Mono',monospace;">{{ number_format($vendor->total_jobs) }}</span>
                                </td>
                                <td class="py-3.5 pr-5">
                                    @php $share = $totalToday > 0 ? round(($vendor->today_jobs/$totalToday)*100) : 0; @endphp
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 h-1.5 rounded-full overflow-hidden" style="background:#EDE9FE;">
                                            <div class="h-full rounded-full" style="width:{{ $share }}%;background:linear-gradient(90deg,#6D28D9,#8B5CF6);"></div>
                                        </div>
                                        <span class="text-[10px] font-mono w-7 text-right" style="color:#9CA3AF;font-family:'DM Mono',monospace;">{{ $share }}%</span>
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
        <div class="w-full max-w-md p-7 shadow-2xl"
             style="background:#FFFFFF;border:1px solid #DDD6FE;border-radius:12px;"
             onclick="event.stopPropagation()">

            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#EDE9FE;">
                        <i data-lucide="user-plus" class="w-4 h-4" style="color:#6D28D9;"></i>
                    </div>
                    <div>
                        <h3 class="font-bold" style="color:#1E1B4B;">Issue Account</h3>
                        <p class="text-[10px] uppercase tracking-widest mt-0.5" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Create new access</p>
                    </div>
                </div>
                <button onclick="document.getElementById('create-account-modal').classList.add('hidden')"
                    class="transition-colors" style="color:#9CA3AF;">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- ── FORM SECTION ────────────────────────────── --}}
            <div id="invite-form-section">
                <form action="{{ route('admin.accounts.invite') }}" method="POST" class="space-y-4" id="invite-form">
                    @csrf

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Account type</label>
                        <select name="role" id="modal-role" onchange="toggleInviteRoleFields()" required
                            class="w-full rounded-xl px-4 py-3 text-sm appearance-none focus:outline-none transition-all"
                            style="background:#F5F3FF;border:1px solid #DDD6FE;color:#1E1B4B;">
                            @can('create-admin')
                                <option value="admin">System Admin</option>
                            @endcan
                            <option value="vendor">Vendor</option>
                            <option value="client">Client</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Full name</label>
                        <input type="text" name="name" required placeholder="Jane Smith"
                            class="w-full rounded-xl px-4 py-3 text-sm focus:outline-none transition-all"
                            style="background:#F5F3FF;border:1px solid #DDD6FE;color:#1E1B4B;">
                    </div>

                    {{-- Vendor: payout rate --}}
                    <div id="invite-vendor-fields" class="hidden">
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Custom Payout Rate (₹/order)</label>
                        <input type="number" name="payout_rate" min="1" step="0.01"
                            placeholder="Default: {{ config('services.portal.vendor_payout_per_order') }}"
                            class="w-full rounded-xl px-4 py-3 text-sm focus:outline-none transition-all"
                            style="background:#F5F3FF;border:1px solid #DDD6FE;color:#1E1B4B;">
                        <p class="text-[10px] mt-1" style="color:#9CA3AF;">Leave blank to use the system default (₹{{ config('services.portal.vendor_payout_per_order') }}/order).</p>
                    </div>

                    {{-- Client: slots --}}
                    <div id="invite-client-fields" class="hidden">
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Initial slots</label>
                        <input type="number" name="slots" value="10" min="1"
                            class="w-full rounded-xl px-4 py-3 text-sm focus:outline-none transition-all"
                            style="background:#F5F3FF;border:1px solid #DDD6FE;color:#1E1B4B;">
                    </div>

                    @if($errors->any())
                        <div class="rounded-xl px-4 py-3" style="background:#FEE2E2;border:1px solid #DC2626;">
                            @foreach($errors->all() as $error)
                                <p class="text-xs" style="color:#7F1D1D;">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <button type="submit" id="invite-submit-btn" class="btn-primary w-full justify-center py-3">
                        <i id="invite-submit-icon" data-lucide="link" class="w-3.5 h-3.5"></i>
                        <svg id="invite-submit-spinner" class="hidden w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span id="invite-submit-label">Generate Invite Link</span>
                    </button>
                </form>
            </div>

            {{-- ── RESULT SECTION (shown after invite is generated) ── --}}
            <div id="invite-result-section" class="hidden space-y-4">
                <div class="rounded-xl p-4" style="background:#F0FDF4;border:1px solid #86EFAC;">
                    <p class="text-xs font-bold mb-1" style="color:#166534;">Invite link generated</p>
                    <p class="text-[11px]" style="color:#15803D;">
                        Valid for <strong>7 days</strong>. Send this link to
                        <strong id="invite-result-name"></strong> — they'll activate their account via Telegram.
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Invite link</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="invite-link-box" readonly
                            class="flex-1 rounded-xl px-4 py-3 text-xs font-mono focus:outline-none select-all"
                            style="background:#F5F3FF;border:1px solid #DDD6FE;color:#1E1B4B;">
                        <button type="button" onclick="copyInviteLink()" id="copy-invite-btn"
                            class="flex-shrink-0 px-4 py-3 rounded-xl text-xs font-bold transition-all"
                            style="background:#EDE9FE;border:1px solid #DDD6FE;color:#6D28D9;">
                            Copy
                        </button>
                    </div>
                </div>

                <button type="button" onclick="showInviteForm()"
                    class="w-full py-2.5 rounded-xl text-xs font-semibold transition-colors"
                    style="background:#F5F3FF;border:1px solid #DDD6FE;color:#6D28D9;">
                    Generate Another Invite
                </button>
            </div>

            <script>
                function toggleInviteRoleFields() {
                    const role = document.getElementById('modal-role').value;
                    document.getElementById('invite-client-fields').classList.toggle('hidden', role !== 'client');
                    document.getElementById('invite-vendor-fields').classList.toggle('hidden', role !== 'vendor');
                }

                toggleInviteRoleFields();

                document.getElementById('invite-form')?.addEventListener('submit', function () {
                    const btn = document.getElementById('invite-submit-btn');
                    const icon = document.getElementById('invite-submit-icon');
                    const spinner = document.getElementById('invite-submit-spinner');
                    const label = document.getElementById('invite-submit-label');
                    if (btn) { btn.disabled = true; btn.classList.add('opacity-80', 'cursor-wait'); }
                    icon?.classList.add('hidden');
                    spinner?.classList.remove('hidden');
                    if (label) label.textContent = 'Generating...';
                });

                function showInviteForm() {
                    document.getElementById('invite-form-section').classList.remove('hidden');
                    document.getElementById('invite-result-section').classList.add('hidden');
                }

                function copyInviteLink() {
                    const input = document.getElementById('invite-link-box');
                    navigator.clipboard.writeText(input.value).then(() => {
                        const btn = document.getElementById('copy-invite-btn');
                        const original = btn.textContent;
                        btn.textContent = 'Copied!';
                        btn.style.background = '#D1FAE5';
                        btn.style.borderColor = '#6EE7B7';
                        btn.style.color = '#065F46';
                        setTimeout(() => {
                            btn.textContent = original;
                            btn.style.background = '#EDE9FE';
                            btn.style.borderColor = '#DDD6FE';
                            btn.style.color = '#6D28D9';
                        }, 2000);
                    });
                }

                @if(session('invite_link'))
                    document.getElementById('invite-link-box').value = @json(session('invite_link'));
                    document.getElementById('invite-result-name').textContent = @json(session('invite_name', ''));
                    document.getElementById('invite-form-section').classList.add('hidden');
                    document.getElementById('invite-result-section').classList.remove('hidden');
                    document.getElementById('create-account-modal').classList.remove('hidden');
                @endif
            </script>
        </div>
    </div>

    @if($errors->any())
    <script>
        document.getElementById('create-account-modal').classList.remove('hidden');
        const inviteBtn = document.getElementById('invite-submit-btn');
        const inviteIcon = document.getElementById('invite-submit-icon');
        const inviteSpinner = document.getElementById('invite-submit-spinner');
        const inviteLabel = document.getElementById('invite-submit-label');
        if (inviteBtn) { inviteBtn.disabled = false; inviteBtn.classList.remove('opacity-80', 'cursor-wait'); }
        if (inviteIcon) inviteIcon.classList.remove('hidden');
        if (inviteSpinner) inviteSpinner.classList.add('hidden');
        if (inviteLabel) inviteLabel.textContent = 'Generate Invite Link';
    </script>
    @endif

</x-admin-layout>
