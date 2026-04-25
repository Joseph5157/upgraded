<div id="client-upload-modal"
    class="hidden fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4"
    onclick="if(event.target===this)closeClientUploadModal()">

    <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-lg shadow-2xl overflow-y-auto max-h-[90vh]"
        onclick="event.stopPropagation()">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 pt-4 pb-4 border-b border-white/[0.06]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-500/[0.12] rounded-xl flex items-center justify-center border border-indigo-500/[0.2]">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-indigo-400"></i>
                </div>
                <div>
                    <h3 class="text-[14px] font-bold text-white tracking-tight">New Order</h3>
                    <p class="text-[9px] text-slate-600 font-mono uppercase tracking-widest mt-0.5">1 credit will be used</p>
                </div>
            </div>
            <button onclick="closeClientUploadModal()"
                class="w-8 h-8 bg-white/[0.04] hover:bg-white/[0.08] text-slate-500 hover:text-white rounded-lg flex items-center justify-center transition-all border border-white/[0.06]">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="client-upload-modal-form" action="{{ route('client.dashboard.upload') }}" method="POST" enctype="multipart/form-data"
            class="px-5 py-4 space-y-3">
            @csrf

            {{-- File upload zone --}}
            <label id="client-upload-label"
                class="flex items-center gap-3 w-full px-4 py-4 bg-white/[0.03] border-2 border-dashed border-indigo-500/[0.16] rounded-xl cursor-pointer hover:border-indigo-400/40 hover:bg-indigo-500/[0.04] transition-all">
                <div class="w-10 h-10 bg-indigo-500/[0.08] rounded-xl flex items-center justify-center border border-indigo-500/[0.12] flex-shrink-0 transition-all">
                    <i data-lucide="file-plus" class="w-5 h-5 text-indigo-400"></i>
                </div>
                <div id="client-upload-preview" class="min-w-0 flex-1">
                    <p class="text-[13px] font-semibold text-white/90">Drop a file or tap to browse</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">PDF · DOCX · DOC · ZIP · up to 100MB</p>
                </div>
                <input type="file" name="files[]" id="client-upload-input" required class="hidden"
                    accept=".pdf,.doc,.docx,.zip,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip"
                    onchange="handleClientFileSelect(this)">
            </label>

            {{-- Notes --}}
            <div class="space-y-1.5">
                <label class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                    <i data-lucide="message-square" class="w-3 h-3 text-indigo-400"></i>
                    Instructions for Vendor
                    <span class="text-slate-600 font-medium normal-case tracking-normal">(optional)</span>
                </label>
                <textarea name="notes" id="client-upload-notes" rows="2"
                    placeholder="e.g. Please check for AI content in Chapter 2, priority is plagiarism scan..."
                    class="w-full bg-white/[0.03] border border-white/[0.07] hover:border-indigo-500/30 focus:border-indigo-500/50 rounded-xl px-4 py-3 text-[12px] text-white placeholder-slate-700 focus:outline-none transition-all resize-none leading-relaxed"></textarea>
                <p id="client-upload-notes-counter" class="text-[9px] text-slate-700 text-right font-mono">0 / 1000</p>
            </div>

            {{-- Info bar --}}
            <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-white/[0.06] bg-white/[0.03]">
                <p class="text-[10px] text-slate-400">PDF, DOCX, DOC, ZIP · Max 100MB</p>
                <p class="text-[10px] text-slate-500">One file per order</p>
            </div>

            {{-- Ready strip --}}
            <div id="client-upload-ready" class="hidden items-center gap-2.5 px-3.5 py-2.5 bg-emerald-500/[0.06] border border-emerald-500/[0.12] rounded-xl">
                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0"></i>
                <p class="text-[10px] text-emerald-400 font-semibold">File looks good. Ready to submit.</p>
            </div>

            {{-- Progress bar --}}
            <div id="client-upload-progress" class="hidden flex-col gap-1.5 px-3.5 py-2.5 bg-indigo-500/[0.06] border border-indigo-500/[0.12] rounded-xl">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] text-indigo-400 font-semibold">Uploading...</p>
                    <span id="client-upload-progress-text" class="text-[10px] text-indigo-400 font-bold tabular-nums">0%</span>
                </div>
                <div class="h-1.5 bg-white/[0.06] rounded-full overflow-hidden">
                    <div id="client-upload-progress-fill" class="h-full bg-indigo-500 rounded-full transition-[width] duration-150" style="width:0%"></div>
                </div>
            </div>

            {{-- Error strip --}}
            <div id="client-upload-error" class="hidden items-center gap-2.5 px-3.5 py-2.5 bg-red-500/[0.06] border border-red-500/[0.15] rounded-xl">
                <svg class="w-3.5 h-3.5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p id="client-upload-error-msg" class="text-[10px] text-red-400 font-semibold"></p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-1" style="padding-bottom: max(env(safe-area-inset-bottom), 12px);">
                <button type="button" id="client-upload-cancel-btn"
                    onclick="closeClientUploadModal()"
                    class="px-5 py-2.5 text-[11px] font-semibold text-slate-500 hover:text-white bg-white/[0.04] hover:bg-white/[0.08] rounded-xl transition-all border border-white/[0.06]">
                    Cancel
                </button>
                <button type="button" id="client-upload-submit-btn"
                    onclick="submitClientUploadForm()"
                    disabled
                    class="flex-1 py-2.5 text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    Select a file first
                </button>
            </div>
        </form>
    </div>
</div>
