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

    {{-- UPLOAD CARD --}}
    <div class="rounded-3xl overflow-hidden"
         style="background:#0f0f14; border:1px solid rgba(99,102,241,0.2); box-shadow:0 0 0 1px rgba(99,102,241,0.07), 0 8px 32px -8px rgba(99,102,241,0.15);">

        {{-- Gradient top strip --}}
        <div class="h-1 w-full" style="background:linear-gradient(90deg,#6366f1,#8b5cf6,#6366f1);"></div>

        <div class="p-4 sm:p-5 xl:p-6 flex flex-col gap-4">

            {{-- Header --}}
            <div class="flex justify-between items-start gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-indigo-400/70">New Order</p>
                    <h2 class="text-[15px] sm:text-[17px] font-bold text-white tracking-tight mt-2">Secure Upload</h2>
                    <p class="text-[11px] text-slate-400 mt-1">Submit your document for non-repository scanning</p>
                </div>
                <div class="w-10 h-10 sm:w-11 sm:h-11 bg-indigo-500/[0.12] rounded-2xl flex items-center justify-center border border-indigo-500/[0.2] flex-shrink-0">
                    <i data-lucide="shield" class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-400"></i>
                </div>
            </div>

            {{-- Upload button — inner card --}}
            <div class="rounded-2xl" style="background:rgba(99,102,241,0.04); border:1px solid rgba(99,102,241,0.12);">
                <button onclick="openClientUploadModal()"
                    class="w-full flex flex-col items-center justify-center gap-2 py-7 text-indigo-400 font-bold text-[13px] transition-all active:scale-[0.98] hover:bg-indigo-500/[0.05] rounded-2xl">
                    <span class="w-11 h-11 rounded-xl bg-indigo-500/[0.12] border border-indigo-500/[0.2] flex items-center justify-center mb-1">
                        <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                    </span>
                    <span class="text-[14px] font-bold text-white">New Order</span>
                    <span class="text-[10px] text-slate-500 font-medium normal-case tracking-normal">PDF · DOCX · ZIP · up to 100MB</span>
                </button>
            </div>

            {{-- Feature badges --}}
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

    {{-- DOWNLOADS SECTION --}}
    @php
        $downloadOrders = $orders->filter(
            fn($o) => in_array($o->status->value, ['processing', 'delivered'])
                   && $o->updated_at->gte(now()->subHours(24))
        );
    @endphp

    <div>
        <div class="flex items-center justify-between gap-3 px-1 mb-3">
            <h2 class="text-[10px] sm:text-[11px] font-black text-white uppercase tracking-[0.18em] sm:tracking-[0.2em]">Downloads</h2>
            <span class="text-[7px] font-black uppercase tracking-widest text-slate-600 bg-white/[0.03] border border-white/[0.06] px-2 py-0.5 rounded">Last 24h</span>
        </div>

        <div class="space-y-3">
            @forelse($downloadOrders as $order)

                {{-- PROCESSING --}}
                @if($order->status->value === 'processing')
                    <div class="card rounded-2xl overflow-hidden">
                        <div class="flex items-start justify-between gap-3 p-4">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-10 h-10 bg-blue-500/[0.07] rounded-xl flex items-center justify-center text-blue-400 border border-blue-500/[0.12] flex-shrink-0">
                                    <i data-lucide="file-text" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-[13px] font-bold text-white truncate leading-snug">
                                        {{ $order->files->first()
                                            ? ($order->files->first()->original_name ?? basename($order->files->first()->file_path))
                                            : 'Document' }}
                                    </h4>
                                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                        #{{ strtoupper($order->token_view) }} &bull; {{ $order->updated_at->format('h:i A') }}
                                    </p>
                                </div>
                            </div>
                            <span class="status-badge bg-blue-500/[0.1] text-blue-400 border border-blue-500/[0.15] flex-shrink-0">
                                <span class="w-1 h-1 rounded-full bg-blue-400 pulse-dot"></span> Processing
                            </span>
                        </div>
                        <div class="border-t border-white/[0.05] px-4 py-3 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full pulse-dot flex-shrink-0"></span>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Report will appear here when ready</p>
                        </div>
                    </div>

                {{-- DELIVERED --}}
                @elseif($order->status->value === 'delivered')
                    <div class="card rounded-2xl overflow-hidden">
                        <div class="flex items-start justify-between gap-3 p-4">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-10 h-10 bg-emerald-500/[0.07] rounded-xl flex items-center justify-center text-emerald-400 border border-emerald-500/[0.12] flex-shrink-0">
                                    <i data-lucide="file-check" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-[13px] font-bold text-white truncate leading-snug">
                                        {{ $order->files->first()
                                            ? ($order->files->first()->original_name ?? basename($order->files->first()->file_path))
                                            : 'Document' }}
                                    </h4>
                                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                        #{{ strtoupper($order->token_view) }} &bull; {{ $order->updated_at->format('h:i A') }}
                                    </p>
                                </div>
                            </div>
                            <span class="status-badge bg-emerald-500/[0.1] text-emerald-400 border border-emerald-500/[0.15] flex-shrink-0">
                                <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Ready
                            </span>
                        </div>
                        <div class="border-t border-white/[0.05] px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($order->report?->ai_report_path && $order->report?->plag_report_path)
                                    <a href="{{ route('client.download', $order->token_view) }}"
                                        class="flex items-center gap-1.5 px-3 py-2 bg-indigo-500/[0.12] hover:bg-indigo-500/[0.2] text-indigo-300 text-[10px] font-bold rounded-xl border border-indigo-500/[0.2] transition-all active:scale-95">
                                        <i data-lucide="archive" class="w-3.5 h-3.5"></i> Download Both
                                    </a>
                                @endif
                                @if($order->report?->ai_report_path)
                                    <a href="{{ route('client.download', $order->token_view) }}?type=ai"
                                        class="flex items-center gap-1.5 px-3 py-2 bg-white/[0.03] hover:bg-red-500/[0.1] text-red-300 text-[10px] font-bold rounded-xl border border-red-500/[0.12] transition-all active:scale-95">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i> AI Report
                                    </a>
                                @endif
                                @if($order->report?->plag_report_path)
                                    <a href="{{ route('client.download', $order->token_view) }}?type=plag"
                                        class="flex items-center gap-1.5 px-3 py-2 bg-white/[0.03] hover:bg-amber-500/[0.1] text-amber-300 text-[10px] font-bold rounded-xl border border-amber-500/[0.12] transition-all active:scale-95">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i> Plag Report
                                    </a>
                                @endif
                                @if(!$order->report?->ai_report_path && !$order->report?->plag_report_path)
                                    <p class="text-[10px] text-slate-600 font-medium">Reports not yet attached by admin</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

            @empty
                <div class="card rounded-2xl px-4 py-6 flex items-center gap-4">
                    <div class="w-9 h-9 bg-white/[0.03] rounded-xl flex items-center justify-center border border-white/[0.05] flex-shrink-0">
                        <i data-lucide="download" class="w-4 h-4 text-slate-700"></i>
                    </div>
                    <p class="text-[11px] text-slate-600 font-medium">No active downloads — reports ready in the last 24h will appear here</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
