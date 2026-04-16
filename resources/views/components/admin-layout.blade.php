<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Admin') }} — PlagExpert</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ── Sidebar nav link ── */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            transition: background 0.12s, color 0.12s;
            width: 100%;
        }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .nav-link.active { background: rgba(99,102,241,0.12); color: #818cf8; }
        .dark .nav-link:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .dark .nav-link.active { background: rgba(99,102,241,0.12); color: #818cf8; }

        /* Light mode overrides */
        @media (prefers-color-scheme: light) {
            .nav-link:hover { background: #ECEEF2; color: #111827; }
            .nav-link.active { background: #EEF2FF; color: #4F6EF7; }
        }

        .nav-group-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #334155;
            padding: 0 10px;
            margin-bottom: 4px;
        }
        .dark .nav-group-label { color: #334155; }

        /* badge pill inside nav items */
        .nav-badge {
            margin-left: auto;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            line-height: 1.4;
        }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }
    </style>
</head>

<body class="bg-[#F0F2F5] antialiased dark:bg-[#050505] overflow-x-hidden h-full">
<div class="flex h-screen overflow-hidden">

    {{-- ═══════════ SIDEBAR ═══════════ --}}
    <aside class="hidden md:flex w-56 flex-shrink-0 bg-[#0a0a0c] border-r border-white/5 flex-col">

        {{-- Brand --}}
        <div class="px-5 py-5 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0">P</div>
                <div>
                    <p class="text-sm font-bold text-white leading-none">PlagExpert</p>
                    <p class="text-[9px] text-slate-600 uppercase tracking-widest mt-0.5">Admin</p>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">

            {{-- Overview --}}
            <div>
                <p class="nav-group-label">Overview</p>
                <div class="space-y-0.5">
                    <a href="{{ route('admin.dashboard') }}"
                       class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.announcements.index') }}"
                       class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                        <i data-lucide="megaphone" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Announcements
                    </a>
                </div>
            </div>

            {{-- Clients --}}
            <div>
                <p class="nav-group-label">Clients</p>
                <div class="space-y-0.5">
                    {{-- Client accounts — with frozen badge --}}
                    @php
                        $frozenClients = Cache::remember('admin_nav_frozen_clients', 60, fn() =>
                            \App\Models\User::where('role','client')->where('status','frozen')->count()
                        );
                    @endphp
                    <a href="{{ route('admin.accounts.index') }}?tab=clients"
                       class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                        <i data-lucide="users" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Accounts</span>
                        @if($frozenClients > 0)
                            <span class="nav-badge bg-red-500/15 text-red-400 border border-red-500/20">{{ $frozenClients }}</span>
                        @endif
                    </a>

                    {{-- Credits & quotas --}}
                    @php
                        $lowCreditClients = Cache::remember('admin_nav_low_credits', 60, fn() =>
                            \App\Models\Client::whereRaw('slots_consumed >= slots')->count()
                        );
                    @endphp
                    <a href="{{ route('admin.matrix.index') }}"
                       class="nav-link {{ request()->routeIs('admin.matrix.*') ? 'active' : '' }}">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Credits</span>
                        @if($lowCreditClients > 0)
                            <span class="nav-badge bg-amber-500/15 text-amber-400 border border-amber-500/20">{{ $lowCreditClients }} low</span>
                        @endif
                    </a>

                    {{-- Topup requests --}}
                    @php
                        $pendingTopups = Cache::remember('admin_nav_pending_topups', 60, fn() =>
                            \App\Models\TopupRequest::where('status','pending')->count()
                        );
                    @endphp
                    <a href="{{ route('admin.topup.index') }}"
                       class="nav-link {{ request()->routeIs('admin.topup.*') ? 'active' : '' }}">
                        <i data-lucide="zap" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Top-ups</span>
                        @if($pendingTopups > 0)
                            <span class="nav-badge bg-amber-500/15 text-amber-400 border border-amber-500/20">{{ $pendingTopups }}</span>
                        @endif
                    </a>

                    {{-- Refund requests --}}
                    @php
                        $pendingRefunds = Cache::remember('admin_nav_pending_refunds', 60, fn() =>
                            \App\Models\RefundRequest::where('status','pending')->count()
                        );
                    @endphp
                    <a href="{{ route('admin.refunds.index') }}"
                       class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                        <i data-lucide="refresh-ccw" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Refunds</span>
                        @if($pendingRefunds > 0)
                            <span class="nav-badge bg-amber-500/15 text-amber-400 border border-amber-500/20">{{ $pendingRefunds }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Vendors --}}
            <div>
                <p class="nav-group-label">Vendors</p>
                <div class="space-y-0.5">
                    @php
                        $frozenVendors = Cache::remember('admin_nav_frozen_vendors', 60, fn() =>
                            \App\Models\User::where('role','vendor')->where('status','frozen')->count()
                        );
                    @endphp
                    <a href="{{ route('admin.accounts.index') }}?tab=vendors"
                       class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                        <i data-lucide="shield" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="flex-1">Accounts</span>
                        @if($frozenVendors > 0)
                            <span class="nav-badge bg-red-500/15 text-red-400 border border-red-500/20">{{ $frozenVendors }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.finance.payouts.index') }}"
                       class="nav-link {{ request()->routeIs('admin.finance.payouts.*') ? 'active' : '' }}">
                        <i data-lucide="wallet" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Payouts
                    </a>
                    <a href="{{ route('admin.billing.index') }}"
                       class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Billing & Ledger
                    </a>
                </div>
            </div>

        </nav>

        {{-- Admin profile + logout --}}
        <div class="px-3 py-4 border-t border-white/5">
            <a href="{{ route('profile.edit') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }} mb-2">
                <div class="w-6 h-6 rounded-md bg-indigo-600/20 text-indigo-400 flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-white truncate leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[9px] text-slate-600 mt-0.5">My profile</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="nav-link w-full text-red-500/70 hover:text-red-400 hover:bg-red-500/10">
                    <i data-lucide="log-out" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    Sign out
                </button>
            </form>
        </div>

    </aside>

    {{-- ═══════════ MOBILE HEADER ═══════════ --}}
    <div class="md:hidden fixed top-0 inset-x-0 z-30 bg-[#0a0a0c] border-b border-white/5 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-indigo-600 rounded-md flex items-center justify-center text-white text-xs font-bold">P</div>
            <span class="text-sm font-bold text-white">Admin</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs font-bold text-red-400 px-3 py-1.5 border border-red-500/20 rounded-lg">Sign out</button>
        </form>
    </div>

    {{-- ═══════════ MAIN CONTENT ═══════════ --}}
    <main class="flex-1 h-full overflow-y-auto overflow-x-hidden bg-[#F0F2F5] dark:bg-[#050505] pt-0 md:pt-0">
        <div class="md:hidden h-14"></div>{{-- mobile spacer --}}

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-6 mt-6 flex items-center gap-3 px-5 py-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 text-sm font-semibold">
                <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-6 mt-6 flex items-center gap-3 px-5 py-3.5 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold">
                <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="p-6 max-w-7xl mx-auto">
            {{ $slot }}
        </div>
    </main>

</div>

@stack('modals')

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@stack('scripts')
</body>
</html>
