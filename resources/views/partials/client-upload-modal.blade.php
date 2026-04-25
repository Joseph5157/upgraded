<div id="client-upload-modal"
    class="hidden fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4"
    onclick="if(event.target===this)closeClientUploadModal()">

    <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-lg shadow-2xl overflow-y-auto max-h-[90vh]"
        onclick="event.stopPropagation()">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 pt-4 pb-4 border-b border-white/[0.06]">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                    <h3 class="text-[14px] font-bold text-white tracking-tight">New Order</h3>
                    <p class="text-[9px] text-slate-500 font-mono uppercase tracking-widest mt-0.5">1 credit will be used</p>
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
                class="flex items-center gap-4 w-full px-4 py-4 bg-indigo-500/[0.04] border-2 border-dashed border-indigo-500/30 rounded-xl cursor-pointer hover:border-violet-400/60 hover:bg-indigo-500/[0.08] transition-all group">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/30 group-hover:scale-105 transition-transform">
                    <i data-lucide="file-plus" class="w-6 h-6 text-white"></i>
                </div>
                <div id="client-upload-preview" class="min-w-0 flex-1">
                    <p class="text-[13px] font-semibold text-white/90">Drop a file or tap to browse</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">PDF · DOCX · DOC · ZIP · up to 100MB</p>
                </div>
                <input type="file" name="files[]" id="client-upload-input" required class="hidden"
                    accept=".pdf,.doc,.docx,.zip,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip"
                    onchange="handleClientFileSelect(this)">
            </label>

            {{-- Feature pills --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-emerald-500/[0.07] border border-emerald-500/20">
                    <div class="w-6 h-6 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow shadow-emerald-500/30">
                        <i data-lucide="cpu" class="w-3.5 h-3.5 text-white"></i>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-300 leading-tight">AI Detection<br>Enabled</span>
                </div>
                <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-violet-500/[0.07] border border-violet-500/20">
                    <div class="w-6 h-6 bg-gradient-to-br from-violet-400 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow shadow-violet-500/30">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-white"></i>
                    </div>
                    <span class="text-[10px] font-bold text-violet-300 leading-tight">No Repo<br>Mode</span>
                </div>
            </div>

            {{-- Notes --}}
            <div class="space-y-1.5">
                <label class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                    <i data-lucide="message-square" class="w-3 h-3 text-violet-400"></i>
                    Instructions for Vendor
                    <span class="text-slate-600 font-medium normal-case tracking-normal">(optional)</span>
                </label>
                <textarea name="notes" id="client-upload-notes" rows="2"
                    placeholder="e.g. Please check for AI content in Chapter 2, priority is plagiarism scan..."
                    class="w-full bg-white/[0.03] border border-white/[0.07] hover:border-violet-500/30 focus:border-violet-500/50 rounded-xl px-4 py-3 text-[12px] text-white placeholder-slate-700 focus:outline-none transition-all resize-none leading-relaxed"></textarea>
                <p id="client-upload-notes-counter" class="text-[9px] text-slate-700 text-right font-mono">0 / 1000</p>
            </div>

            {{-- Info bar --}}
            <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-white/[0.06] bg-white/[0.02]">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3 h-3 text-slate-600 flex-shrink-0"></i>
                    <p class="text-[10px] text-slate-500">PDF, DOCX, DOC, ZIP · Max 100MB</p>
                </div>
                <p class="text-[10px] text-slate-600">One file per order</p>
            </div>

            {{-- Ready strip --}}
            <div id="client-upload-ready" class="hidden items-center gap-2.5 px-3.5 py-2.5 rounded-xl border border-emerald-500/20 bg-emerald-500/[0.06]">
                <div class="w-5 h-5 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow shadow-emerald-500/30">
                    <i data-lucide="check" class="w-3 h-3 text-white"></i>
                </div>
                <p class="text-[10px] text-emerald-400 font-semibold">File looks good. Ready to submit.</p>
            </div>

            {{-- Progress bar --}}
            <div id="client-upload-progress" class="hidden flex-col gap-1.5 px-3.5 py-2.5 rounded-xl border border-indigo-500/20 bg-indigo-500/[0.06]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="loader" class="w-3 h-3 text-indigo-400 animate-spin"></i>
                        <p class="text-[10px] text-indigo-400 font-semibold">Uploading...</p>
                    </div>
                    <span id="client-upload-progress-text" class="text-[10px] text-indigo-300 font-bold tabular-nums">0%</span>
                </div>
                <div class="h-1.5 bg-white/[0.06] rounded-full overflow-hidden">
                    <div id="client-upload-progress-fill" class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full transition-[width] duration-150" style="width:0%"></div>
                </div>
            </div>

            {{-- Error strip --}}
            <div id="client-upload-error" class="hidden items-center gap-2.5 px-3.5 py-2.5 rounded-xl border border-red-500/20 bg-red-500/[0.06]">
                <div class="w-5 h-5 bg-gradient-to-br from-red-400 to-rose-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow shadow-red-500/30">
                    <i data-lucide="alert-triangle" class="w-3 h-3 text-white"></i>
                </div>
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
                    class="flex-1 py-2.5 text-[11px] font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    Select a file first
                </button>
            </div>
        </form>
    </div>
</div>
