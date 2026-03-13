<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        // Force dark mode as default
        document.documentElement.classList.add('dark');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin &mdash; {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary:   '#4F6EF7',
                        surface:   '#F0F2F5',
                        base:      '#FAFBFC',
                        ink:       '#1A1D23',
                        muted:     '#9CA3AF',
                        border:    '#E2E6EA',
                        subtle:    '#ECEEF2',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.08);
            border-radius: 2px;
        }

        .nav-group-label {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #9CA3AF;
            padding: 0 0.5rem;
            margin-bottom: 0.35rem;
        }
        .dark .nav-group-label {
            color: #3a3a4a;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6B7280;
            transition: all 0.15s;
            border: 1px solid transparent;
            text-decoration: none;
            width: 100%;
        }

        .nav-link:hover {
            background: #ECEEF2;
            color: #111827;
        }

        .nav-link.active {
            background: #EEF2FF;
            color: #4F6EF7;
            font-weight: 700;
            border-color: transparent;
        }

        .dark .nav-link {
            color: #64748b;
        }
        .dark .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }
        .dark .nav-link.active {
            background: rgba(99, 102, 241, 0.12);
            color: #818cf8;
            border-color: transparent;
        }
    </style>
</head>

<body class="bg-[#F0F2F5] antialiased dark:bg-[#050505] overflow-x-hidden">
    <div class="flex h-screen overflow-hidden overflow-x-hidden">

        {{-- 1. STICKY SIDEBAR --}}
        <aside class="hidden md:flex w-64 flex-shrink-0 bg-[#F7F8FA] border-r border-[#E2E6EA] flex-col dark:bg-[#0a0a0c] dark:border-white/5">

            {{-- Brand --}}
            <div class="p-6 border-b border-[#E2E6EA] dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-8 bg-[#4F6EF7] rounded-full"></div>
                    <span class="text-sm font-extrabold text-gray-900 tracking-wide uppercase dark:text-white">Control Center</span>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto p-4 space-y-6 py-6 custom-scrollbar">

                {{-- Operations --}}
                <div class="space-y-0.5">
                    <p class="nav-group-label">Operations</p>
                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 flex-shrink-0"></i> Dashboard
                    </a>
                    <a href="{{ route('admin.matrix.index') }}"
                        class="nav-link {{ request()->routeIs('admin.matrix.*') ? 'active' : '' }}">
                        <i data-lucide="users" class="w-3.5 h-3.5 flex-shrink-0"></i> Credit Manager
                    </a>
                    <a href="{{ route('admin.billing.index') }}"
                        class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 flex-shrink-0"></i> Financial Matrix
                    </a>
                    <a href="{{ route('admin.announcements.index') }}"
                        class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                        <i data-lucide="megaphone" class="w-3.5 h-3.5 flex-shrink-0"></i> Announcements
                    </a>
                </div>

                {{-- Finance & Matrix --}}
                <div class="space-y-0.5">
                    <p class="nav-group-label">Finance &amp; Matrix</p>
                    @php
                        $lowCreditCount = \App\Models\Client::withCount('orders')
                            ->get()
                            ->filter(fn($c) => ($c->slots - $c->orders_count) <= 0)
                            ->count();
                    @endphp
                    <a href="{{ route('admin.finance.matrix') }}"
                        class="nav-link {{ request()->routeIs('admin.finance.matrix') ? 'active' : '' }}">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Client Matrix</span>
                        @if($lowCreditCount > 0)
                            <span
                                class="text-[8px] font-black bg-red-500/15 text-red-400 border border-red-500/20 px-1.5 py-0.5 rounded-md leading-none">
                                {{ $lowCreditCount }} Low
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('admin.finance.ledger') }}"
                        class="nav-link {{ request()->routeIs('admin.finance.ledger') ? 'active' : '' }}">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5 flex-shrink-0"></i> Ledger History
                    </a>
                    <a href="{{ route('admin.finance.payouts.index') }}"
                        class="nav-link {{ request()->routeIs('admin.finance.payouts.*') ? 'active' : '' }}">
                        <i data-lucide="wallet" class="w-3.5 h-3.5 flex-shrink-0"></i> Vendor Payouts
                    </a>
                    <a href="{{ route('admin.refunds.index') }}"
                        class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                        <i data-lucide="refresh-ccw" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Refund Requests</span>
                        @php $pendingRefunds = \App\Models\RefundRequest::where('status','pending')->count(); @endphp
                        @if($pendingRefunds > 0)
                            <span class="text-[8px] font-black bg-amber-500/15 text-amber-400 border border-amber-500/20 px-1.5 py-0.5 rounded-md leading-none">
                                {{ $pendingRefunds }}
                            </span>
                        @endif
                    </a>
                </div>

                {{-- Management --}}
                <div class="space-y-0.5">
                    <p class="nav-group-label">Management</p>
                    <a href="{{ route('admin.dashboard') }}#create-account" class="nav-link">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 flex-shrink-0"></i> Issue Account
                    </a>
                    <a href="{{ route('admin.accounts.index') }}"
                        class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                        <i data-lucide="users" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Account Manager</span>
                        @php $_frozenCount = \App\Models\User::whereIn('role',['vendor','client'])->where('status','frozen')->count(); @endphp
                        @if($_frozenCount > 0)
                            <span class="text-[8px] font-black bg-red-500/15 text-red-400 border border-red-500/20 px-1.5 py-0.5 rounded-md leading-none">{{ $_frozenCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}"
                        class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i data-lucide="settings" class="w-3.5 h-3.5 flex-shrink-0"></i> Settings
                    </a>
                </div>

            </nav>

            {{-- Operator Section --}}
            <div class="p-4 border-t border-[#E2E6EA] bg-[#F0F2F5] dark:bg-[#0a0a0c] dark:border-white/5">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2 px-2 dark:text-slate-700">Operator</div>
                <div class="flex items-center gap-3 px-2 mb-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-red-600/20 text-red-500 flex items-center justify-center text-[10px] font-bold flex-shrink-0 dark:bg-red-500/20 dark:text-red-400">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 truncate">
                        <p class="text-xs font-bold text-gray-900 uppercase tracking-tight truncate dark:text-white">
                            {{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-bold text-red-500">SYSTEM_ROOT</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-semibold text-gray-500 hover:text-red-500 hover:bg-red-500/5 transition-all dark:text-slate-500 dark:hover:text-red-400 dark:hover:bg-red-500/10">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
                    </button>
                </form>
            </div>

        </aside>

        {{-- 2. INDEPENDENT SCROLLING CONTENT --}}
        <main class="flex-1 h-full overflow-y-auto overflow-x-hidden relative bg-[#F0F2F5] custom-scrollbar dark:bg-[#050505] w-full min-w-0">
            {{-- Mobile Header (visible only on mobile) --}}
            <div class="md:hidden sticky top-0 z-20 bg-[#F7F8FA] dark:bg-[#0a0a0c] border-b border-[#E2E6EA] dark:border-white/5 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-6 bg-[#4F6EF7] rounded-full"></div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">Admin</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative group">
                        <button class="w-8 h-8 rounded-lg bg-red-600/20 text-red-500 dark:bg-red-500/20 dark:text-red-400 flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </button>
                        <div class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#1a1a1c] border border-gray-200 dark:border-white/10 rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all p-1.5">
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg">
                                <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i> Dashboard
                            </a>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg">
                                <i data-lucide="settings" class="w-3.5 h-3.5"></i> Settings
                            </a>
                            <hr class="my-1.5 border-gray-200 dark:border-white/10">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg">
                                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-6 lg:p-10 max-w-[1600px] mx-auto space-y-6 sm:space-y-12">
                {{ $slot }}
            </div>
        </main>

    </div>
    <script>lucide.createIcons();</script>
</body>

</html>