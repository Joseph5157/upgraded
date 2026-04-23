<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Admin') }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { background: var(--pe-bg-page); font-family: 'DM Sans', sans-serif; }

        /* ── Sidebar nav link ── */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #4B5563;
            text-decoration: none;
            transition: background 0.12s, color 0.12s;
            width: 100%;
        }
        .nav-link:hover { background: #EDE9FE; color: #6D28D9; }
        .nav-link.active {
            background: #EDE9FE;
            color: #6D28D9;
            font-weight: 600;
            border-left: 3px solid #6D28D9;
            border-radius: 0 8px 8px 0;
        }

        .nav-group-label {
            font-size: 9px;
            font-family: 'DM Mono', monospace;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #9CA3AF;
            padding: 0 10px;
            margin-bottom: 4px;
        }

        /* badge pill inside nav items */
        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 20px;
            line-height: 1.4;
        }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(109,40,217,0.15); border-radius: 99px; }
    </style>
</head>

<body class="bg-[#EEF2FF] antialiased overflow-x-hidden">
<div class="flex h-screen overflow-hidden">

    {{-- ═══════════ SIDEBAR ═══════════ --}}
    <aside class="hidden md:flex w-56 flex-shrink-0 flex-col bg-white border-r border-[#DDD6FE]" style="box-shadow:2px 0 8px rgba(109,40,217,0.08);">

        {{-- Brand --}}
        <div class="px-5 py-5" style="border-bottom:1px solid #DDD6FE;">
            <div class="flex items-center gap-3">
                <div class="brand-mark w-7 h-7 flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                     style="background:linear-gradient(135deg,#6D28D9,#8B5CF6);border-radius:10px;box-shadow:0 4px 12px rgba(109,40,217,0.45);font-weight:700;">P</div>
                <div>
                    <p class="text-sm font-bold leading-none" style="color:#1E1B4B;">{{ config('app.name') }}</p>
                    <p class="text-[9px] uppercase tracking-widest mt-0.5" style="color:#9CA3AF;font-family:'DM Mono',monospace;">Admin</p>
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
                    <a href="#"
                       onclick="event.preventDefault(); document.getElementById('create-account-modal')?.classList.remove('hidden');"
                       class="nav-link">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Create Account
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
                            <span class="nav-badge" style="background:#FEE2E2;color:#DC2626;">{{ $frozenClients }}</span>
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
                            <span class="nav-badge" style="background:#FEF3C7;color:#92400E;">{{ $lowCreditClients }} out</span>
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
                            <span class="nav-badge" style="background:#FEF3C7;color:#92400E;">{{ $pendingTopups }}</span>
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
                            <span class="nav-badge" style="background:#FEF3C7;color:#92400E;">{{ $pendingRefunds }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.client-links.index') }}"
                       class="nav-link {{ request()->routeIs('admin.client-links.*') ? 'active' : '' }}">
                        <i data-lucide="link" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Guest Links
                    </a>
                    <a href="{{ route('admin.payment-settings.index') }}"
                       class="nav-link {{ request()->routeIs('admin.payment-settings.*') ? 'active' : '' }}">
                        <i data-lucide="wallet" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Payment Methods
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
                            <span class="nav-badge" style="background:#FEE2E2;color:#DC2626;">{{ $frozenVendors }}</span>
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
                        Billing
                    </a>
                    <a href="{{ route('admin.pricing.index') }}"
                       class="nav-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                        <i data-lucide="tag" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Pricing
                    </a>
                </div>
            </div>

        </nav>

        {{-- Admin profile + logout --}}
        <div class="px-3 py-4" style="border-top:1px solid #DDD6FE;">
            <a href="{{ route('profile.edit') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }} mb-2">
                <div class="w-6 h-6 flex items-center justify-center text-[9px] font-bold flex-shrink-0"
                     style="background:#EDE9FE;border:2px solid #8B5CF6;color:#6D28D9;border-radius:50%;font-weight:700;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold truncate leading-none" style="color:#1E1B4B;">{{ auth()->user()->name }}</p>
                    <p class="text-[9px] mt-0.5" style="color:#9CA3AF;">My profile</p>
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
    <div class="md:hidden fixed top-0 inset-x-0 z-30 px-4 py-3 flex items-center justify-between" style="background:#FFFFFF;border-bottom:1px solid #DDD6FE;box-shadow:0 1px 3px rgba(109,40,217,0.10);">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-md flex items-center justify-center text-white text-xs font-bold"
                 style="background:linear-gradient(135deg,#6D28D9,#8B5CF6);">P</div>
            <span class="text-sm font-bold" style="color:#1E1B4B;">Admin</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs font-bold text-red-400 px-3 py-1.5 border border-red-500/20 rounded-lg">Sign out</button>
        </form>
    </div>

    {{-- ═══════════ MAIN CONTENT ═══════════ --}}
    <main class="flex-1 h-full overflow-y-auto overflow-x-hidden pt-0 md:pt-0" style="background:#EEF2FF;">
        <div class="md:hidden h-14"></div>{{-- mobile spacer --}}

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-6 mt-6 flex items-center gap-3 px-5 py-3.5 rounded-2xl text-sm font-semibold"
                 style="background:#D1FAE5;border:1px solid #059669;color:#064E3B;">
                <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-6 mt-6 flex items-center gap-3 px-5 py-3.5 rounded-2xl text-sm font-semibold"
                 style="background:#FEE2E2;border:1px solid #DC2626;color:#7F1D1D;">
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

