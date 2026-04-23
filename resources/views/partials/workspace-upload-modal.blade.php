@if(in_array($order->status->value, ['claimed', 'processing']))
<div id="upload-modal-{{ $order->id }}" data-upload-modal="{{ $order->id }}"
    class="hidden fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4"
    onclick="if(event.target===this)this.classList.add('hidden')">

    <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-lg shadow-2xl overflow-y-auto max-h-[90vh]"
        onclick="event.stopPropagation()">

        <div class="flex items-center justify-between px-5 pt-4 pb-4 border-b border-white/[0.06]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-500/[0.12] rounded-xl flex items-center justify-center border border-indigo-500/[0.2]">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-indigo-400"></i>
                </div>
                <div>
                    <h3 class="text-[14px] font-bold text-white tracking-tight">Submit reports</h3>
                    <p class="text-[9px] text-slate-600 font-mono uppercase tracking-widest mt-0.5 truncate max-w-[220px]">
                        {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Order #' . $order->id }}
                    </p>
                </div>
            </div>
            <button onclick="closeUploadModal({{ $order->id }}, this)"
                class="w-8 h-8 bg-white/[0.04] hover:bg-white/[0.08] text-slate-500 hover:text-white rounded-lg flex items-center justify-center transition-all border border-white/[0.06]">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="{{ route('orders.report', $order) }}" method="POST" enctype="multipart/form-data"
            class="px-5 py-4 space-y-3">
            @csrf

            <div class="flex flex-col gap-2 rounded-xl border border-amber-500/[0.16] bg-amber-500/[0.06] p-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-3.5 h-3.5 text-amber-300 flex-shrink-0"></i>
                    <p class="text-[10px] font-semibold text-amber-100">AI report unavailable?</p>
                </div>
                <label class="flex items-center gap-2 text-[10px] font-semibold text-slate-200 cursor-pointer w-fit">
                    <input type="checkbox" id="ai-skipped-{{ $order->id }}" name="ai_skipped" value="1" class="rounded bg-white/[0.04] border-white/[0.1] text-indigo-500 focus:ring-indigo-500/30" onchange="toggleAiBypass({{ $order->id }}, this.checked, this)">
                    AI report could not be generated
                </label>
                <div id="ai-skip-reason-container-{{ $order->id }}" class="hidden">
                    <input type="text" name="ai_skip_reason" id="ai-skip-reason-input-{{ $order->id }}" placeholder="Brief reason (e.g. file too short for AI check)" class="w-full bg-black/20 border border-amber-500/[0.18] rounded-lg px-3 py-2 text-[10px] text-white placeholder-amber-100/30 focus:outline-none focus:border-amber-400/50" oninput="checkUploadReady({{ $order->id }}, this)">
                </div>
            </div>

            <div class="space-y-2">
                <div id="ai-upload-container-{{ $order->id }}">
                    <label id="ai-label-{{ $order->id }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-white/[0.03] border border-dashed border-white/[0.08] rounded-xl cursor-pointer hover:border-red-400/40 hover:bg-red-500/[0.04] transition-all">
                        <svg class="w-5 h-5 text-red-300/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div id="ai-preview-{{ $order->id }}" class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-white">AI report PDF</p>
                            <p class="text-[10px] text-slate-500">Tap to select a PDF</p>
                        </div>
                        <input type="file" name="ai_report" accept=".pdf" required class="hidden"
                            onchange="previewFile(this, 'ai-preview-{{ $order->id }}', 'ai-label-{{ $order->id }}', 'red', {{ $order->id }})">
                    </label>
                </div>

                <div>
                    <label id="plag-label-{{ $order->id }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-white/[0.03] border border-dashed border-white/[0.08] rounded-xl cursor-pointer hover:border-amber-400/40 hover:bg-amber-500/[0.04] transition-all">
                        <svg class="w-5 h-5 text-amber-300/70 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <div id="plag-preview-{{ $order->id }}" class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-white">Plagiarism report PDF</p>
                            <p class="text-[10px] text-slate-500">Tap to select a PDF</p>
                        </div>
                        <input type="file" name="plag_report" accept=".pdf" required class="hidden"
                            onchange="previewFile(this, 'plag-preview-{{ $order->id }}', 'plag-label-{{ $order->id }}', 'amber', {{ $order->id }})">
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-white/[0.06] bg-white/[0.03]">
                <p class="text-[10px] text-slate-400">PDF only. Max 100 MB</p>
                <p class="text-[10px] text-slate-500">Both required unless AI is skipped</p>
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
                    onclick="closeUploadModal({{ $order->id }}, this)"
                    class="px-5 py-2.5 text-[11px] font-semibold text-slate-500 hover:text-white bg-white/[0.04] hover:bg-white/[0.08] rounded-xl transition-all border border-white/[0.06]">
                    Cancel
                </button>
                <button type="button" id="submit-btn-{{ $order->id }}"
                    onclick="submitUploadForm({{ $order->id }}, this)"
                    disabled
                    class="flex-1 py-2.5 text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    Select required files
                </button>
            </div>
        </form>
    </div>
</div>
@endif
