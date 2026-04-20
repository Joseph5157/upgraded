<x-vendor-layout title="Dashboard">

    <div class="space-y-3">
        <x-announcements-banner />
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

        {{-- Available Pool --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-indigo-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-indigo-400 bg-indigo-400/5 border border-indigo-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Pool</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white" data-stat="available_pool">{{ $stats['available_pool'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Available Orders</p>
        </div>

        {{-- Active Jobs --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-blue-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-blue-400 bg-blue-400/5 border border-blue-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Active</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white" data-stat="active_jobs">{{ $stats['active_jobs'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">In Progress</p>
        </div>

        {{-- Today Delivered --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-emerald-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Today</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white" data-stat="total_checked_today">{{ $stats['total_checked_today'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Delivered Today</p>
        </div>

        {{-- Total Delivered --}}
        <div class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-purple-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-purple-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-purple-400 bg-purple-400/5 border border-purple-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">All Time</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['total_delivered'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Total Delivered</p>
        </div>
    </div>

    {{-- ===== PRIMARY CONTENT GRID ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">

        {{-- LEFT COLUMN (2/3 width) --}}
        <div class="lg:col-span-2 space-y-4 sm:space-y-5 min-w-0">

            {{-- ===== MY WORKSPACE ===== --}}
            <div id="workspace" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-white/[0.04]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">My Workspace</h2>
                        @if($myWorkspace->count() > 0)
                            <span
                                class="workspace-count-badge bg-indigo-500/10 text-indigo-400 border border-indigo-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $myWorkspace->count() }}</span>
                        @endif
                    </div>
                </div>

                {{-- Mobile cards --}}
                <div class="sm:hidden">
                    @if($myWorkspace->isNotEmpty())
                        <div class="flex flex-col gap-3 px-4 pt-4 pb-2">
                            @foreach($myWorkspace as $order)
                                @include('partials.workspace-card', ['order' => $order])
                            @endforeach
                        </div>
                    @else
                        <div class="px-4 pt-4 pb-3 border-t border-gray-100 dark:border-white/[0.04]">
                            <div class="rounded-2xl border border-dashed border-white/[0.08] bg-black/10 dark:bg-white/[0.02] px-4 py-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] rounded-2xl flex items-center justify-center text-gray-400 dark:text-slate-500 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-500 dark:text-slate-300">No active jobs</p>
                                        <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Claim an order from the queue below</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Desktop table --}}
                <div class="hidden sm:block overflow-x-auto">
                <table class="w-full min-w-0">
                    <thead>
                        <tr
                            class="text-[9px] text-gray-400 font-semibold uppercase tracking-widest border-b border-gray-100 dark:text-slate-600 dark:border-white/[0.04]">
                            <th class="text-left px-3 sm:px-6 py-3 font-semibold">File</th>
                            <th class="text-center px-2 sm:px-4 py-3 font-semibold hidden sm:table-cell">Status</th>
                            <th class="text-right px-3 sm:px-6 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @forelse($myWorkspace as $order)
                            @include('partials.workspace-row', ['order' => $order])
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-14 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="w-12 h-12 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] rounded-2xl flex items-center justify-center text-gray-400 dark:text-slate-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">No active jobs</p>
                                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Claim an order from the queue below
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{-- ===== AVAILABLE FILES ===== --}}
            @include('dashboard.partials.available-queue')

        </div>{{-- end left col --}}

        {{-- RIGHT COLUMN (1/3 width) --}}
        <div class="space-y-4 sm:space-y-5 min-w-0">

            {{-- Recent History --}}
            <div id="history" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/[0.04]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Deliveries</h2>
                    @if($recentHistory->count() > 0)
                        <span class="text-[9px] text-gray-500 dark:text-slate-500 font-semibold">{{ $recentHistory->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @forelse($recentHistory as $history)
                        <div
                            class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="w-6 h-6 bg-emerald-500/5 rounded-lg flex items-center justify-center text-emerald-600 flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-[11px] font-semibold text-gray-900 truncate group-hover:text-gray-900 transition-colors dark:text-slate-300">
                                        {{ $history->files->first() ? basename($history->files->first()->file_path) : 'Document' }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5 font-mono">
                                        {{ $history->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span
                                class="flex-shrink-0 text-[8px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded uppercase tracking-wider">Done</span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold">No deliveries yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end right col --}}

    </div>{{-- end grid --}}

    {{-- Upload modals live inside workspace-row.blade.php (desktop) and
         workspace-card.blade.php (mobile) so they exist for both initial
         page-load orders and AJAX-injected orders with no duplicate IDs. --}}

    <script>
        const DASHBOARD_URL = @json(route('dashboard'));
        const CSRF_REFRESH_URL = @json(route('csrf.refresh'));

        const MAX_REPORT_SIZE = 100 * 1024 * 1024;
        let refreshInProgress = false;

        function refreshAvailableQueue() {
            if (document.hidden || refreshInProgress) return;

            refreshInProgress = true;

            fetch(DASHBOARD_URL + '?queue_only=1&queue_refresh=' + Date.now(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const incomingQueue = doc.getElementById('files');
                    const currentQueue = document.getElementById('files');

                    if (!incomingQueue || !currentQueue) return;
                    currentQueue.outerHTML = incomingQueue.outerHTML;
                    if (window.lucide && lucide.createIcons) lucide.createIcons();
                })
                .catch(() => {
                    // Ignore transient fetch errors; next polling tick will retry.
                })
                .finally(() => {
                    refreshInProgress = false;
                });
        }

        setInterval(refreshAvailableQueue, 30000);


        function setUploadError(orderId, message) {
            const errStrip = document.getElementById('error-strip-' + orderId);
            const errMsg   = document.getElementById('error-msg-' + orderId);
            if (!errStrip || !errMsg) return;
            errMsg.textContent = message;
            errStrip.classList.remove('hidden');
            errStrip.classList.add('flex');
        }

        function clearUploadError(orderId) {
            const errStrip = document.getElementById('error-strip-' + orderId);
            if (!errStrip) return;
            errStrip.classList.add('hidden');
            errStrip.classList.remove('flex');
        }

        function resetUploadUi(orderId) {
            const submitBtn   = document.getElementById('submit-btn-' + orderId);
            const cancelBtn   = document.getElementById('cancel-btn-' + orderId);
            const readyStrip  = document.getElementById('progress-' + orderId);
            const progressBar = document.getElementById('upload-progress-' + orderId);

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> Submit Reports';
            }
            if (cancelBtn) cancelBtn.disabled = false;
            if (progressBar) {
                progressBar.classList.add('hidden');
                progressBar.classList.remove('flex');
            }
            if (readyStrip) {
                readyStrip.classList.remove('hidden');
                readyStrip.classList.add('flex');
            }
            if (window.lucide && lucide.createIcons) lucide.createIcons();
        }

        function previewFile(input, previewId, labelId, color, orderId) {
            const file = input.files[0];
            if (!file) return;

            clearUploadError(orderId);

            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            if (!isPdf) {
                input.value = '';
                setUploadError(orderId, 'Only PDF files can be uploaded for vendor reports.');
                return;
            }

            if (file.size > MAX_REPORT_SIZE) {
                input.value = '';
                setUploadError(orderId, 'Each report must be 100MB or smaller.');
                return;
            }

            const preview = document.getElementById(previewId);
            const label   = document.getElementById(labelId);
            const name    = file.name.length > 24 ? file.name.slice(0, 21) + '...' : file.name;

            const colorMap = {
                red: {
                    text:         'text-red-400',
                    iconBg:       'bg-red-500/[0.12]',
                    iconBorder:   'border-red-500/[0.25]',
                    labelBorder:  'border-red-500/30',
                    labelBg:      'bg-red-500/[0.05]',
                },
                amber: {
                    text:         'text-amber-400',
                    iconBg:       'bg-amber-500/[0.12]',
                    iconBorder:   'border-amber-500/[0.25]',
                    labelBorder:  'border-amber-500/30',
                    labelBg:      'bg-amber-500/[0.05]',
                },
            };
            const c = colorMap[color];

            // Replace zone content with filename + ready indicator
            preview.innerHTML = `
                <p class="text-xs font-bold ${c.text} truncate">${name}</p>
                <p class="text-[10px] text-slate-400">Ready to submit</p>
            `;

            // Swap zone to "selected" state — solid border + tinted bg
            label.classList.remove('border-dashed', 'border-white/[0.08]', 'bg-white/[0.03]');
            label.classList.add(c.labelBorder, c.labelBg);

            checkUploadReady(orderId);
        }

        function toggleAiBypass(orderId, isSkipped) {
            const uploadContainer = document.getElementById('ai-upload-container-' + orderId);
            const reasonContainer = document.getElementById('ai-skip-reason-container-' + orderId);
            const aiInput = document.querySelector('#ai-label-' + orderId + ' input[type="file"]');
            
            if (isSkipped) {
                uploadContainer.classList.add('hidden');
                reasonContainer.classList.remove('hidden');
                if (aiInput) aiInput.value = '';
                
                const preview = document.getElementById('ai-preview-' + orderId);
                const label = document.getElementById('ai-label-' + orderId);
                if (preview) {
                    preview.innerHTML = `
                        <div class="w-12 h-12 bg-red-500/[0.08] rounded-2xl flex items-center justify-center border border-red-500/[0.15] group-hover:scale-105 transition-all">
                            <i data-lucide="file-scan" class="w-5 h-5 text-red-300"></i>
                        </div>
                        <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wider leading-tight">AI Report PDF</span>
                        <span class="text-[10px] text-slate-500 leading-relaxed">Click to upload PDF</span>
                    `;
                }
                if (label) {
                    label.classList.add('border-dashed', 'border-white/[0.08]', 'bg-white/[0.03]');
                    label.classList.remove('border-red-500/30', 'bg-red-500/[0.05]');
                }
                if (window.lucide && lucide.createIcons) lucide.createIcons();
            } else {
                uploadContainer.classList.remove('hidden');
                reasonContainer.classList.add('hidden');
                document.getElementById('ai-skip-reason-input-' + orderId).value = '';
            }
            checkUploadReady(orderId);
        }

        function checkUploadReady(orderId) {
            const aiInput      = document.querySelector('#ai-label-' + orderId + ' input[type="file"]');
            const plagInput    = document.querySelector('#plag-label-' + orderId + ' input[type="file"]');
            const skipCheckbox = document.getElementById('ai-skipped-' + orderId);
            const reasonInput  = document.getElementById('ai-skip-reason-input-' + orderId);
            const bar          = document.getElementById('progress-' + orderId);
            const btn          = document.getElementById('submit-btn-' + orderId);
            
            let aiReady   = false;
            let plagReady = false;

            if (skipCheckbox && skipCheckbox.checked) {
                if (reasonInput && reasonInput.value.trim().length > 0) aiReady = true;
            } else {
                if (aiInput && aiInput.files && aiInput.files.length > 0) aiReady = true;
            }

            if (plagInput && plagInput.files && plagInput.files.length > 0) plagReady = true;

            if (aiReady && plagReady) {
                if (bar) { bar.classList.remove('hidden'); bar.classList.add('flex'); }
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> Submit Reports';
                }
            } else {
                if (bar) { bar.classList.add('hidden'); bar.classList.remove('flex'); }
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> Select Required Files';
                }
            }
            if (window.lucide && lucide.createIcons) lucide.createIcons();
        }

        function submitUploadForm(orderId) {
            const modal       = document.getElementById('upload-modal-' + orderId);
            const form        = modal.querySelector('form');
            const submitBtn   = document.getElementById('submit-btn-' + orderId);
            const cancelBtn   = document.getElementById('cancel-btn-' + orderId);
            const readyStrip  = document.getElementById('progress-' + orderId);
            const progressBar = document.getElementById('upload-progress-' + orderId);
            const fill        = document.getElementById('upload-progress-fill-' + orderId);
            const pctText     = document.getElementById('upload-progress-text-' + orderId);

            if (submitBtn.disabled) return;

            clearUploadError(orderId);

            // Lock the UI
            submitBtn.disabled = true;
            cancelBtn.disabled = true;
            submitBtn.innerHTML = 'Uploading...';
            readyStrip.classList.add('hidden');
            progressBar.classList.remove('hidden');
            progressBar.classList.add('flex');

            // Refresh CSRF token first to handle long-lived sessions
            fetch(CSRF_REFRESH_URL)
                .then(r => r.json())
                .then(data => {
                    const tokenField = form.querySelector('input[name="_token"]');
                    if (tokenField) tokenField.value = data.token;
                })
                .catch(() => { /* proceed with existing token on fetch failure */ })
                .finally(() => {
                    const xhr = new XMLHttpRequest();

                    xhr.upload.onprogress = function (e) {
                        if (!e.lengthComputable) return;
                        const pct = Math.round((e.loaded / e.total) * 100);
                        fill.style.width    = pct + '%';
                        pctText.textContent = pct + '%';
                    };

                    xhr.onload = function () {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            // Try to parse as JSON (our AJAX handler response)
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.error) {
                                    setUploadError(orderId, data.error);
                                    resetUploadUi(orderId);
                                    return;
                                }
                                // Success - stash the message for display after redirect
                                try { if (data.success) sessionStorage.setItem('upload_success', data.success); } catch (_) {}
                                window.location.href = data.redirect || DASHBOARD_URL;
                                return;
                            } catch (e) {
                                // Not JSON - XHR followed a normal redirect, navigate to final URL
                            }
                            window.location.href = DASHBOARD_URL;
                        } else {
                            // HTTP 4xx / 5xx - re-enable the form and show an inline error
                            resetUploadUi(orderId);

                            let msg = 'Upload failed. Please try again.';
                            if (xhr.status === 419) {
                                msg = 'Session expired — please refresh the page and try again.';
                            } else if (xhr.status === 422) {
                                try {
                                    const d = JSON.parse(xhr.responseText);
                                    msg = d.error || d.message || (d.errors && Object.values(d.errors)[0]?.[0]) || msg;
                                } catch (e) {}
                            } else if (xhr.status === 403) {
                                msg = 'You are not authorized to upload for this order.';
                            } else if (xhr.status === 413) {
                                msg = 'One of the files is too large for the server to accept. Please upload smaller PDFs.';
                            } else if (xhr.status >= 500) {
                                msg = 'Server error while saving reports. Please try again in a moment.';
                            }
                            setUploadError(orderId, msg);
                        }
                    };

                    xhr.onerror = function () {
                        resetUploadUi(orderId);
                            setUploadError(orderId, 'Network or storage connection error. Please try again.');
                    };

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send(new FormData(form));
                });
        }

    </script>

    <script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Fade and remove every DOM element that belongs to a given order id.
// Uses querySelectorAll so both the hidden mobile card and the visible
// desktop <tr> are removed in one pass - querySelector only finds the first.
function fadeRemoveOrder(orderId) {
    const seen = new Set();
    document.querySelectorAll('[data-order-id="' + orderId + '"]').forEach(function (el) {
        const row = el.closest('tr')
            || (el.classList.contains('order-card') ? el : null)
            || el.closest('.order-card')
            || el.closest('div[class*="border"]')
            || el.parentElement?.closest('div');
        if (row && !seen.has(row)) {
            seen.add(row);
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(function () { row.remove(); }, 300);
        }
    });
}

function ajaxAction(url, btn, type, orderId, status = null) {
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>';

    const body = status ? JSON.stringify({ status }) : null;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(status ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (type === 'claim') {
                fadeRemoveOrder(orderId);

                const poolEl = document.querySelector('[data-stat="available_pool"]');
                if (poolEl) { const c = parseInt(poolEl.textContent)||0; if(c>0) poolEl.textContent = c-1; }
                const activeEl = document.querySelector('[data-stat="active_jobs"]');
                if (activeEl) { const c = parseInt(activeEl.textContent)||0; activeEl.textContent = c+1; }

                const isMobile = window.innerWidth < 640;

                if (!isMobile && data.rowHtml) {
                    const table = document.querySelector('#workspace table');
                    const tbody = table?.querySelector('tbody') || table;
                    if (tbody) {
                        const emptyRow = tbody.querySelector('td[colspan]')?.closest('tr');
                        if (emptyRow) emptyRow.remove();
                        tbody.insertAdjacentHTML('afterbegin', data.rowHtml);
                    }
                    const badge = document.querySelector('.workspace-count-badge');
                    if (badge) { const c = parseInt(badge.textContent||'0')||0; badge.textContent = c+1; badge.classList.remove('hidden'); }
                    if (window.lucide) lucide.createIcons();
                    showToast(data.message, 'success');

                } else if (isMobile && data.cardHtml) {
                    const cardScroll = document.querySelector('#workspace .flex.flex-col.gap-3');
                    if (cardScroll) {
                        const emptyState = document.querySelector('#workspace .rounded-2xl.border.border-dashed');
                        if (emptyState) emptyState.closest('div').remove();
                        cardScroll.insertAdjacentHTML('afterbegin', data.cardHtml);
                    }
                    const badge = document.querySelector('.workspace-count-badge');
                    if (badge) { const c = parseInt(badge.textContent||'0')||0; badge.textContent = c+1; badge.classList.remove('hidden'); }
                    if (window.lucide) lucide.createIcons();
                    showToast(data.message, 'success');

                } else {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                }
            }

            if (type === 'unclaim') {
                // Remove ALL elements (mobile card + desktop tr) with this order id
                fadeRemoveOrder(orderId);
                showToast(data.message, 'success');

                // Update counters instantly
                const activeEl = document.querySelector('[data-stat="active_jobs"]');
                if (activeEl) { const c = parseInt(activeEl.textContent) || 0; if (c > 0) activeEl.textContent = c - 1; }
                const poolEl = document.querySelector('[data-stat="available_pool"]');
                if (poolEl) { const c = parseInt(poolEl.textContent) || 0; poolEl.textContent = c + 1; }

                // Refresh the available queue so released order appears immediately
                setTimeout(() => refreshAvailableQueue(), 400);
            }

        } else {
            btn.disabled = false;
            btn.innerHTML = original;
            showToast(data.message || 'Something went wrong.', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = original;
        showToast('Network error. Please try again.', 'error');
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 rounded-2xl text-sm font-semibold shadow-2xl transition-all ' +
        (type === 'success'
            ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400'
            : 'bg-red-500/10 border border-red-500/20 text-red-400');
    toast.style.transform = 'translateX(-50%)';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}
    </script>

</x-vendor-layout>
