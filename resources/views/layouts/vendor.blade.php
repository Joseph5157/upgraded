@php
    $_vendor        = auth()->user();
    $_totalEarned   = \App\Models\VendorDailySnapshot::where('user_id', $_vendor->id)->sum('amount_earned');
    $_totalPaid     = \App\Models\VendorPayout::where('user_id', $_vendor->id)->sum('amount');
    $_pendingPayout = max(0, $_totalEarned - $_totalPaid);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <script>
        // Force dark mode as default
        document.documentElement.classList.add('dark');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — PlagExpert Agent</title>
    <link rel="icon" type="image/png" href="/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 99px; }
        .sidebar-link { transition: all 0.15s ease; }
        .sidebar-link.active,
        .sidebar-link:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .sidebar-link.active { color: #818cf8; }
        .sidebar-link.active .sidebar-icon { color: #818cf8; }
        .dark .sidebar-link.active { color: #a5b4fc; background: rgba(255,255,255,0.04); }
        .dark .sidebar-link.active .sidebar-icon { color: #a5b4fc; }

        /* Mobile drawer slide-in */
        #mobile-sidebar {
            transform: translateX(-100%);
            transition: transform 0.25s ease;
        }
        #mobile-sidebar.open {
            transform: translateX(0);
        }
        #sidebar-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }
        #sidebar-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body class="bg-[#0f1117] text-slate-300 antialiased dark:bg-[#0f1117] overflow-x-hidden">

    {{-- ===== MOBILE SIDEBAR OVERLAY ===== --}}
    <div id="sidebar-overlay"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden"
         onclick="closeSidebar()"></div>

    {{-- ===== MOBILE DRAWER SIDEBAR ===== --}}
    <aside id="mobile-sidebar"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-[#13151c] border-r border-white/[0.06] flex flex-col md:hidden">
        @include('layouts._sidebar-nav')
    </aside>

    {{-- ===== MAIN LAYOUT ===== --}}
    <div class="flex h-screen overflow-hidden overflow-x-hidden">

        {{-- ===== DESKTOP SIDEBAR (hidden on mobile) ===== --}}
        <aside class="hidden md:flex w-[220px] flex-shrink-0 bg-[#13151c] border-r border-white/[0.06] flex-col h-full dark:bg-[#13151c] dark:border-white/[0.06]">
            @include('layouts._sidebar-nav')
        </aside>

        {{-- ===== MAIN AREA ===== --}}
        <div class="flex-1 flex flex-col overflow-hidden overflow-x-hidden min-w-0 w-full">

            {{-- Top Bar --}}
            <header class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-3 sm:py-4 bg-[#0f1117] border-b border-white/[0.06] flex-shrink-0 dark:bg-[#0f1117] dark:border-white/[0.06]">

                {{-- Left: hamburger (mobile) + title --}}
                <div class="flex items-center gap-3 min-w-0">
                    {{-- Hamburger — mobile only --}}
                    <button onclick="openSidebar()"
                            class="md:hidden flex-shrink-0 w-8 h-8 bg-white/[0.04] border border-white/[0.06] rounded-xl flex items-center justify-center text-slate-400 hover:text-white transition-colors"
                            aria-label="Open menu">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-base font-bold text-white truncate">{{ $title ?? 'Dashboard' }}</h1>
                        <p class="text-[10px] text-slate-500 hidden sm:block">{{ now()->format('l, d M Y') }}</p>
                    </div>
                </div>

                {{-- Right: actions --}}
                <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                    {{-- Balance pill --}}
                    <a href="{{ route('vendor.earnings') }}"
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-[10px] font-bold uppercase tracking-widest transition-all
                              {{ $_pendingPayout > 0
                                  ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20'
                                  : 'bg-white/[0.03] border-white/[0.06] text-slate-500 hover:bg-white/[0.06]' }}">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/>
                        </svg>
                        <span>₹{{ number_format($_pendingPayout, 0) }}</span>
                    </a>

                    {{-- Live Sync --}}
                    <div class="hidden sm:flex items-center gap-1.5 bg-[#162a1f] border border-emerald-500/20 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full shadow-[0_0_6px_#34d399] animate-pulse"></span>
                        <span class="text-[9px] text-emerald-400 font-bold uppercase tracking-widest">Live</span>
                    </div>

                    {{-- Profile menu --}}
                    <div class="group relative">
                        <button class="flex items-center gap-2 bg-white/[0.04] border border-white/[0.06] rounded-xl px-2 sm:px-3 py-1.5 hover:bg-white/[0.07] transition-all">
                            <div class="w-6 h-6 bg-indigo-600/20 rounded-lg flex items-center justify-center text-indigo-300 text-[10px] font-bold flex-shrink-0">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="text-xs font-medium text-slate-300 hidden sm:block max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                            <svg class="w-3 h-3 text-slate-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 top-full mt-2 w-48 bg-[#1c1e27] border border-white/10 rounded-2xl p-1.5 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profile Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-400 hover:bg-red-500/10 rounded-xl transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Scrollable content --}}
            <main class="flex-1 overflow-y-auto overflow-x-hidden px-3 sm:px-5 lg:px-8 py-4 sm:py-6 lg:py-7 pb-20 md:pb-0 space-y-4 sm:space-y-6 dark:bg-[#0f1117] w-full min-w-0">

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div id="flash-success" class="flex items-center gap-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 text-xs font-semibold">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div id="flash-error" class="flex items-center gap-3 px-4 py-3 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-xs font-semibold">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        <nav class="fixed bottom-0 left-0 right-0 z-30 md:hidden bg-[#13151c] border-t border-white/[0.06]" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex-1 flex flex-col items-center gap-1 py-3 {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-slate-600' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Home</span>
                </a>
                <a href="{{ route('dashboard') }}#workspace" class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-600">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Work</span>
                </a>
                <a href="{{ route('dashboard') }}#files" class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-600">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Queue</span>
                </a>
                <a href="{{ route('vendor.earnings') }}" class="flex-1 flex flex-col items-center gap-1 py-3 {{ request()->routeIs('vendor.earnings') ? 'text-indigo-400' : 'text-slate-600' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Earnings</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex-1 flex flex-col items-center gap-1 py-3 {{ request()->routeIs('profile.*') ? 'text-indigo-400' : 'text-slate-600' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Profile</span>
                </a>
            </div>
        </nav>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons && lucide.createIcons();

        function openSidebar() {
            document.getElementById('mobile-sidebar').classList.add('open');
            document.getElementById('sidebar-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            document.getElementById('mobile-sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }
        // Close drawer when a nav link is tapped
        document.querySelectorAll('#mobile-sidebar a').forEach(function(el) {
            el.addEventListener('click', closeSidebar);
        });
        // Show success flash stored by XHR-based report uploads (sessionStorage avoids flash consumption bug)
        (function () {
            var msg = sessionStorage.getItem('upload_success');
            if (!msg) return;
            sessionStorage.removeItem('upload_success');
            var main = document.querySelector('main');
            if (!main) return;
            var el = document.createElement('div');
            el.className = 'flex items-center gap-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 text-xs font-semibold';
            el.innerHTML = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>' + msg + '</span>';
            main.insertBefore(el, main.firstChild);
        })();
        // CSRF token refresh — silently renew every 30 minutes so long sessions never 419
        function refreshCsrfToken() {
            fetch(@json(route('csrf.refresh')), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    document.querySelectorAll('input[name="_token"]').forEach(function(el) {
                        el.value = data.token;
                    });
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.token);
                })
                .catch(function() {});
        }
        setInterval(refreshCsrfToken, 30 * 60 * 1000);

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>

</html>
