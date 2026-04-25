<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        body { font-family: 'Outfit', 'Inter', sans-serif; }

        .card {
            background: #0f0f14;
            border: 1px solid rgba(255,255,255,0.055);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover { border-color: rgba(99,102,241,0.18); }

        .card-glow:hover {
            box-shadow: 0 0 0 1px rgba(99,102,241,0.15),
                        0 8px 32px -8px rgba(99,102,241,0.12);
        }

        .upload-zone {
            border: 2px dashed rgba(99,102,241,0.15);
            background: rgba(99,102,241,0.02);
            transition: all 0.25s ease;
        }
        .upload-zone:hover {
            border-color: rgba(99,102,241,0.4);
            background: rgba(99,102,241,0.05);
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

        .shimmer-line {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.03), transparent);
            background-size: 200% 100%;
        }

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

<body class="h-screen flex bg-[#070709] text-slate-400 overflow-hidden overflow-x-hidden">

    <div id="sidebar-overlay"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden"
         onclick="closeSidebar()"></div>

    <aside id="mobile-sidebar"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-[#0b0b0f] border-r border-white/[0.05] flex flex-col md:hidden">
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
            <div class="flex items-center justify-between px-4 py-2.5 rounded-xl text-slate-600 cursor-not-allowed select-none text-sm font-medium">
                <div class="flex items-center gap-3">
                    <i data-lucide="history" class="w-4 h-4 flex-shrink-0"></i>
                    Orders
                </div>
                <span class="text-[7px] font-black uppercase tracking-widest text-indigo-500/40 bg-indigo-500/[0.06] border border-indigo-500/[0.1] px-1.5 py-0.5 rounded">Soon</span>
            </div>
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

    {{-- 
         SIDEBAR
     --}}
    <aside class="hidden md:flex w-[220px] flex-shrink-0 h-full border-r border-white/[0.05] flex-col bg-[#0b0b0f]">

        {{-- Brand --}}
        <div class="px-5 pt-6 pb-8">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30 flex-shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
                </div>
                <span class="font-bold text-white text-[15px] tracking-tight">{{ config('app.name') }}</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-2 space-y-0.5">
            <a href="#" class="sidebar-active flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                <i data-lucide="layout-grid" class="w-4 h-4 flex-shrink-0"></i>
                Dashboard
            </a>
            <div class="flex items-center justify-between px-4 py-2.5 rounded-xl text-slate-600 cursor-not-allowed select-none text-sm font-medium">
                <div class="flex items-center gap-3">
                    <i data-lucide="history" class="w-4 h-4 flex-shrink-0"></i>
                    Orders
                </div>
                <span class="text-[7px] font-black uppercase tracking-widest text-indigo-500/40 bg-indigo-500/[0.06] border border-indigo-500/[0.1] px-1.5 py-0.5 rounded">Soon</span>
            </div>
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

        {{-- Sign out --}}
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

    {{-- 
         MAIN
     --}}
    <main class="flex-1 overflow-y-auto bg-[#070709] scrollbar-thin">

        {{-- TOP HEADER --}}
        <header class="min-h-[56px] border-b border-white/[0.05] flex items-center justify-between px-3 sm:px-8 py-2 sm:py-0 bg-[#070709]/80 backdrop-blur-xl sticky top-0 z-20">
            {{-- Mobile Menu Button --}}
            <button class="hidden md:hidden w-8 h-8 flex items-center justify-center text-slate-400 hover:text-white mr-2" onclick="openSidebar()" aria-label="Open menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            @php
                $hour = now()->hour;
                $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
            @endphp
            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                <h1 class="text-[12px] sm:text-[15px] font-semibold text-white/90 truncate">
                    {{ $greeting }}, {{ auth()->user()->name }}
                </h1>
                <span class="text-sm sm:text-base flex-shrink-0">👋</span>
            </div>
            <div class="flex items-center gap-2 sm:gap-5 ml-2">
                <div class="flex items-center gap-2 sm:gap-3 pr-2 sm:pr-5 border-r border-white/[0.05]">
                    <p class="text-[9px] font-mono text-indigo-400 sm:hidden">{{ str_pad($client->id, 4, '0', STR_PAD_LEFT) }}</p>
                    <div class="text-right hidden sm:block">
                        <p class="text-[9px] text-slate-600 font-bold uppercase tracking-[0.2em]">Client ID</p>
                        <p class="text-[11px] font-mono font-bold text-indigo-400 bg-indigo-500/[0.08] px-2 py-0.5 rounded-md mt-0.5">
                            ID-{{ str_pad($client->id, 4, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 ring-1 ring-indigo-500/20">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="relative cursor-pointer">
                    <i data-lucide="bell" class="w-4 h-4 sm:w-[18px] sm:h-[18px] text-slate-500 hover:text-slate-300 transition-colors"></i>
                    <span class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 bg-indigo-500 rounded-full ring-2 ring-[#070709]"></span>
                </div>
            </div>
        </header>

        {{--  ANNOUNCEMENTS BANNER  --}}
        <x-announcements-banner class="bg-gradient-to-r from-blue-500 to-purple-500 text-white py-4 px-6 rounded-lg shadow-lg" />

        <div id="client-dashboard-live" class="px-3 py-4 pb-24 md:pb-0 max-w-[1380px] mx-auto space-y-4 sm:px-6 sm:py-5 sm:space-y-5 xl:px-8 xl:py-6 xl:space-y-6">

            @php
                $activeOrders = $orders->whereNotIn('status', ['delivered', 'cancelled'])->count();
                $planLabel = $client->plan_expiry && $client->plan_expiry->isPast() ? 'Expired' : 'Professional';
                $creditTone = $remaining > 10
                    ? 'border-emerald-500/[0.16] bg-emerald-500/[0.05] text-emerald-300'
                    : ($remaining > 0
                        ? 'border-amber-500/[0.16] bg-amber-500/[0.05] text-amber-300'
                        : 'border-red-500/[0.16] bg-red-500/[0.05] text-red-300');
            @endphp

            <div class="card rounded-[1.75rem] p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Client Overview</p>
                        <h2 class="text-[1.2rem] sm:text-[1.5rem] font-semibold text-white mt-2 tracking-tight leading-tight">Upload. Track. Download.</h2>
                    </div>
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-[0.18em] border @if($client->plan_expiry && $client->plan_expiry->isPast()) border-red-500/[0.18] bg-red-500/[0.06] text-red-300 @else border-emerald-500/[0.18] bg-emerald-500/[0.06] text-emerald-300 @endif">
                        <span class="w-1.5 h-1.5 rounded-full @if($client->plan_expiry && $client->plan_expiry->isPast()) bg-red-400 @else bg-emerald-400 @endif"></span>
                        {{ $planLabel }}
                    </span>
                </div>

                <div class="mt-4 space-y-3">
                    @if(session('success'))
                        <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-emerald-500/[0.16] bg-emerald-500/[0.05]">
                            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                            <p class="text-[12px] sm:text-[13px] font-medium text-emerald-200 leading-6">{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-red-500/[0.16] bg-red-500/[0.05]">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0"></i>
                            <p class="text-[12px] sm:text-[13px] font-medium text-red-200 leading-6">{{ session('error') }}</p>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-amber-500/[0.16] bg-amber-500/[0.05]">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-amber-300 mt-0.5 flex-shrink-0"></i>
                            <p class="text-[12px] sm:text-[13px] font-medium text-amber-100 leading-6">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3 rounded-2xl px-4 py-3 border {{ $creditTone }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full @if($remaining > 10) bg-emerald-400 @elseif($remaining > 0) bg-amber-400 @else bg-red-400 @endif"></span>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Credit Status</p>
                                <p class="text-[13px] font-semibold mt-1">
                                    @if($remaining > 10)
                                        {{ $remaining }} credits available
                                    @elseif($remaining > 0)
                                        {{ $remaining }} credits remaining
                                    @else
                                        0 credits, top up required
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('topup-modal').classList.remove('hidden')"
                            class="px-3 py-1.5 rounded-lg bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-bold uppercase tracking-[0.18em] transition-colors">
                            Top Up
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 min-w-0 gap-3">
                <div class="card rounded-2xl p-4 min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Credits</p>
                    <div class="flex items-end justify-between mt-3 gap-3">
                        <div>
                            <h3 class="text-[2rem] font-extrabold text-white leading-none font-mono">{{ $remaining }}</h3>
                            <p class="text-[11px] text-slate-400 mt-2">Used: {{ $consumed }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/[0.08] border border-indigo-500/[0.12] flex items-center justify-center text-indigo-400 flex-shrink-0">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <div class="card rounded-2xl p-4 min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Orders</p>
                    <div class="flex items-end justify-between mt-3 gap-3">
                        <div>
                            <h3 class="text-[2rem] font-extrabold text-white leading-none font-mono">{{ $activeOrders }}</h3>
                            <p class="text-[11px] text-slate-400 mt-2">Active in workflow</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-500/[0.08] border border-blue-500/[0.12] flex items-center justify-center text-blue-400 flex-shrink-0">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{--  MAIN GRID: UPLOAD BUTTON + ACTIVITY  --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

                {{-- NEW ORDER BUTTON --}}
                <div class="lg:col-span-7">
                    <div class="card rounded-3xl p-4 sm:p-5 xl:p-6 flex flex-col gap-4">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">New Order</p>
                                <h2 class="text-[15px] sm:text-[17px] font-bold text-white tracking-tight mt-2">Secure Upload</h2>
                                <p class="text-[11px] text-slate-400 mt-1">Submit your document for non-repository scanning</p>
                            </div>
                            <div class="w-10 h-10 sm:w-11 sm:h-11 bg-white/[0.03] rounded-2xl flex items-center justify-center border border-white/[0.06] flex-shrink-0">
                                <i data-lucide="shield" class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-400"></i>
                            </div>
                        </div>

                        <button onclick="openClientUploadModal()"
                            class="w-full flex items-center justify-center gap-2.5 py-5 rounded-2xl border-2 border-dashed border-indigo-500/[0.2] bg-indigo-500/[0.03] hover:border-indigo-400/50 hover:bg-indigo-500/[0.06] text-indigo-400 font-bold text-[13px] transition-all active:scale-[0.98]">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                            New Order
                        </button>

                        <div class="grid grid-cols-2 gap-2 sm:gap-3">
                            <div class="p-3 sm:p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-slate-300 uppercase tracking-[0.14em] sm:tracking-widest leading-tight">AI Detection<br>Enabled</span>
                            </div>
                            <div class="p-3 sm:p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-slate-300 uppercase tracking-[0.14em] sm:tracking-widest leading-tight">No Repo<br>Mode</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RECENT ACTIVITY --}}
                <div class="lg:col-span-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-3 px-1">
                        <h2 class="text-[10px] sm:text-[11px] font-black text-white uppercase tracking-[0.18em] sm:tracking-[0.2em]">Recent Activity</h2>
                        <span class="text-[7px] font-black uppercase tracking-widest text-indigo-400/40 bg-indigo-500/[0.05] border border-indigo-500/[0.08] px-2 py-0.5 rounded cursor-not-allowed">Coming Soon</span>
                    </div>

                    <div class="card rounded-3xl p-3 sm:p-4 overflow-y-auto scrollbar-thin max-h-[500px] space-y-2">
                        <div class="overflow-x-auto -mx-4 px-4">
                        @forelse($orders as $order)
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-3 sm:p-4 group">

                                {{-- File row --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white/[0.04] rounded-xl flex items-center justify-center text-slate-500 group-hover:bg-indigo-500/[0.12] group-hover:text-indigo-400 transition-all border border-white/[0.05] flex-shrink-0">
                                            <i data-lucide="file-text" class="w-5 h-5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="min-w-0">
                                                <h4 class="text-[12px] sm:text-[13px] font-bold text-white truncate leading-snug max-w-[170px] sm:max-w-none">
                                        {{ $order->files->first() ? ($order->files->first()->original_name ?? basename($order->files->first()->file_path)) : 'Document' }}
                                                </h4>
                                                @if($order->files_count > 1)
                                                    <p class="text-[9px] text-indigo-300 font-bold uppercase tracking-widest mt-1">
                                                        + {{ $order->files_count - 1 }} more file{{ $order->files_count - 1 > 1 ? 's' : '' }}
                                                    </p>
                                                @endif
                                            </div>
                                            <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                                {{ $order->created_at->format('d M, h:i A') }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Status badge --}}
                                    @if($order->status->value === 'delivered')
                                        <span class="status-badge bg-emerald-500/[0.1] text-emerald-400 border border-emerald-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Ready
                                        </span>
                                    @elseif($order->status->value === 'cancelled')
                                        <span class="status-badge bg-slate-500/[0.1] text-slate-500 border border-slate-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-slate-500"></span> Cancelled
                                        </span>
                                    @elseif($order->status->value === 'processing')
                                        <span class="status-badge bg-blue-500/[0.1] text-blue-400 border border-blue-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-blue-400 pulse-dot"></span> In progress
                                        </span>
                                    @elseif($order->status->value === 'claimed')
                                        <span class="status-badge bg-amber-500/[0.1] text-amber-400 border border-amber-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-amber-400"></span> Reserved
                                        </span>
                                    @else
                                        <span class="status-badge bg-slate-500/[0.08] text-slate-500 border border-slate-500/[0.1] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-slate-500 pulse-dot"></span> Queued
                                        </span>
                                    @endif
                                </div>

                                {{-- Divider --}}
                                <div class="border-t border-white/[0.05] mt-3 pt-3">

                                    {{-- DELIVERED STATE --}}
                                    @if($order->status->value === 'delivered')
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            {{-- Download buttons --}}
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($order->report?->ai_report_path && $order->report?->plag_report_path)
                                                    <a href="{{ route('client.download', $order->token_view) }}"
                                                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-500/[0.12] hover:bg-indigo-500/[0.2] text-indigo-300 text-[9px] font-bold rounded-lg border border-indigo-500/[0.2] transition-all active:scale-95">
                                                        <i data-lucide="archive" class="w-3 h-3"></i> Download Both
                                                    </a>
                                                @endif
                                                @if($order->report?->ai_report_path)
                                                    <a href="{{ route('client.download', $order->token_view) }}?type=ai"
                                                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-red-500/[0.12] text-red-300 text-[9px] font-bold rounded-lg border border-red-500/[0.12] transition-all active:scale-95">
                                                        <i data-lucide="download" class="w-3 h-3"></i> AI Report
                                                    </a>
                                                @endif
                                                @if($order->report?->plag_report_path)
                                                    <a href="{{ route('client.download', $order->token_view) }}?type=plag"
                                                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-amber-500/[0.12] text-amber-300 text-[9px] font-bold rounded-lg border border-amber-500/[0.12] transition-all active:scale-95">
                                                        <i data-lucide="download" class="w-3 h-3"></i> Plag Report
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                    {{-- CANCELLED STATE --}}
                                    @elseif($order->status->value === 'cancelled')
                                        @if($order->files->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                                @foreach($order->files as $file)
                                                    <form method="POST" action="{{ route('client.orders.files.delete', [$order, $file]) }}"
                                                        onsubmit="return confirm('Permanently delete this file from our servers?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/[0.08] hover:bg-red-500/[0.15] text-red-400 text-[10px] font-bold rounded-lg border border-red-500/[0.15] transition-all">
                                                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                                        <span class="truncate max-w-[120px]">{{ $file->original_name ?? basename($file->file_path) }}</span>
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                                <i data-lucide="ban" class="w-3 h-3"></i> Order Cancelled
                                            </p>
                                            @php
                                                $existingRefund = $order->refundRequest ?? null;
                                            @endphp
                                            @if($order->release_count > 0)
                                                {{-- Vendor already submitted to Turnitin — no auto-refund --}}
                                                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/[0.08] text-amber-400 text-[10px] font-bold rounded-lg border border-amber-500/[0.15]" title="A vendor processed this order in Turnitin. Contact admin for manual review.">
                                                    <i data-lucide="alert-circle" class="w-3 h-3"></i> Contact Admin
                                                </span>
                                            @elseif($existingRefund && $existingRefund->status === 'pending')
                                                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/[0.08] text-amber-400 text-[10px] font-bold rounded-lg border border-amber-500/[0.15]">
                                                    <i data-lucide="clock" class="w-3 h-3"></i> Refund queued
                                                </span>
                                            @elseif($existingRefund && $existingRefund->status === 'approved')
                                                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/[0.08] text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-500/[0.15]">
                                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Refund Approved
                                                </span>
                                            @else
                                                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/[0.08] text-red-400 text-[10px] font-bold rounded-lg border border-red-500/[0.15]">
                                                    <i data-lucide="x-circle" class="w-3 h-3"></i> Refund Rejected
                                                </span>
                                            @endif
                                        </div>

                                    {{-- ACTIVE / PROCESSING / PENDING STATE --}}
                                    @else
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2 text-[9px] text-slate-400 font-bold uppercase tracking-widest">
                                                @if($order->status->value === 'processing')
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full pulse-dot"></span>
                                                    In progress...
                                                @elseif($order->status->value === 'claimed')
                                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                                    Reserved...
                                                @else
                                                    <span class="w-1.5 h-1.5 bg-slate-600 rounded-full pulse-dot"></span>
                                                    Queued...
                                                @endif
                                            </div>
                                            @if($order->status->value === 'pending' && !$order->claimed_by)
                                                <form action="{{ route('client.orders.delete', $order) }}" method="POST"
                                                    onsubmit="return confirm('Delete this order and all its files permanently?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-red-500/[0.12] text-red-300 text-[9px] font-bold rounded-lg border border-red-500/[0.12] transition-all active:scale-95">
                                                        <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-14 text-center">
                                <div class="w-14 h-14 bg-white/[0.03] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/[0.05]">
                                    <i data-lucide="inbox" class="w-6 h-6 text-slate-700"></i>
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">No Recent Orders</p>
                                <p class="text-[11px] text-slate-500 mt-1">Upload a document to get started</p>
                            </div>
                        @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <footer class="px-8 py-6 text-center border-t border-white/[0.04] bg-[#0b0b0f] mt-4">
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.3em]">{{ config('app.name') }} &bull; Advanced plagiarism review</p>
        </footer>

        {{-- Mobile Bottom Nav --}}
        <nav class="fixed bottom-0 left-0 right-0 z-30 md:hidden bg-[#09090c] border-t border-white/[0.06]" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="flex items-center">

                {{-- Home --}}
                <a href="{{ route('client.dashboard') }}"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-indigo-400">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Home</span>
                </a>

                {{-- Orders --}}
                <button onclick="showComingSoon()"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-600">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Orders</span>
                </button>

                {{-- Credits --}}
                <a href="{{ route('client.subscription') }}"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Credits</span>
                </a>

                {{-- Profile --}}
                <a href="{{ route('profile.edit') }}"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Profile</span>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit"
                       class="w-full flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-red-400 transition-colors">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span class="text-[9px] font-bold uppercase tracking-widest">Logout</span>
                    </button>
                </form>

            </div>
        </nav>

        {{-- Coming Soon Toast --}}
        <div id="coming-soon-toast"
             class="fixed bottom-24 left-1/2 -translate-x-1/2 z-50 hidden md:hidden bg-[#1e1e2e] border border-indigo-500/20 text-indigo-300 text-xs font-semibold px-5 py-3 rounded-2xl shadow-xl">
            Orders coming soon
        </div>
    </main>

    {{-- 
         TOP-UP MODAL
     --}}
    <div id="topup-modal"
        class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-md p-7 shadow-2xl" onclick="event.stopPropagation()">

            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/[0.2]">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-bold text-white">Request credit top-up</h3>
                        <p class="text-[9px] text-slate-600 uppercase tracking-widest mt-0.5">Add credits to your account</p>
                    </div>
                </div>
                <button onclick="document.getElementById('topup-modal').classList.add('hidden')"
                    class="text-slate-600 hover:text-slate-300 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('client.topup.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Choose a top-up amount</label>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <button type="button" onclick="setSlots(50)"
                            class="slot-preset py-2.5 bg-white/[0.04] hover:bg-indigo-500/[0.1] border border-white/[0.06] hover:border-indigo-500/30 rounded-xl text-xs font-bold text-slate-400 hover:text-indigo-400 transition-all">
                            50 Slots
                        </button>
                        <button type="button" onclick="setSlots(100)"
                            class="slot-preset py-2.5 bg-white/[0.04] hover:bg-indigo-500/[0.1] border border-white/[0.06] hover:border-indigo-500/30 rounded-xl text-xs font-bold text-slate-400 hover:text-indigo-400 transition-all">
                            100 Slots
                        </button>
                        <button type="button" onclick="setSlots(200)"
                            class="slot-preset py-2.5 bg-white/[0.04] hover:bg-indigo-500/[0.1] border border-white/[0.06] hover:border-indigo-500/30 rounded-xl text-xs font-bold text-slate-400 hover:text-indigo-400 transition-all">
                            200 Slots
                        </button>
                    </div>
                    <input type="number" name="amount_requested" id="slot-input" min="1"
                        placeholder="Or enter custom amount..."
                        oninput="updatePrice(this.value)"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500/50 transition-colors placeholder-slate-700 font-mono"
                        required>
                </div>

                <div class="p-4 bg-indigo-500/[0.05] border border-indigo-500/[0.1] rounded-2xl flex items-center justify-between">
                    <div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total Payable</p>
                        <p id="price-display" class="text-2xl font-extrabold text-white mt-0.5 font-mono">₹0</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Rate</p>
                        <p class="text-[11px] text-indigo-400 font-bold font-mono mt-0.5">{{ number_format($client->price_per_file, 0) }} / slot</p>
                    </div>
                </div>

                <div class="p-4 bg-white/[0.02] border border-white/[0.05] rounded-2xl space-y-3">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-600">Payment instructions</p>
                    @if($paymentSetting)
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-emerald-500/[0.1] rounded-lg flex items-center justify-center text-emerald-500 flex-shrink-0">
                                <i data-lucide="smartphone" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Account holder name</p>
                                <p class="text-[13px] font-semibold text-white mt-0.5">{{ $paymentSetting->upi_name }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-indigo-500/[0.1] rounded-lg flex items-center justify-center text-indigo-400 flex-shrink-0">
                                <i data-lucide="at-sign" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">UPI ID</p>
                                <p class="text-sm font-mono font-bold text-white mt-0.5">{{ $paymentSetting->upi_id }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-[11px] text-amber-400">Payment details not configured yet. Contact admin.</p>
                    @endif
                    <p class="text-[10px] text-slate-400 leading-relaxed">Send the exact amount to the UPI ID, then paste your <span class="text-indigo-400 font-semibold">UTR or transaction reference number</span> below.</p>
                </div>

                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">UTR or transaction reference</label>
                    <input type="text" name="transaction_id" required placeholder="e.g. 123456789012"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500/50 transition-colors placeholder-slate-700 font-mono">
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold uppercase tracking-[0.25em] rounded-xl transition-all flex justify-center items-center gap-2 shadow-lg shadow-indigo-500/20">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit top-up request
                </button>
            </form>
        </div>
    </div>

    @include('partials.client-upload-modal')

    <script>
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

        document.querySelectorAll('#mobile-sidebar a').forEach(function(el) {
            el.addEventListener('click', closeSidebar);
        });
    </script>

    <script>
        // ── Client upload modal ─────────────────────────────────────
        const CLIENT_UPLOAD_MAX_SIZE  = 100 * 1024 * 1024;
        const CLIENT_CSRF_REFRESH_URL = @json(route('csrf.refresh'));
        const CLIENT_LOGIN_URL        = @json(route('login', ['expired' => 1]));
        const CLIENT_DASHBOARD_URL    = @json(route('client.dashboard'));

        function openClientUploadModal() {
            if (window.__clientDashboardPolling) window.__clientDashboardPolling.stop();
            const modal = document.getElementById('client-upload-modal');
            if (modal) modal.classList.remove('hidden');
            if (window.lucide && lucide.createIcons) lucide.createIcons();
            // wire notes counter each time modal opens
            const notes = document.getElementById('client-upload-notes');
            if (notes) {
                notes.oninput = function () {
                    const counter = document.getElementById('client-upload-notes-counter');
                    if (counter) counter.textContent = this.value.length + ' / 1000';
                };
            }
        }

        function closeClientUploadModal() {
            const modal = document.getElementById('client-upload-modal');
            if (modal) modal.classList.add('hidden');
            _resetClientUploadModal();
            if (window.__clientDashboardPolling) window.__clientDashboardPolling.start();
        }

        function _resetClientUploadModal() {
            const input = document.getElementById('client-upload-input');
            if (input) input.value = '';

            const preview = document.getElementById('client-upload-preview');
            if (preview) {
                preview.innerHTML = '<p class="text-[13px] font-semibold text-white/90">Drop a file or tap to browse</p>' +
                    '<p class="text-[10px] text-slate-500 mt-0.5">PDF \u00b7 DOCX \u00b7 DOC \u00b7 ZIP \u00b7 up to 100MB</p>';
            }

            const label = document.getElementById('client-upload-label');
            if (label) {
                label.classList.add('border-dashed', 'border-indigo-500/[0.16]', 'bg-white/[0.03]');
                label.classList.remove('border-indigo-500/30', 'bg-indigo-500/[0.05]');
            }

            const notes = document.getElementById('client-upload-notes');
            if (notes) notes.value = '';
            const counter = document.getElementById('client-upload-notes-counter');
            if (counter) counter.textContent = '0 / 1000';

            ['client-upload-ready', 'client-upload-progress', 'client-upload-error'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
            });

            const submitBtn = document.getElementById('client-upload-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> Select a file first';
            }
            const cancelBtn = document.getElementById('client-upload-cancel-btn');
            if (cancelBtn) cancelBtn.disabled = false;

            const fill = document.getElementById('client-upload-progress-fill');
            if (fill) fill.style.width = '0%';
            const pct = document.getElementById('client-upload-progress-text');
            if (pct) pct.textContent = '0%';

            if (window.lucide && lucide.createIcons) lucide.createIcons();
        }

        function handleClientFileSelect(input) {
            const files = Array.from(input.files);
            if (!files.length) return;

            _clientUploadClearError();

            if (files.length > 1) {
                input.value = '';
                _clientUploadShowError('Only 1 file allowed per order.');
                return;
            }

            const file = files[0];

            if (file.size > CLIENT_UPLOAD_MAX_SIZE) {
                input.value = '';
                _clientUploadShowError('File must be 100MB or smaller.');
                return;
            }

            const ext  = file.name.split('.').pop().toUpperCase();
            const size = file.size > 1048576
                ? (file.size / 1048576).toFixed(1) + ' MB'
                : (file.size / 1024).toFixed(0) + ' KB';
            const name = file.name.length > 30 ? file.name.slice(0, 27) + '...' : file.name;

            const preview = document.getElementById('client-upload-preview');
            if (preview) {
                preview.innerHTML = '<p class="text-[12px] font-bold text-indigo-300 truncate">' + name + '</p>' +
                    '<p class="text-[10px] text-slate-400">' + ext + ' \u00b7 ' + size + ' \u00b7 Ready to submit</p>';
            }

            const label = document.getElementById('client-upload-label');
            if (label) {
                label.classList.remove('border-dashed', 'border-indigo-500/[0.16]', 'bg-white/[0.03]');
                label.classList.add('border-indigo-500/30', 'bg-indigo-500/[0.05]');
            }

            const ready = document.getElementById('client-upload-ready');
            if (ready) { ready.classList.remove('hidden'); ready.classList.add('flex'); }

            const submitBtn = document.getElementById('client-upload-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> Submit Order';
            }

            if (window.lucide && lucide.createIcons) lucide.createIcons();
        }

        function submitClientUploadForm() {
            const form      = document.getElementById('client-upload-modal-form');
            const submitBtn = document.getElementById('client-upload-submit-btn');
            const cancelBtn = document.getElementById('client-upload-cancel-btn');
            const ready     = document.getElementById('client-upload-ready');
            const progress  = document.getElementById('client-upload-progress');
            const fill      = document.getElementById('client-upload-progress-fill');
            const pctText   = document.getElementById('client-upload-progress-text');

            if (!form || submitBtn.disabled) return;

            _clientUploadClearError();

            submitBtn.disabled = true;
            cancelBtn.disabled = true;
            submitBtn.innerHTML = 'Uploading...';
            if (ready)    { ready.classList.add('hidden');    ready.classList.remove('flex'); }
            if (progress) { progress.classList.remove('hidden'); progress.classList.add('flex'); }

            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch(CLIENT_CSRF_REFRESH_URL)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    csrfToken = data.token;
                    const tokenField = form.querySelector('input[name="_token"]');
                    if (tokenField) tokenField.value = csrfToken;
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', csrfToken);
                })
                .catch(function () {})
                .finally(function () {
                    const xhr      = new XMLHttpRequest();
                    const formData = new FormData(form);

                    xhr.upload.onprogress = function (e) {
                        if (!e.lengthComputable) return;
                        const pct = Math.round((e.loaded / e.total) * 100);
                        if (fill)    fill.style.width    = pct + '%';
                        if (pctText) pctText.textContent = pct + '%';
                    };

                    xhr.onload = function () {
                        if (xhr.status === 419) {
                            window.location.replace(CLIENT_LOGIN_URL);
                            return;
                        }

                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.error) {
                                    _clientUploadResetBtn();
                                    _clientUploadShowError(data.error);
                                    return;
                                }
                                try { if (data.success) sessionStorage.setItem('upload_success', data.success); } catch (_) {}
                                window.location.href = data.redirect || CLIENT_DASHBOARD_URL;
                                return;
                            } catch (e) {}
                            window.location.href = CLIENT_DASHBOARD_URL;
                        } else {
                            _clientUploadResetBtn();
                            var msg = 'Upload failed. Please try again.';
                            try {
                                var payload = JSON.parse(xhr.responseText);
                                msg = payload.error
                                    || (payload.errors && Object.values(payload.errors).reduce(function (a, l) { return a.concat(l); }, []).find(Boolean))
                                    || payload.message
                                    || msg;
                            } catch (e) {}
                            if (xhr.status === 413) msg = 'File too large. Please upload a smaller file.';
                            if (xhr.status >= 500) msg = 'Server error. Please try again in a moment.';
                            _clientUploadShowError(msg);
                        }
                    };

                    xhr.onerror = function () {
                        _clientUploadResetBtn();
                        _clientUploadShowError('Network error. Please check your connection and try again.');
                    };

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    xhr.send(formData);
                });
        }

        function _clientUploadShowError(msg) {
            const errEl  = document.getElementById('client-upload-error');
            const errMsg = document.getElementById('client-upload-error-msg');
            if (errMsg) errMsg.textContent = msg;
            if (errEl)  { errEl.classList.remove('hidden'); errEl.classList.add('flex'); }
            const progress = document.getElementById('client-upload-progress');
            if (progress) { progress.classList.add('hidden'); progress.classList.remove('flex'); }
        }

        function _clientUploadClearError() {
            const errEl = document.getElementById('client-upload-error');
            if (errEl) { errEl.classList.add('hidden'); errEl.classList.remove('flex'); }
        }

        function _clientUploadResetBtn() {
            const submitBtn = document.getElementById('client-upload-submit-btn');
            const cancelBtn = document.getElementById('client-upload-cancel-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> Submit Order';
            }
            if (cancelBtn) cancelBtn.disabled = false;
            const progress = document.getElementById('client-upload-progress');
            if (progress) { progress.classList.add('hidden'); progress.classList.remove('flex'); }
            const ready = document.getElementById('client-upload-ready');
            if (ready) { ready.classList.remove('hidden'); ready.classList.add('flex'); }
            if (window.lucide && lucide.createIcons) lucide.createIcons();
        }

        // Hoist modal to <body> so it is never clipped by a parent overflow
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('client-upload-modal');
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
        // ── End client upload modal ──────────────────────────────────
    </script>

    <script>
        lucide.createIcons();

        function showComingSoon() {
            const toast = document.getElementById('coming-soon-toast');
            if (!toast) return;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2500);
        }

        //  Top-up
        const pricePerSlot = {{ $client->price_per_file ?? 0 }};

        function setSlots(n) {
            document.getElementById('slot-input').value = n;
            updatePrice(n);
        }

        function updatePrice(val) {
            const n = parseInt(val) || 0;
            document.getElementById('price-display').textContent = '₹' + (n * pricePerSlot).toLocaleString('en-IN');
        }

    </script>
    <script>
        (function () {
            const pulseUrl = @json(route('client.dashboard.pulse'));
            const loginUrl = @json(route('login', ['expired' => 1]));
            let currentSignature = @json($dashboardSignature ?? '');
            let pollTimer = null;
            let inFlight = false;

            function redirectToLogin() {
                window.location.replace(loginUrl);
            }

             async function checkForDashboardUpdates() {
                if (inFlight || document.hidden) {
                    return;
                }

                inFlight = true;

                try {
                    const response = await fetch(pulseUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });

                    if (response.status === 401 || response.status === 419 || (response.redirected && response.url.includes('/login'))) {
                        redirectToLogin();
                        return;
                    }

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();

                    if (payload.signature) {
                        currentSignature = payload.signature;
                    }

                    if (payload.liveHtml) {
                        const liveEl = document.getElementById('client-dashboard-live');
                        if (liveEl) {
                            liveEl.outerHTML = payload.liveHtml;
                            if (window.lucide && lucide.createIcons) {
                                lucide.createIcons();
                            }
                        }
                    }
                } catch (error) {
                    // Ignore transient network/session hiccups; next poll will retry.
                } finally {
                    inFlight = false;
                }
            }

            function startDashboardPolling() {
                if (pollTimer !== null) {
                    return;
                }

                checkForDashboardUpdates();
                pollTimer = window.setInterval(checkForDashboardUpdates, 10000);
            }

            function stopDashboardPolling() {
                if (pollTimer !== null) {
                    window.clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

             document.addEventListener('visibilitychange', function () {
                 if (document.hidden) {
                     stopDashboardPolling();
                     return;
                 }

                 checkForDashboardUpdates();
                 startDashboardPolling();
             });

             window.addEventListener('focus', checkForDashboardUpdates);
             window.addEventListener('pageshow', checkForDashboardUpdates);
             window.addEventListener('online', checkForDashboardUpdates);

             window.__clientDashboardPolling = {
                 start: startDashboardPolling,
                 stop: stopDashboardPolling,
                 check: checkForDashboardUpdates,
             };

             startDashboardPolling();
         })();
    </script>
    <script>
        window.addEventListener('pageshow', function(event) {
            const nav = performance.getEntriesByType('navigation')[0];
            if (event.persisted || (nav && nav.type === 'back_forward')) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
