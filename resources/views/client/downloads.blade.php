<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloads - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        body { font-family: 'Outfit', 'Inter', sans-serif; }

        .card {
            background: #0f0f14;
            border: 1px solid rgba(255,255,255,0.055);
        }

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
    </style>
</head>

<body class="h-screen flex bg-[#070709] text-slate-400 overflow-hidden overflow-x-hidden">

    {{-- DESKTOP SIDEBAR --}}
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
            <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-200 hover:bg-white/[0.04] transition-all">
                <i data-lucide="layout-grid" class="w-4 h-4 flex-shrink-0"></i>
                Dashboard
            </a>
            <a href="{{ route('client.downloads') }}" class="sidebar-active flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                <i data-lucide="download" class="w-4 h-4 flex-shrink-0"></i>
                Downloads
            </a>
            <a href="{{ route('client.subscription') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-200 hover:bg-white/[0.04] transition-all">
                <i data-lucide="credit-card" class="w-4 h-4 flex-shrink-0"></i>
                Credits
            </a>
            <div class="flex items-center justify-between px-4 py-2.5 rounded-xl text-slate-600 cursor-not-allowed select-none text-sm font-medium">
                <div class="flex items-center gap-3">
                    <i data-lucide="settings" class="w-4 h-4 flex-shrink-0"></i>
                    Profile
                </div>
                <span class="text-[7px] font-black uppercase tracking-widest text-indigo-500/40 bg-indigo-500/[0.06] border border-indigo-500/[0.1] px-1.5 py-0.5 rounded">Soon</span>
            </div>
        </nav>

        <div class="px-3 pb-5 pt-2 border-t border-white/[0.05] mt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-[11px] font-bold text-red-400 bg-red-500/[0.08] hover:bg-red-500/[0.15] border border-red-500/[0.15] hover:border-red-500/[0.3] transition-all active:scale-[0.98]">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 overflow-y-auto bg-[#070709] scrollbar-thin">

        {{-- TOP HEADER --}}
        <header class="min-h-[56px] border-b border-white/[0.05] flex items-center justify-between px-4 sm:px-8 bg-[#070709]/80 backdrop-blur-xl sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <a href="{{ route('client.dashboard') }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-200 hover:bg-white/[0.06] transition-all md:hidden">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-[13px] sm:text-[15px] font-semibold text-white/90">Downloads</h1>
                    <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest hidden sm:block">Last 24 hours</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-[9px] font-mono text-indigo-400">{{ str_pad($client->id, 4, '0', STR_PAD_LEFT) }}</p>
                <div class="w-8 h-8 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 ring-1 ring-indigo-500/20">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
            </div>
        </header>

        <div class="px-3 py-4 pb-24 md:pb-8 max-w-[860px] mx-auto space-y-3 sm:px-6 sm:py-5">

            {{-- SESSION MESSAGES --}}
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-emerald-500/[0.16] bg-emerald-500/[0.05]">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                    <p class="text-[12px] font-medium text-emerald-200">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-red-500/[0.16] bg-red-500/[0.05]">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0"></i>
                    <p class="text-[12px] font-medium text-red-200">{{ session('error') }}</p>
                </div>
            @endif

            {{-- ORDER LIST --}}
            @forelse($orders as $order)

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
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Being processed — your report will appear here when ready</p>
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
                <div class="py-20 text-center">
                    <div class="w-16 h-16 bg-white/[0.03] rounded-2xl flex items-center justify-center mx-auto mb-5 border border-white/[0.05]">
                        <i data-lucide="download" class="w-7 h-7 text-slate-700"></i>
                    </div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.2em]">No active downloads</p>
                    <p class="text-[11px] text-slate-600 mt-1.5">Orders being processed or ready to download will appear here</p>
                    <a href="{{ route('client.dashboard') }}"
                        class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 rounded-xl text-[10px] font-bold text-indigo-400 bg-indigo-500/[0.08] border border-indigo-500/[0.15] hover:bg-indigo-500/[0.15] transition-all">
                        <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> New Order
                    </a>
                </div>
            @endforelse

        </div>
    </main>

    {{-- MOBILE BOTTOM NAV --}}
    <nav class="fixed bottom-0 left-0 right-0 z-30 md:hidden bg-[#09090c] border-t border-white/[0.06]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex items-center">
            <a href="{{ route('client.dashboard') }}"
               class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Home</span>
            </a>
            <a href="{{ route('client.downloads') }}"
               class="flex-1 flex flex-col items-center gap-1 py-3 text-indigo-400">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Downloads</span>
            </a>
            <a href="{{ route('client.subscription') }}"
               class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Credits</span>
            </a>
            <a href="{{ route('profile.edit') }}"
               class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Profile</span>
            </a>
        </div>
    </nav>

    <script>lucide.createIcons();</script>
</body>
</html>
