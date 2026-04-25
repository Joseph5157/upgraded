<div id="client-dashboard-live" class="space-y-4 sm:space-y-5">
    @php
        $activeOrders = $orders->whereNotIn('status', ['delivered', 'cancelled'])->count();
        $planLabel = $client->plan_expiry && $client->plan_expiry->isPast() ? 'Expired' : 'Professional';
        $creditTone = $remaining > 10
            ? 'border-emerald-500/[0.16] bg-emerald-500/[0.05] text-emerald-300'
            : ($remaining > 0
                ? 'border-amber-500/[0.16] bg-amber-500/[0.05] text-amber-300'
                : 'border-red-500/[0.16] bg-red-500/[0.05] text-red-300');
    @endphp

    <div class="card rounded-[1.75rem] p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Client Overview</p>
                <h2 class="text-[1.2rem] sm:text-[1.5rem] font-semibold text-white mt-2 tracking-tight leading-tight">Upload. Track. Download.</h2>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-[0.18em] border @if($client->plan_expiry && $client->plan_expiry->isPast()) border-red-500/[0.18] bg-red-500/[0.06] text-red-300 @else border-emerald-500/[0.18] bg-emerald-500/[0.06] text-emerald-300 @endif">
                <span class="w-1.5 h-1.5 rounded-full @if($client->plan_expiry && $client->plan_expiry->isPast()) bg-red-400 @else bg-emerald-400 @endif"></span>
                {{ $planLabel }}
            </span>
        </div>

        <div class="mt-4 space-y-3">
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-emerald-500/[0.16] bg-emerald-500/[0.05]">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                    <p class="text-[12px] sm:text-[13px] font-medium text-emerald-200 leading-6">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-red-500/[0.16] bg-red-500/[0.05]">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0"></i>
                    <p class="text-[12px] sm:text-[13px] font-medium text-red-200 leading-6">{{ session('error') }}</p>
                </div>
            @endif
            @if($errors->any())
                <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-amber-500/[0.16] bg-amber-500/[0.05]">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-amber-300 mt-0.5 flex-shrink-0"></i>
                    <p class="text-[12px] sm:text-[13px] font-medium text-amber-100 leading-6">{{ $errors->first() }}</p>
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 rounded-2xl px-4 py-3 border {{ $creditTone }}">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full @if($remaining > 10) bg-emerald-400 @elseif($remaining > 0) bg-amber-400 @else bg-red-400 @endif"></span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Credit Status</p>
                        <p class="text-[13px] font-semibold mt-1">
                            @if($remaining > 10)
                                {{ $remaining }} credits available
                            @elseif($remaining > 0)
                                {{ $remaining }} credits remaining
                            @else
                                0 credits, top up required
                            @endif
                        </p>
                    </div>
                </div>
                <button onclick="document.getElementById('topup-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-lg bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-bold uppercase tracking-[0.18em] transition-colors">
                    Top Up
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 min-w-0 gap-3">
        <div class="card rounded-2xl p-4 min-w-0">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Credits</p>
            <div class="flex items-end justify-between mt-3 gap-3">
                <div>
                    <h3 class="text-[2rem] font-extrabold text-white leading-none font-mono">{{ $remaining }}</h3>
                    <p class="text-[11px] text-slate-400 mt-2">Used: {{ $consumed }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/[0.08] border border-indigo-500/[0.12] flex items-center justify-center text-indigo-400 flex-shrink-0">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                </div>
            </div>
        </div>

        <div class="card rounded-2xl p-4 min-w-0">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Orders</p>
            <div class="flex items-end justify-between mt-3 gap-3">
                <div>
                    <h3 class="text-[2rem] font-extrabold text-white leading-none font-mono">{{ $activeOrders }}</h3>
                    <p class="text-[11px] text-slate-400 mt-2">Active in workflow</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-500/[0.08] border border-blue-500/[0.12] flex items-center justify-center text-blue-400 flex-shrink-0">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">
        <div class="lg:col-span-7">
            <div class="card rounded-3xl p-4 sm:p-5 xl:p-6 flex flex-col gap-4">
                <div class="flex justify-between items-start gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">New Order</p>
                        <h2 class="text-[15px] sm:text-[17px] font-bold text-white tracking-tight mt-2">Secure Upload</h2>
                        <p class="text-[11px] text-slate-400 mt-1">Submit your document for non-repository scanning</p>
                    </div>
                    <div class="w-10 h-10 sm:w-11 sm:h-11 bg-white/[0.03] rounded-2xl flex items-center justify-center border border-white/[0.06] flex-shrink-0">
                        <i data-lucide="shield" class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-400"></i>
                    </div>
                </div>

                <button onclick="openClientUploadModal()"
                    class="w-full flex items-center justify-center gap-2.5 py-5 rounded-2xl border-2 border-dashed border-indigo-500/[0.2] bg-indigo-500/[0.03] hover:border-indigo-400/50 hover:bg-indigo-500/[0.06] text-indigo-400 font-bold text-[13px] transition-all active:scale-[0.98]">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    New Order
                </button>

                <div class="grid grid-cols-2 gap-2 sm:gap-3">
                    <div class="p-3 sm:p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] flex items-center gap-2 sm:gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="text-[9px] sm:text-[10px] font-bold text-slate-300 uppercase tracking-[0.14em] sm:tracking-widest leading-tight">AI Detection<br>Enabled</span>
                    </div>
                    <div class="p-3 sm:p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] flex items-center gap-2 sm:gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="text-[9px] sm:text-[10px] font-bold text-slate-300 uppercase tracking-[0.14em] sm:tracking-widest leading-tight">No Repo<br>Mode</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-5 flex flex-col gap-4">
            <div class="flex items-center justify-between gap-3 px-1">
                <h2 class="text-[10px] sm:text-[11px] font-black text-white uppercase tracking-[0.18em] sm:tracking-[0.2em]">Recent Activity</h2>
                <span class="text-[7px] font-black uppercase tracking-widest text-indigo-400/40 bg-indigo-500/[0.05] border border-indigo-500/[0.08] px-2 py-0.5 rounded cursor-not-allowed">Coming Soon</span>
            </div>

            <div class="card rounded-3xl p-3 sm:p-4 overflow-y-auto scrollbar-thin max-h-[500px] space-y-2">
                <div class="overflow-x-auto -mx-4 px-4">
                @forelse($orders as $order)
                    <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-3 sm:p-4 group">

                        {{-- File row --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white/[0.04] rounded-xl flex items-center justify-center text-slate-500 group-hover:bg-indigo-500/[0.12] group-hover:text-indigo-400 transition-all border border-white/[0.05] flex-shrink-0">
                                    <i data-lucide="file-text" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="min-w-0">
                                        <h4 class="text-[12px] sm:text-[13px] font-bold text-white truncate leading-snug max-w-[170px] sm:max-w-none">
                                            {{ $order->files->first() ? ($order->files->first()->original_name ?? basename($order->files->first()->file_path)) : 'Document' }}
                                        </h4>
                                        @if($order->files_count > 1)
                                            <p class="text-[9px] text-indigo-300 font-bold uppercase tracking-widest mt-1">
                                                + {{ $order->files_count - 1 }} more file{{ $order->files_count - 1 > 1 ? 's' : '' }}
                                            </p>
                                        @endif
                                    </div>
                                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                        {{ $order->created_at->format('d M, h:i A') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Status badge --}}
                            @if($order->status->value === 'delivered')
                                <span class="status-badge bg-emerald-500/[0.1] text-emerald-400 border border-emerald-500/[0.15] flex-shrink-0">
                                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Ready
                                </span>
                            @elseif($order->status->value === 'cancelled')
                                <span class="status-badge bg-slate-500/[0.1] text-slate-500 border border-slate-500/[0.15] flex-shrink-0">
                                    <span class="w-1 h-1 rounded-full bg-slate-500"></span> Cancelled
                                </span>
                            @elseif($order->status->value === 'processing')
                                <span class="status-badge bg-blue-500/[0.1] text-blue-400 border border-blue-500/[0.15] flex-shrink-0">
                                    <span class="w-1 h-1 rounded-full bg-blue-400 pulse-dot"></span> In progress
                                </span>
                            @elseif($order->status->value === 'claimed')
                                <span class="status-badge bg-amber-500/[0.1] text-amber-400 border border-amber-500/[0.15] flex-shrink-0">
                                    <span class="w-1 h-1 rounded-full bg-amber-400"></span> Reserved
                                </span>
                            @else
                                <span class="status-badge bg-slate-500/[0.08] text-slate-500 border border-slate-500/[0.1] flex-shrink-0">
                                    <span class="w-1 h-1 rounded-full bg-slate-500 pulse-dot"></span> Queued
                                </span>
                            @endif
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-white/[0.05] mt-3 pt-3">

                            {{-- DELIVERED STATE --}}
                            @if($order->status->value === 'delivered')
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($order->report?->ai_report_path && $order->report?->plag_report_path)
                                            <a href="{{ route('client.download', $order->token_view) }}"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-500/[0.12] hover:bg-indigo-500/[0.2] text-indigo-300 text-[9px] font-bold rounded-lg border border-indigo-500/[0.2] transition-all active:scale-95">
                                                <i data-lucide="archive" class="w-3 h-3"></i> Download Both
                                            </a>
                                        @endif
                                        @if($order->report?->ai_report_path)
                                            <a href="{{ route('client.download', $order->token_view) }}?type=ai"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-red-500/[0.12] text-red-300 text-[9px] font-bold rounded-lg border border-red-500/[0.12] transition-all active:scale-95">
                                                <i data-lucide="download" class="w-3 h-3"></i> AI Report
                                            </a>
                                        @endif
                                        @if($order->report?->plag_report_path)
                                            <a href="{{ route('client.download', $order->token_view) }}?type=plag"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-amber-500/[0.12] text-amber-300 text-[9px] font-bold rounded-lg border border-amber-500/[0.12] transition-all active:scale-95">
                                                <i data-lucide="download" class="w-3 h-3"></i> Plag Report
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            {{-- CANCELLED STATE --}}
                            @elseif($order->status->value === 'cancelled')
                                @if($order->files->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2 mb-3">
                                        @foreach($order->files as $file)
                                            <form method="POST" action="{{ route('client.orders.files.delete', [$order, $file]) }}"
                                                onsubmit="return confirm('Permanently delete this file from our servers?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/[0.08] hover:bg-red-500/[0.15] text-red-400 text-[10px] font-bold rounded-lg border border-red-500/[0.15] transition-all">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                    <span class="truncate max-w-[120px]">{{ $file->original_name ?? basename($file->file_path) }}</span>
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                        <i data-lucide="ban" class="w-3 h-3"></i> Order Cancelled
                                    </p>
                                    @php $existingRefund = $order->refundRequest ?? null; @endphp
                                    @if($order->release_count > 0)
                                        <span class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/[0.08] text-amber-400 text-[10px] font-bold rounded-lg border border-amber-500/[0.15]" title="A vendor processed this order in Turnitin. Contact admin for manual review.">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i> Contact Admin
                                        </span>
                                    @elseif($existingRefund && $existingRefund->status === 'pending')
                                        <span class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/[0.08] text-amber-400 text-[10px] font-bold rounded-lg border border-amber-500/[0.15]">
                                            <i data-lucide="clock" class="w-3 h-3"></i> Refund queued
                                        </span>
                                    @elseif($existingRefund && $existingRefund->status === 'approved')
                                        <span class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/[0.08] text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-500/[0.15]">
                                            <i data-lucide="check-circle" class="w-3 h-3"></i> Refund Approved
                                        </span>
                                    @else
                                        <span class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/[0.08] text-red-400 text-[10px] font-bold rounded-lg border border-red-500/[0.15]">
                                            <i data-lucide="x-circle" class="w-3 h-3"></i> Refund Rejected
                                        </span>
                                    @endif
                                </div>

                            {{-- ACTIVE / PENDING STATE --}}
                            @else
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400 font-bold uppercase tracking-widest">
                                        @if($order->status->value === 'processing')
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full pulse-dot"></span>
                                            In progress...
                                        @elseif($order->status->value === 'claimed')
                                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                            Reserved...
                                        @else
                                            <span class="w-1.5 h-1.5 bg-slate-600 rounded-full pulse-dot"></span>
                                            Queued...
                                        @endif
                                    </div>
                                    @if($order->status->value === 'pending' && !$order->claimed_by)
                                        <form action="{{ route('client.orders.delete', $order) }}" method="POST"
                                            onsubmit="return confirm('Delete this order and all its files permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-red-500/[0.12] text-red-300 text-[9px] font-bold rounded-lg border border-red-500/[0.12] transition-all active:scale-95">
                                                <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-14 text-center">
                        <div class="w-14 h-14 bg-white/[0.03] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/[0.05]">
                            <i data-lucide="inbox" class="w-6 h-6 text-slate-700"></i>
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">No Recent Orders</p>
                        <p class="text-[11px] text-slate-500 mt-1">Upload a document to get started</p>
                    </div>
                @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
