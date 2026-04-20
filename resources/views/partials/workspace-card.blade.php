<div class="order-card w-full rounded-2xl border border-white/[0.06] bg-black/10 dark:bg-white/[0.02] p-3" data-order-id="{{ $order->id }}">
    <div class="flex items-start justify-between gap-2">
        <div
            class="w-9 h-9 bg-indigo-600/10 rounded-xl flex items-center justify-center text-indigo-400 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        @if($order->status->value === 'processing')
            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-blue-400 bg-blue-500/5 border border-blue-500/10 px-2 py-1 rounded-full flex-shrink-0">
                <span class="w-1 h-1 bg-blue-400 rounded-full animate-pulse"></span> Processing
            </span>
        @elseif($order->status->value === 'claimed')
            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-400 bg-amber-500/5 border border-amber-500/10 px-2 py-1 rounded-full flex-shrink-0">
                <span class="w-1 h-1 bg-amber-400 rounded-full"></span> Reserved
            </span>
        @else
            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] px-2 py-1 rounded-full flex-shrink-0">
                <span class="w-1 h-1 bg-slate-500 rounded-full"></span> Pending
            </span>
        @endif
    </div>

    <div class="mt-3 min-w-0">
        <p class="text-xs font-semibold text-gray-900 truncate dark:text-slate-200">
            {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Document' }}
        </p>
        <div class="flex flex-wrap items-center gap-1.5 mt-1">
            @if($order->client)
                <span class="text-[9px] text-gray-500 dark:text-slate-500 truncate">{{ $order->client->name }}</span>
            @endif
            <span class="text-[8px] font-bold px-1 py-0.5 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
        </div>
        @if($order->notes)
            <p class="text-[9px] text-amber-400/80 mt-1.5 leading-relaxed line-clamp-2 min-h-[2rem]">
                <i data-lucide="message-square" class="w-2.5 h-2.5 inline-block mr-0.5 -mt-0.5"></i>{{ $order->notes }}
            </p>
        @else
            <div class="min-h-[2rem]"></div>
        @endif
    </div>

    {{-- Per-file downloads (multi-file orders) --}}
    @if($order->files->count() > 1)
        <div class="mt-3 space-y-1">
            @foreach($order->files as $file)
                <a href="{{ route('orders.files.download', [$order, $file]) }}"
                    class="w-full inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[9px] font-semibold text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 border border-gray-200 dark:bg-white/[0.05] dark:border-white/[0.08] dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] rounded-lg transition-all">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="truncate">{{ basename($file->file_path) }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 gap-2 mt-3">
        @if($order->files->first() && $order->files->count() === 1)
            <a href="{{ route('orders.files.download', [$order, $order->files->first()]) }}"
                class="inline-flex items-center justify-center gap-1 px-2.5 py-2 text-[10px] font-semibold text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 border border-gray-200 dark:bg-white/[0.05] dark:border-white/[0.08] dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] rounded-lg transition-all">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Download
            </a>
        @else
            <div></div>
        @endif
        <button
            onclick="ajaxAction('{{ route('orders.unclaim', $order) }}', this, 'unclaim', {{ $order->id }})"
            class="w-full inline-flex items-center justify-center gap-1 px-4 py-2.5 text-xs font-bold text-red-500 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all border border-red-500/20"
            data-order-id="{{ $order->id }}">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            Release
        </button>
        <div></div>
        @if($order->status->value === 'processing' || $order->status->value === 'claimed')
            <button
                onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.remove('hidden')"
                class="col-span-2 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-lg shadow-indigo-600/10">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Reports
            </button>
        @endif
    </div>
</div>

@if(in_array($order->status->value, ['claimed', 'processing']))
<div id="upload-modal-{{ $order->id }}"
    class="hidden fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4"
    onclick="if(event.target===this)this.classList.add('hidden')">

    <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-lg shadow-2xl overflow-y-auto max-h-[90vh]"
        onclick="event.stopPropagation()">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 pt-4 pb-4 border-b border-white/[0.06]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-500/[0.12] rounded-xl flex items-center justify-center border border-indigo-500/[0.2]">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-indigo-400"></i>
                </div>
                <div>
                    <h3 class="text-[14px] font-bold text-white tracking-tight">Submit Results</h3>
                    <p class="text-[9px] text-slate-600 font-mono uppercase tracking-widest mt-0.5 truncate max-w-[220px]">
                        {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Order #' . $order->id }}
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.add('hidden')"
                class="w-8 h-8 bg-white/[0.04] hover:bg-white/[0.08] text-slate-500 hover:text-white rounded-lg flex items-center justify-center transition-all border border-white/[0.06]">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ route('orders.report', $order) }}" method="POST" enctype="multipart/form-data"
            class="px-5 py-4 space-y-3">
            @csrf

            {{-- AI Bypass Checkbox --}}
            <div class="flex flex-col gap-2 rounded-xl border border-amber-500/[0.16] bg-amber-500/[0.06] p-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-3.5 h-3.5 text-amber-300 flex-shrink-0"></i>
                    <p class="text-[10px] font-semibold text-amber-100">AI report unavailable?</p>
                </div>
                <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-200 cursor-pointer w-fit">
                    <input type="checkbox" id="ai-skipped-{{ $order->id }}" name="ai_skipped" value="1" class="rounded bg-white/[0.04] border-white/[0.1] text-indigo-500 focus:ring-indigo-500/30" onchange="toggleAiBypass({{ $order->id }}, this.checked)">
                    AI report could not be generated
                </label>
                <div id="ai-skip-reason-container-{{ $order->id }}" class="hidden">
                    <input type="text" name="ai_skip_reason" id="ai-skip-reason-input-{{ $order->id }}" placeholder="Brief reason (e.g. file too short for AI check)" class="w-full bg-black/20 border border-amber-500/[0.18] rounded-lg px-3 py-2 text-[10px] text-white placeholder-amber-100/30 focus:outline-none focus:border-amber-400/50" oninput="checkUploadReady({{ $order->id }})">
                </div>
            </div>

            {{-- TWO UPLOAD ZONES --}}
            <div class="space-y-2">

                {{-- AI Report --}}
                <div id="ai-upload-container-{{ $order->id }}">
                    <label id="ai-label-{{ $order->id }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-white/[0.03] border border-dashed border-white/[0.08] rounded-xl cursor-pointer hover:border-red-400/40 hover:bg-red-500/[0.04] transition-all">
                        <svg class="w-5 h-5 text-red-300/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div id="ai-preview-{{ $order->id }}" class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-white">AI Report PDF</p>
                            <p class="text-[10px] text-slate-500">Tap to select PDF</p>
                        </div>
                        <input type="file" name="ai_report" accept=".pdf" required class="hidden"
                            onchange="previewFile(this, 'ai-preview-{{ $order->id }}', 'ai-label-{{ $order->id }}', 'red', {{ $order->id }})">
                    </label>
                </div>

                {{-- Plagiarism Report --}}
                <div>
                    <label id="plag-label-{{ $order->id }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-white/[0.03] border border-dashed border-white/[0.08] rounded-xl cursor-pointer hover:border-amber-400/40 hover:bg-amber-500/[0.04] transition-all">
                        <svg class="w-5 h-5 text-amber-300/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <div id="plag-preview-{{ $order->id }}" class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-white">Plagiarism Report PDF</p>
                            <p class="text-[10px] text-slate-500">Tap to select PDF</p>
                        </div>
                        <input type="file" name="plag_report" accept=".pdf" required class="hidden"
                            onchange="previewFile(this, 'plag-preview-{{ $order->id }}', 'plag-label-{{ $order->id }}', 'amber', {{ $order->id }})">
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-white/[0.06] bg-white/[0.03]">
                <p class="text-[10px] text-slate-400">PDF only · Max 100 MB</p>
                <p class="text-[10px] text-slate-500">Both required unless AI skipped</p>
            </div>

            <div id="progress-{{ $order->id }}" class="hidden items-center gap-2.5 px-3.5 py-2.5 bg-emerald-500/[0.06] border border-emerald-500/[0.12] rounded-xl">
                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0"></i>
                <p class="text-[10px] text-emerald-400 font-semibold">Files look good. Ready to submit.</p>
            </div>

            <div id="upload-progress-{{ $order->id }}" class="hidden flex-col gap-1.5 px-3.5 py-2.5 bg-indigo-500/[0.06] border border-indigo-500/[0.12] rounded-xl">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] text-indigo-400 font-semibold">Uploading reports...</p>
                    <span id="upload-progress-text-{{ $order->id }}" class="text-[10px] text-indigo-400 font-bold tabular-nums">0%</span>
                </div>
                <div class="h-1.5 bg-white/[0.06] rounded-full overflow-hidden">
                    <div id="upload-progress-fill-{{ $order->id }}" class="h-full bg-indigo-500 rounded-full transition-[width] duration-150" style="width:0%"></div>
                </div>
            </div>

            <div id="error-strip-{{ $order->id }}" class="hidden items-center gap-2.5 px-3.5 py-2.5 bg-red-500/[0.06] border border-red-500/[0.15] rounded-xl">
                <svg class="w-3.5 h-3.5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p id="error-msg-{{ $order->id }}" class="text-[10px] text-red-400 font-semibold"></p>
            </div>

            <div class="flex gap-3 pt-1" style="padding-bottom: max(env(safe-area-inset-bottom), 12px);">
                <button type="button" id="cancel-btn-{{ $order->id }}"
                    onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.add('hidden')"
                    class="px-5 py-2.5 text-[11px] font-semibold text-slate-500 hover:text-white bg-white/[0.04] hover:bg-white/[0.08] rounded-xl transition-all border border-white/[0.06]">
                    Cancel
                </button>
                <button type="button" id="submit-btn-{{ $order->id }}"
                    onclick="submitUploadForm({{ $order->id }})"
                    disabled
                    class="flex-1 py-2.5 text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    Select Required Files
                </button>
            </div>
        </form>
    </div>
</div>
@endif
