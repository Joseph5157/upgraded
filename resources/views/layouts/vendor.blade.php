<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — PlagExpert Agent</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #2a2a2a;
            border-radius: 99px;
        }

        .sidebar-link {
            transition: all 0.15s ease;
        }

        .sidebar-link.active,
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        .sidebar-link.active {
            color: #818cf8;
        }

        .sidebar-link.active .sidebar-icon {
            color: #818cf8;
        }

        .dark .sidebar-link.active {
            color: #a5b4fc;
            background: rgba(255, 255, 255, 0.04);
        }

        .dark .sidebar-link.active .sidebar-icon {
            color: #a5b4fc;
        }
    </style>
</head>

<body class="bg-[#0f1117] text-slate-300 antialiased dark:bg-[#0f1117]">

    <div class="flex h-screen overflow-hidden">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="w-[220px] flex-shrink-0 bg-[#13151c] border-r border-white/[0.06] flex flex-col h-full dark:bg-[#13151c] dark:border-white/[0.06]">

            {{-- Brand --}}
            <div class="px-5 py-5 border-b border-white/[0.06]">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 bg-indigo-600 rounded-xl flex items-center justify-center font-bold text-white text-sm">
                        T</div>
                    <div>
                        <p class="text-sm font-bold text-white leading-none dark:text-white">PlagExpert</p>
                        <p class="text-[9px] text-slate-500 mt-0.5 uppercase tracking-widest dark:text-slate-500">Agent Portal</p>
                    </div>
                </div>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest px-3 mb-3">Navigation</p>

                <a href="{{ route('dashboard') }}"
                    class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06] {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="sidebar-icon w-4 h-4 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-slate-500' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('dashboard') }}#workspace"
                    class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06]">
                    <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Workspace
                </a>

                <a href="{{ route('dashboard') }}#files"
                    class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06]">
                    <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Files
                </a>

                <a href="{{ route('dashboard') }}#history"
                    class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06]">
                    <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    History
                </a>

                <a href="{{ route('dashboard') }}#agents"
                    class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06]">
                    <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Agents
                </a>

                <a href="{{ route('vendor.earnings') }}"
                    class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06] {{ request()->routeIs('vendor.earnings') ? 'active' : '' }}">
                    <svg class="sidebar-icon w-4 h-4 flex-shrink-0 {{ request()->routeIs('vendor.earnings') ? 'text-indigo-400' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Earnings
                </a>

                <div class="pt-4 mt-2 border-t border-white/[0.06]">
                    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest px-3 mb-3">Account</p>
                    <a href="{{ route('profile.edit') }}"
                        class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.06] {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <svg class="sidebar-icon w-4 h-4 flex-shrink-0 {{ request()->routeIs('profile.*') ? 'text-indigo-400' : 'text-slate-500' }}" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </a>
                </div>
            </nav>

            {{-- User at bottom --}}
            <div class="px-4 py-4 border-t border-white/[0.06]">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 bg-indigo-600/20 rounded-xl flex items-center justify-center text-indigo-300 text-xs font-bold border border-indigo-600/20 flex-shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate dark:text-white">{{ auth()->user()->name }}</p>
                        <p class="text-[9px] text-slate-500 truncate dark:text-slate-500">Agent</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto flex-shrink-0">
                        @csrf
                        <button type="submit" class="text-slate-600 hover:text-red-400 transition-colors"
                            title="Log out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ===== MAIN AREA ===== --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top Bar --}}
            <header
                class="flex items-center justify-between px-8 py-4 bg-[#0f1117] border-b border-white/[0.06] flex-shrink-0 dark:bg-[#0f1117] dark:border-white/[0.06]">
                <div>
                    <h1 class="text-base font-bold text-white">{{ $title ?? 'Dashboard' }}</h1>
                    <p class="text-[10px] text-slate-500 mt-0.5">{{ now()->format('l, d M Y') }}</p>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Live Sync --}}
                    <div
                        class="hidden sm:flex items-center gap-1.5 bg-[#162a1f] border border-emerald-500/20 px-3 py-1.5 rounded-full dark:bg-[#162a1f] dark:border-emerald-500/20">
                        <span
                            class="w-1.5 h-1.5 bg-emerald-400 rounded-full shadow-[0_0_6px_#34d399] animate-pulse"></span>
                        <span class="text-[9px] text-emerald-400 font-bold uppercase tracking-widest">Live</span>
                    </div>

                    <x-dark-mode-toggle />

                    {{-- Notifications --}}
                    <button
                        class="relative w-8 h-8 bg-white/[0.04] border border-white/[0.06] rounded-xl flex items-center justify-center text-slate-400 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>

                    {{-- Profile menu --}}
                    <div class="group relative">
                        <button
                            class="flex items-center gap-2.5 bg-white/[0.04] border border-white/[0.06] rounded-xl px-3 py-1.5 hover:bg-white/[0.07] transition-all">
                            <div
                                class="w-6 h-6 bg-indigo-600/20 rounded-lg flex items-center justify-center text-indigo-300 text-[10px] font-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span
                                class="text-xs font-medium text-slate-300 hidden sm:block max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div
                            class="absolute right-0 top-full mt-2 w-48 bg-[#1c1e27] border border-white/10 rounded-2xl p-1.5 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 dark:bg-[#1c1e27] dark:border-white/10">
                            <a href="{{ route('profile.edit') }}"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-400 hover:bg-red-500/10 rounded-xl transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Scrollable content --}}
            <main class="flex-1 overflow-y-auto px-8 py-7 space-y-6 dark:bg-[#0f1117]">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons && lucide.createIcons();
    </script>
</body>

</html>