<x-vendor-layout title="Dashboard">

    <div class="space-y-3">
        <x-announcements-banner />
    </div>

    @include('dashboard.partials.live')

    <script>
        const DASHBOARD_URL = @json(route('dashboard'));
        const LOGIN_URL = @json(route('login', ['expired' => 1]));
        const CSRF_REFRESH_URL = @json(route('csrf.refresh'));

        const MAX_REPORT_SIZE = 100 * 1024 * 1024;
        const VENDOR_PULSE_URL = @json(route('dashboard.pulse'));
        let currentSignature = @json($dashboardSignature ?? '');
        let refreshInProgress = false;

        function redirectToLogin(message = 'Your session expired. Please sign in again.') {
            window.location.replace(LOGIN_URL);
        }

        function refreshVendorDashboard() {
            if (document.hidden || refreshInProgress) return;

            refreshInProgress = true;

            fetch(VENDOR_PULSE_URL + '?signature=' + encodeURIComponent(currentSignature) + '&pulse=' + Date.now(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                cache: 'no-store',
            })
                .then(response => {
                    if (response.status === 401 || response.status === 419 || (response.redirected && response.url.includes('/login'))) {
                        redirectToLogin();
                        return null;
                    }

                    return response.json();
                })
                .then(data => {
                    if (!data) return;
                    if (data.signature) {
                        currentSignature = data.signature;
                    }
                    if (!data.liveHtml) return;

                    const liveEl = document.getElementById('vendor-dashboard-live');
                    if (!liveEl) return;

                    liveEl.outerHTML = data.liveHtml;
                    if (window.lucide && lucide.createIcons) lucide.createIcons();
                    hoistUploadModals();
                })
                .catch(() => {
                    // Ignore transient fetch errors; next polling tick will retry.
                })
                .finally(() => {
                    refreshInProgress = false;
                });
        }

        function startVendorPolling() {
            refreshVendorDashboard();
            window.setInterval(refreshVendorDashboard, 10000);
        }

        window.addEventListener('focus', refreshVendorDashboard);
        window.addEventListener('pageshow', refreshVendorDashboard);
        window.addEventListener('online', refreshVendorDashboard);

        startVendorPolling();


        function getUploadModalFromEl(orderId, el) {
            if (el && typeof el.closest === 'function') {
                const modal = el.closest('[data-upload-modal="' + orderId + '"]');
                if (modal) return modal;
            }
            return document.querySelector('[data-upload-modal="' + orderId + '"]:not(.hidden)')
                || document.querySelector('[data-upload-modal="' + orderId + '"]')
                || document.getElementById('upload-modal-' + orderId);
        }

        function openUploadModal(orderId, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            if (modal) modal.classList.remove('hidden');
        }

        function closeUploadModal(orderId, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            if (modal) modal.classList.add('hidden');
        }

        function setUploadError(orderId, message, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            const errStrip = modal ? modal.querySelector('#error-strip-' + orderId) : document.getElementById('error-strip-' + orderId);
            const errMsg   = modal ? modal.querySelector('#error-msg-' + orderId) : document.getElementById('error-msg-' + orderId);
            if (!errStrip || !errMsg) return;
            errMsg.textContent = message;
            errStrip.classList.remove('hidden');
            errStrip.classList.add('flex');
        }

        function clearUploadError(orderId, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            const errStrip = modal ? modal.querySelector('#error-strip-' + orderId) : document.getElementById('error-strip-' + orderId);
            if (!errStrip) return;
            errStrip.classList.add('hidden');
            errStrip.classList.remove('flex');
        }

        function resetUploadUi(orderId, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            const submitBtn   = modal ? modal.querySelector('#submit-btn-' + orderId) : document.getElementById('submit-btn-' + orderId);
            const cancelBtn   = modal ? modal.querySelector('#cancel-btn-' + orderId) : document.getElementById('cancel-btn-' + orderId);
            const readyStrip  = modal ? modal.querySelector('#progress-' + orderId) : document.getElementById('progress-' + orderId);
            const progressBar = modal ? modal.querySelector('#upload-progress-' + orderId) : document.getElementById('upload-progress-' + orderId);

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

            clearUploadError(orderId, input);

            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            if (!isPdf) {
                input.value = '';
                setUploadError(orderId, 'Only PDF files can be uploaded for vendor reports.', input);
                checkUploadReady(orderId, input);
                return;
            }

            if (file.size > MAX_REPORT_SIZE) {
                input.value = '';
                setUploadError(orderId, 'Each report must be 100MB or smaller.', input);
                checkUploadReady(orderId, input);
                return;
            }

            const modal = getUploadModalFromEl(orderId, input);
            const preview = modal ? modal.querySelector('#' + previewId) : document.getElementById(previewId);
            const label   = modal ? modal.querySelector('#' + labelId) : document.getElementById(labelId);
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

            checkUploadReady(orderId, input);
        }

        function toggleAiBypass(orderId, isSkipped, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            const uploadContainer = modal ? modal.querySelector('#ai-upload-container-' + orderId) : document.getElementById('ai-upload-container-' + orderId);
            const reasonContainer = modal ? modal.querySelector('#ai-skip-reason-container-' + orderId) : document.getElementById('ai-skip-reason-container-' + orderId);
            const aiInput = modal ? modal.querySelector('#ai-label-' + orderId + ' input[type="file"]') : document.querySelector('#ai-label-' + orderId + ' input[type="file"]');
            
            if (isSkipped) {
                uploadContainer.classList.add('hidden');
                reasonContainer.classList.remove('hidden');
                if (aiInput) aiInput.value = '';
                
                const preview = modal ? modal.querySelector('#ai-preview-' + orderId) : document.getElementById('ai-preview-' + orderId);
                const label = modal ? modal.querySelector('#ai-label-' + orderId) : document.getElementById('ai-label-' + orderId);
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
                const reasonInput = modal ? modal.querySelector('#ai-skip-reason-input-' + orderId) : document.getElementById('ai-skip-reason-input-' + orderId);
                if (reasonInput) reasonInput.value = '';
            }
            checkUploadReady(orderId, el);
        }

        function checkUploadReady(orderId, el = null) {
            const modal = getUploadModalFromEl(orderId, el);
            const aiInput      = modal ? modal.querySelector('#ai-label-' + orderId + ' input[type="file"]') : document.querySelector('#ai-label-' + orderId + ' input[type="file"]');
            const plagInput    = modal ? modal.querySelector('#plag-label-' + orderId + ' input[type="file"]') : document.querySelector('#plag-label-' + orderId + ' input[type="file"]');
            const skipCheckbox = modal ? modal.querySelector('#ai-skipped-' + orderId) : document.getElementById('ai-skipped-' + orderId);
            const reasonInput  = modal ? modal.querySelector('#ai-skip-reason-input-' + orderId) : document.getElementById('ai-skip-reason-input-' + orderId);
            const bar          = modal ? modal.querySelector('#progress-' + orderId) : document.getElementById('progress-' + orderId);
            const btn          = modal ? modal.querySelector('#submit-btn-' + orderId) : document.getElementById('submit-btn-' + orderId);
            
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

        // Move every upload modal to <body> so it is never trapped inside a
        // display:none responsive container (sm:hidden / hidden sm:block).
        // Duplicates (mobile card + desktop row render the same modal ID) are
        // deduplicated by keeping only the first occurrence.
        function hoistUploadModals() {
            const seen = new Set();
            document.querySelectorAll('[id^="upload-modal-"]').forEach(function (modal) {
                if (seen.has(modal.id)) {
                    modal.remove();
                } else {
                    seen.add(modal.id);
                    if (modal.parentElement !== document.body) {
                        document.body.appendChild(modal);
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', hoistUploadModals);

        function submitUploadForm(orderId, el = null) {
            const modal       = getUploadModalFromEl(orderId, el);
            if (!modal) return;
            const form        = modal.querySelector('form');
            const submitBtn   = modal.querySelector('#submit-btn-' + orderId);
            const cancelBtn   = modal.querySelector('#cancel-btn-' + orderId);
            const readyStrip  = modal.querySelector('#progress-' + orderId);
            const progressBar = modal.querySelector('#upload-progress-' + orderId);
            const fill        = modal.querySelector('#upload-progress-fill-' + orderId);
            const pctText     = modal.querySelector('#upload-progress-text-' + orderId);

            if (submitBtn.disabled) return;

            clearUploadError(orderId, el);

            // Lock the UI
            submitBtn.disabled = true;
            cancelBtn.disabled = true;
            submitBtn.innerHTML = 'Uploading...';
            readyStrip.classList.add('hidden');
            progressBar.classList.remove('hidden');
            progressBar.classList.add('flex');

            // Seed with whatever is currently in the meta tag (kept fresh by the
            // 30-minute auto-refresh).  The fetch below will overwrite this with
            // the latest server-issued token when it succeeds.
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Refresh CSRF token first to handle long-lived sessions
            fetch(CSRF_REFRESH_URL)
                .then(r => r.json())
                .then(data => {
                    csrfToken = data.token;
                    const tokenField = form.querySelector('input[name="_token"]');
                    if (tokenField) tokenField.value = csrfToken;
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', csrfToken);
                })
                .catch(() => { /* proceed with existing token on fetch failure */ })
                .finally(() => {
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData(form);
                    const aiInput = modal.querySelector('#ai-label-' + orderId + ' input[type="file"]');
                    const plagInput = modal.querySelector('#plag-label-' + orderId + ' input[type="file"]');

                    if (aiInput && aiInput.files && aiInput.files.length > 0 && !formData.has('ai_report')) {
                        formData.delete('ai_report');
                        formData.append('ai_report', aiInput.files[0], aiInput.files[0].name);
                    }

                    if (plagInput && plagInput.files && plagInput.files.length > 0 && !formData.has('plag_report')) {
                        formData.delete('plag_report');
                        formData.append('plag_report', plagInput.files[0], plagInput.files[0].name);
                    }

                    if (!formData.has('plag_report')) {
                        resetUploadUi(orderId, el);
                        setUploadError(orderId, 'Please re-select the plagiarism report PDF and try again.', el);
                        return;
                    }

                    xhr.upload.onprogress = function (e) {
                        if (!e.lengthComputable) return;
                        const pct = Math.round((e.loaded / e.total) * 100);
                        fill.style.width    = pct + '%';
                        pctText.textContent = pct + '%';
                    };

                    xhr.onload = function () {
                        if (xhr.status === 419) {
                            let payload = null;
                            try { payload = JSON.parse(xhr.responseText); } catch (e) {}
                            window.location.replace((payload && payload.redirect) || LOGIN_URL);
                            return;
                        }

                        if (xhr.status >= 200 && xhr.status < 300) {
                            // Try to parse as JSON (our AJAX handler response)
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.error) {
                                    setUploadError(orderId, data.error, el);
                                    resetUploadUi(orderId, el);
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
                            resetUploadUi(orderId, el);

                            let msg = 'Upload failed. Please try again.';
                            let payload = null;

                            try {
                                payload = JSON.parse(xhr.responseText);
                            } catch (e) {}

                            if (xhr.status === 419) {
                                msg = 'Session expired — please refresh the page and try again.';
                            } else if (payload) {
                                msg = payload.error
                                    || (payload.errors && Object.values(payload.errors).reduce((all, list) => all.concat(list), []).find(Boolean))
                                    || payload.message
                                    || msg;
                            } else if (xhr.status === 403) {
                                msg = 'You are not authorized to upload for this order.';
                            } else if (xhr.status === 413) {
                                msg = 'One of the files is too large for the server to accept. Please upload smaller PDFs.';
                            } else if (xhr.status >= 500) {
                                msg = 'Server error while saving reports. Please try again in a moment.';
                            }
                            setUploadError(orderId, msg, el);
                        }
                    };

                    xhr.onerror = function () {
                        resetUploadUi(orderId, el);
                        setUploadError(orderId, 'Network or storage connection error. Please try again.', el);
                    };

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'application/json');
                    // Send the token both as a header (most reliable) and in the
                    // FormData body (_token field updated above) so Laravel's CSRF
                    // middleware can match it via either path.
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    xhr.send(formData);
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
    .then(r => {
        if (r.status === 401 || r.status === 419 || (r.redirected && r.url.includes('/login'))) {
            redirectToLogin();
            throw Object.assign(new Error('session_expired'), { isAuth: true });
        }

        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            // Middleware returned a redirect (session expired, role mismatch) — HTML, not JSON.
            const isAuth = r.url && r.url.includes('/login');
            throw Object.assign(new Error('non_json'), { isAuth });
        }
        return r.json();
    })
            .then(data => {
        if (data.success) {
            if (type === 'claim') {
                fadeRemoveOrder(orderId);

                const poolEl = document.querySelector('[data-stat="available_pool"]');
                if (poolEl) { const c = parseInt(poolEl.textContent)||0; if(c>0) poolEl.textContent = c-1; }
                const activeEl = document.querySelector('[data-stat="active_jobs"]');
                if (activeEl) { const c = parseInt(activeEl.textContent)||0; activeEl.textContent = c+1; }

                if (data.workspaceHtml) {
                    const workspaceEl = document.querySelector('#workspace');
                    if (workspaceEl) {
                        workspaceEl.outerHTML = data.workspaceHtml;
                    }
                    if (window.lucide) lucide.createIcons();
                    hoistUploadModals();
                    showToast(data.message, 'success');
                    setTimeout(() => refreshVendorDashboard(), 400);
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

                setTimeout(() => refreshVendorDashboard(), 400);
            }

        } else {
            btn.disabled = false;
            btn.innerHTML = original;
            showToast(data.message || 'Something went wrong.', 'error');
        }
    })
    .catch((err) => {
        btn.disabled = false;
        btn.innerHTML = original;
        if (err && err.isAuth) {
            return;
        }
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
