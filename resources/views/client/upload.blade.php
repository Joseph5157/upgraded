<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $client->name }} â€” {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { -webkit-font-smoothing: antialiased; }
        body { font-family: 'Outfit', 'Inter', sans-serif; }

        .card {
            background: #0f0f14;
            border: 1px solid rgba(255,255,255,0.055);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover { border-color: rgba(99,102,241,0.18); }

        .sidebar-active {
            background: rgba(99,102,241,0.12);
            color: #fff;
            border-left: 2px solid #818cf8;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); border-radius: 99px; }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

        .drag-over { border-color: rgba(99,102,241,0.6) !important; background: rgba(99,102,241,0.08) !important; }
    </style>
</head>

<body class="h-screen flex bg-[#070709] text-slate-400 overflow-hidden overflow-x-hidden">
    <aside class="hidden md:flex w-[220px] flex-shrink-0 h-full border-r border-white/[0.05] flex-col bg-[#0b0b0f]">
        <div class="px-5 pt-6 pb-8">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30 flex-shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
                </div>
                <span class="font-bold text-white text-[15px] tracking-tight">{{ config('app.name') }}</span>
            </div>
        </div>

        <nav class="flex-1 px-2 space-y-0.5">
            <a href="#" class="sidebar-active flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                <i data-lucide="layout-grid" class="w-4 h-4 flex-shrink-0"></i>
                Dashboard
            </a>
        </nav>

        @php $remaining = max(0, $client->slots - $client->slots_consumed); @endphp
        <div class="px-5 pb-6 pt-2 border-t border-white/[0.05] mt-2">
            <p class="text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em]">{{ $client->name }}</p>
            <p id="guest-link-sidebar-remaining" class="text-[10px] font-mono mt-0.5 {{ $remaining > 10 ? 'text-emerald-400' : ($remaining > 0 ? 'text-amber-400' : 'text-red-400') }}">{{ $remaining }} slots remaining</p>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-[#070709] scrollbar-thin">
        <header class="min-h-[56px] border-b border-white/[0.05] flex items-center justify-between px-4 sm:px-8 py-2 bg-[#070709]/80 backdrop-blur-xl sticky top-0 z-20">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <div class="md:hidden w-7 h-7 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-white"></i>
                </div>
                <h1 class="text-[13px] sm:text-[15px] font-semibold text-white/90 truncate">Welcome, {{ $client->name }}</h1>
            </div>
            <div class="flex items-center gap-3 ml-2">
                <div class="hidden sm:block text-right">
                    <p class="text-[9px] text-slate-600 font-bold uppercase tracking-[0.2em]">Credits</p>
                    <p id="guest-link-header-remaining" class="text-[11px] font-mono font-bold mt-0.5 {{ $remaining > 10 ? 'text-emerald-400' : ($remaining > 0 ? 'text-amber-400' : 'text-red-400') }}">{{ $remaining }} left</p>
                </div>
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 ring-1 ring-indigo-500/20">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
            </div>
        </header>

        @include('client.upload.partials.live', [
            'link' => $link,
            'client' => $client,
            'orders' => $orders,
            'pulseUrl' => $pulseUrl,
            'signature' => $signature,
        ])

        <footer class="px-8 py-6 text-center border-t border-white/[0.04] bg-[#0b0b0f] mt-4">
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.3em]">{{ config('app.name') }} &bull; Advanced plagiarism review</p>
        </footer>
    </main>

    <script>
        lucide.createIcons();

        function handleFileSelect(input) {
            const files = Array.from(input.files);
            const count = files.length;
            const stage = document.getElementById('upload-stage');
            const countEl = document.getElementById('selected-file-count');
            const preview = document.getElementById('file-preview');
            if (count === 0) { resetUpload(); return; }
            countEl.textContent = count + ' file' + (count > 1 ? 's' : '') + ' selected';
            countEl.classList.remove('hidden');
            stage.classList.remove('hidden');
            preview.innerHTML = '';
            files.forEach(file => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 px-4 py-3';
                div.innerHTML = `
                    <div class="w-7 h-7 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[12px] font-semibold text-white truncate">${file.name}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>`;
                preview.appendChild(div);
            });
        }

        function resetUpload() {
            const input = document.getElementById('files');
            if (input) {
                input.value = '';
            }
            document.getElementById('upload-stage')?.classList.add('hidden');
            document.getElementById('selected-file-count')?.classList.add('hidden');
            const preview = document.getElementById('file-preview');
            if (preview) {
                preview.innerHTML = '';
            }
        }

        window.__wireGuestLinkUploadControls = function () {
            const zone = document.getElementById('drop-zone');
            if (zone && zone.dataset.guestLinkWired !== '1') {
                zone.dataset.guestLinkWired = '1';
                zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
                zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
                zone.addEventListener('drop', e => {
                    e.preventDefault();
                    zone.classList.remove('drag-over');
                    const input = document.getElementById('files');
                    if (input && e.dataTransfer.files.length) {
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(e.dataTransfer.files[0]);
                        input.files = dataTransfer.files;
                        handleFileSelect(input);
                    }
                });
            }

            const form = document.getElementById('upload-form');
            if (form && form.dataset.guestLinkSubmitWired !== '1') {
                form.dataset.guestLinkSubmitWired = '1';
                form.addEventListener('submit', function () {
                    const btn = document.getElementById('upload-submit-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Uploading...';
                    }
                });
            }
        };

        function syncGuestLinkCreditDisplays() {
            const liveEl = document.getElementById('guest-link-live');
            if (!liveEl) {
                return;
            }

            const remaining = Number(liveEl.dataset.guestLinkRemaining || 0);
            const header = document.getElementById('guest-link-header-remaining');
            const sidebar = document.getElementById('guest-link-sidebar-remaining');
            const toneClasses = ['text-emerald-400', 'text-amber-400', 'text-red-400'];
            const tone = remaining > 10 ? 'text-emerald-400' : (remaining > 0 ? 'text-amber-400' : 'text-red-400');

            if (header) {
                header.classList.remove(...toneClasses);
                header.classList.add(tone);
                header.textContent = `${remaining} left`;
            }

            if (sidebar) {
                sidebar.classList.remove(...toneClasses);
                sidebar.classList.add(tone);
                sidebar.textContent = `${remaining} slots remaining`;
            }
        }

        window.__wireGuestLinkUploadControls();
        syncGuestLinkCreditDisplays();

        window.__guestLinkPolling = window.__guestLinkPolling || {};
        (function () {
            const pollIntervalMs = 12000;
            let pollTimer = null;
            let inFlight = false;

            async function checkForGuestLinkUpdates() {
                if (inFlight) {
                    return;
                }

                const liveEl = document.getElementById('guest-link-live');
                if (!liveEl) {
                    return;
                }

                const pulseUrl = liveEl.dataset.pulseUrl;
                const signature = liveEl.dataset.pulseSignature || '';
                inFlight = true;

                try {
                    const response = await fetch(`${pulseUrl}?signature=${encodeURIComponent(signature)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.status === 404) {
                        stopGuestLinkPolling();
                        window.location.reload();
                        return;
                    }

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (payload.liveHtml) {
                        const current = document.getElementById('guest-link-live');
                        if (current) {
                            current.outerHTML = payload.liveHtml;
                            lucide.createIcons();
                            window.__wireGuestLinkUploadControls();
                            syncGuestLinkCreditDisplays();
                        }
                    }
                } catch (error) {
                    // Ignore transient polling failures; the next tick will retry.
                } finally {
                    inFlight = false;
                }
            }

            function startGuestLinkPolling() {
                if (pollTimer !== null) {
                    return;
                }

                checkForGuestLinkUpdates();
                pollTimer = window.setInterval(checkForGuestLinkUpdates, pollIntervalMs);
            }

            function stopGuestLinkPolling() {
                if (pollTimer !== null) {
                    window.clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            window.__guestLinkPolling.start = startGuestLinkPolling;
            window.__guestLinkPolling.stop = stopGuestLinkPolling;

            window.addEventListener('focus', checkForGuestLinkUpdates);
            window.addEventListener('pageshow', checkForGuestLinkUpdates);
            window.addEventListener('online', checkForGuestLinkUpdates);

            startGuestLinkPolling();
        })();
    </script>
</body>
</html>
