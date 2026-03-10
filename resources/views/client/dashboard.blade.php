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
    </style>
</head>

<body class="h-screen flex bg-[#070709] text-slate-400 overflow-hidden">

    {{-- 
         SIDEBAR
     --}}
    <aside class="w-[220px] flex-shrink-0 h-full border-r border-white/[0.05] flex flex-col bg-[#0b0b0f]">

        {{-- Brand --}}
        <div class="px-5 pt-6 pb-8">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30 flex-shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
                </div>
                <span class="font-bold text-white text-[15px] tracking-tight">PlagExpert</span>
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
                    Order History
                </div>
                <span class="text-[7px] font-black uppercase tracking-widest text-indigo-500/40 bg-indigo-500/[0.06] border border-indigo-500/[0.1] px-1.5 py-0.5 rounded">Soon</span>
            </div>
            <a href="{{ route('client.subscription') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-200 hover:bg-white/[0.04] transition-all">
                <i data-lucide="credit-card" class="w-4 h-4 flex-shrink-0"></i>
                Subscription
            </a>
            <div class="flex items-center justify-between px-4 py-2.5 rounded-xl text-slate-600 cursor-not-allowed select-none text-sm font-medium">
                <div class="flex items-center gap-3">
                    <i data-lucide="settings" class="w-4 h-4 flex-shrink-0"></i>
                    Settings
                </div>
                <span class="text-[7px] font-black uppercase tracking-widest text-indigo-500/40 bg-indigo-500/[0.06] border border-indigo-500/[0.1] px-1.5 py-0.5 rounded">Soon</span>
            </div>
        </nav>

        {{-- Sign out --}}
        <div class="px-2 pb-5 pt-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-[11px] font-semibold text-slate-600 hover:text-red-400 hover:bg-red-500/[0.06] hover:border-red-500/[0.1] border border-transparent transition-all">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                </button>
            </form>
        </div>
    </aside>

    {{-- 
         MAIN
     --}}
    <main class="flex-1 overflow-y-auto bg-[#070709] scrollbar-thin">

        {{-- TOP HEADER --}}
        <header class="h-14 border-b border-white/[0.05] flex items-center justify-between px-8 bg-[#070709]/80 backdrop-blur-xl sticky top-0 z-20">
            <div class="flex items-center gap-2">
                <h1 class="text-[15px] font-semibold text-white/90">
                    Good Morning, {{ auth()->user()->name }}
                </h1>
                <span class="text-base"></span>
            </div>
            <div class="flex items-center gap-5">
                <div class="flex items-center gap-3 pr-5 border-r border-white/[0.05]">
                    <div class="text-right">
                        <p class="text-[9px] text-slate-600 font-bold uppercase tracking-[0.2em]">Client ID</p>
                        <p class="text-[11px] font-mono font-bold text-indigo-400 bg-indigo-500/[0.08] px-2 py-0.5 rounded-md mt-0.5">
                            ID-{{ str_pad($client->id, 4, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                    <div class="w-9 h-9 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 ring-1 ring-indigo-500/20">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="relative cursor-pointer">
                    <i data-lucide="bell" class="w-[18px] h-[18px] text-slate-500 hover:text-slate-300 transition-colors"></i>
                    <span class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 bg-indigo-500 rounded-full ring-2 ring-[#070709]"></span>
                </div>
            </div>
        </header>

        {{--  ANNOUNCEMENTS BANNER  --}}
        <x-announcements-banner />

        <div class="px-8 py-7 max-w-[1380px] mx-auto space-y-7">

            {{-- Flash --}}
            @if(session('success'))
                <div class="flex items-center gap-3 px-5 py-3.5 bg-emerald-500/[0.08] border border-emerald-500/20 rounded-2xl text-emerald-400 text-[13px] font-semibold">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{--  CREDIT STATUS BANNER  --}}
            @php $remaining = $client->slots - $client->orders()->count(); @endphp

            @if($remaining > 10)
                <div class="flex items-center gap-4 px-5 py-4 rounded-2xl border border-emerald-500/[0.15] bg-gradient-to-r from-emerald-500/[0.06] to-transparent">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 flex-shrink-0" style="box-shadow:0 0 10px rgba(52,211,153,0.5)"></span>
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.25em] text-emerald-500/60">Credit Status</p>
                        <p class="text-[13px] font-bold text-emerald-400 mt-0.5">Credits Available: <span class="font-mono">{{ $remaining }}</span></p>
                    </div>
                </div>
            @elseif($remaining > 0)
                <div class="flex items-center gap-4 px-5 py-4 rounded-2xl border border-amber-500/20 bg-gradient-to-r from-amber-500/[0.06] to-transparent">
                    <span class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0 pulse-dot" style="box-shadow:0 0 10px rgba(251,191,36,0.5)"></span>
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.25em] text-amber-500/60">Credit Status</p>
                        <p class="text-[13px] font-bold text-amber-400 mt-0.5">Low Credits  <span class="font-mono">{{ $remaining }}</span> remaining</p>
                    </div>
                    <span class="ml-auto text-[8px] font-black uppercase tracking-widest text-amber-500/70 bg-amber-500/[0.08] border border-amber-500/[0.15] px-2.5 py-1 rounded-lg">Low</span>
                </div>
            @else
                <div class="flex items-center gap-4 px-5 py-4 rounded-2xl border border-red-500/20 bg-gradient-to-r from-red-500/[0.06] to-transparent">
                    <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0 pulse-dot" style="box-shadow:0 0 10px rgba(239,68,68,0.5)"></span>
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.25em] text-red-500/60">Credit Status</p>
                        <p class="text-[13px] font-bold text-red-400 mt-0.5">0 Credits  Please Top Up</p>
                    </div>
                    <span class="ml-auto text-[8px] font-black uppercase tracking-widest text-red-500/70 bg-red-500/[0.08] border border-red-500/[0.15] px-2.5 py-1 rounded-lg">Depleted</span>
                </div>
            @endif

            {{--  STAT CARDS  --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Credits Card --}}
                <div class="card card-glow p-6 rounded-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-28 h-28 bg-indigo-500/[0.04] rounded-full -translate-y-1/2 translate-x-1/2 blur-xl pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-5">
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em]">
                            Credits Used &nbsp;<span class="font-mono text-slate-500">{{ $client->orders()->count() }}</span>
                        </p>
                        <div class="w-8 h-8 bg-indigo-500/[0.1] rounded-lg flex items-center justify-center text-indigo-400 border border-indigo-500/[0.15]">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-[2.75rem] font-extrabold text-white leading-none font-mono tracking-tight">
                        {{ max(0, $client->slots - $client->orders()->count()) }}
                    </h3>
                    <p class="text-[11px] text-slate-600 mt-1.5 font-medium">Remaining Credits</p>
                    <div class="mt-5">
                        <button onclick="document.getElementById('topup-modal').classList.remove('hidden')"
                            class="px-4 py-1.5 bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-bold uppercase tracking-widest rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
                            Top Up
                        </button>
                    </div>
                </div>

                {{-- Active Orders Card --}}
                <div class="card card-glow p-6 rounded-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-28 h-28 bg-blue-500/[0.04] rounded-full -translate-y-1/2 translate-x-1/2 blur-xl pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-5">
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em]">Active Orders</p>
                        <div class="w-8 h-8 bg-blue-500/[0.1] rounded-lg flex items-center justify-center text-blue-400 border border-blue-500/[0.15]">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-[2.75rem] font-extrabold text-white leading-none font-mono tracking-tight">
                        {{ $orders->where('status', '!=', 'delivered')->count() }}
                    </h3>
                    <p class="text-[11px] text-slate-600 mt-1.5 font-medium">In Processing Flow</p>
                </div>

                {{-- Plan Status Card --}}
                <div class="card card-glow p-6 rounded-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-28 h-28 bg-emerald-500/[0.04] rounded-full -translate-y-1/2 translate-x-1/2 blur-xl pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-5">
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em]">Plan Status</p>
                        <div class="w-8 h-8 @if($client->plan_expiry && $client->plan_expiry->isPast()) bg-red-500/[0.1] text-red-400 border-red-500/[0.15] @else bg-emerald-500/[0.1] text-emerald-400 border-emerald-500/[0.15] @endif rounded-lg flex items-center justify-center border">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-extrabold text-white leading-none tracking-tight">
                        @if($client->plan_expiry && $client->plan_expiry->isPast()) Expired @else Professional @endif
                    </h3>
                    <p class="text-[11px] text-slate-600 mt-1.5 font-medium">
                        @if($client->plan_expiry) {{ $client->plan_expiry->format('d M, Y') }} @else Perpetual @endif
                    </p>
                </div>
            </div>

            {{--  MAIN GRID: UPLOAD + ACTIVITY  --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- UPLOAD SECTION --}}
                <div class="lg:col-span-7">
                    <div class="card rounded-3xl p-7">
                        <div class="flex justify-between items-start mb-7">
                            <div>
                                <h2 class="text-[17px] font-bold text-white tracking-tight">Secure Upload</h2>
                                <p class="text-[11px] text-slate-600 mt-1">Submit your document for non-repository scanning</p>
                            </div>
                            <div class="w-11 h-11 bg-white/[0.04] rounded-2xl flex items-center justify-center border border-white/[0.06]">
                                <i data-lucide="shield" class="w-5 h-5 text-indigo-400"></i>
                            </div>
                        </div>

                        <form action="{{ route('client.dashboard.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label for="files" class="upload-zone group block rounded-[1.5rem] p-10 text-center cursor-pointer">
                                <input type="file" name="files[]" id="files" multiple required class="hidden" onchange="this.form.submit()">
                                <div class="w-16 h-16 bg-indigo-500/[0.08] rounded-2xl flex items-center justify-center mx-auto mb-5 group-hover:scale-105 transition-all border border-indigo-500/[0.12]">
                                    <i data-lucide="file-plus" class="w-8 h-8 text-indigo-400"></i>
                                </div>
                                <h3 class="text-[15px] font-bold text-white/90 mb-1.5">Drop files here or click</h3>
                                <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">PDF, DOCX up to 50MB</p>
                            </label>
                        </form>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <div class="p-3.5 bg-emerald-500/[0.05] rounded-xl border border-emerald-500/[0.1] flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-tight">AI Detection<br>Enabled</span>
                            </div>
                            <div class="p-3.5 bg-emerald-500/[0.05] rounded-xl border border-emerald-500/[0.1] flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-tight">No Repo<br>Mode</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RECENT ACTIVITY --}}
                <div class="lg:col-span-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-[11px] font-black text-white uppercase tracking-[0.2em]">Recent Activity</h2>
                        <span class="text-[7px] font-black uppercase tracking-widest text-indigo-400/40 bg-indigo-500/[0.05] border border-indigo-500/[0.08] px-2 py-0.5 rounded cursor-not-allowed">Coming Soon</span>
                    </div>

                    <div class="space-y-3 overflow-y-auto scrollbar-thin max-h-[540px] pr-0.5">
                        @forelse($orders as $order)
                            <div class="card card-glow p-4 rounded-2xl group">

                                {{-- File row --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 bg-white/[0.04] rounded-xl flex items-center justify-center text-slate-500 group-hover:bg-indigo-500/[0.12] group-hover:text-indigo-400 transition-all border border-white/[0.05] flex-shrink-0">
                                            <i data-lucide="file-text" class="w-5 h-5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-[13px] font-bold text-slate-200 truncate leading-snug">
                                                {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Document' }}
                                            </h4>
                                            <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">
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
                                    @elseif(isset($order->is_overdue) && $order->is_overdue)
                                        <span class="status-badge bg-red-500/[0.1] text-red-400 border border-red-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-red-400 pulse-dot"></span> Overdue
                                        </span>
                                    @elseif($order->status->value === 'processing')
                                        <span class="status-badge bg-blue-500/[0.1] text-blue-400 border border-blue-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-blue-400 pulse-dot"></span> Processing
                                        </span>
                                    @else
                                        <span class="status-badge bg-slate-500/[0.08] text-slate-500 border border-slate-500/[0.1] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-slate-500 pulse-dot"></span> Pending
                                        </span>
                                    @endif
                                </div>

                                {{-- Divider --}}
                                <div class="border-t border-white/[0.04] mt-3 pt-3">

                                    {{-- DELIVERED STATE --}}
                                    @if($order->status->value === 'delivered')
                                        <div class="flex items-center justify-between">
                                            <div class="flex gap-4">
                                                @if($order->report?->ai_percentage !== null)
                                                    <div>
                                                        <p class="text-[8px] font-bold text-slate-700 uppercase tracking-widest">AI Score</p>
                                                        <p class="text-[12px] font-bold text-red-400 font-mono mt-0.5">{{ (int) $order->report?->ai_percentage }}%</p>
                                                    </div>
                                                @endif
                                                @if($order->report?->plag_percentage !== null)
                                                    <div>
                                                        <p class="text-[8px] font-bold text-slate-700 uppercase tracking-widest">Plagiarism</p>
                                                        <p class="text-[12px] font-bold text-blue-400 font-mono mt-0.5">{{ (int) $order->report?->plag_percentage }}%</p>
                                                    </div>
                                                @endif
                                            </div>
                                            @if($order->report)
                                                <a href="{{ route('client.download', $order->token_view) }}"
                                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-bold rounded-lg transition-all shadow-lg shadow-indigo-500/20 active:scale-95">
                                                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
                                                </a>
                                            @endif
                                        </div>

                                    {{-- CANCELLED STATE --}}
                                    @elseif($order->status->value === 'cancelled')
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                                <i data-lucide="ban" class="w-3 h-3"></i> Order Cancelled
                                            </p>
                                            @php
                                                $existingRefund = $order->refundRequest ?? null;
                                            @endphp
                                            @if(!$existingRefund)
                                                <button onclick="openRefundModal({{ $order->id }})"
                                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/[0.08] hover:bg-indigo-500/[0.15] text-indigo-400 text-[10px] font-bold rounded-lg border border-indigo-500/[0.15] transition-all">
                                                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> Request Refund
                                                </button>
                                            @elseif($existingRefund->status === 'pending')
                                                <span class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/[0.08] text-amber-400 text-[10px] font-bold rounded-lg border border-amber-500/[0.15]">
                                                    <i data-lucide="clock" class="w-3 h-3"></i> Refund Pending
                                                </span>
                                            @elseif($existingRefund->status === 'approved')
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
                                            <div class="flex items-center gap-2 text-[9px] text-slate-600 font-bold uppercase tracking-widest">
                                                @if($order->status->value === 'processing')
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full pulse-dot"></span>
                                                    Processing...
                                                @else
                                                    <span class="w-1.5 h-1.5 bg-slate-600 rounded-full pulse-dot"></span>
                                                    In Queue...
                                                @endif
                                            </div>

                                            @if($order->due_at->isPast())
                                                <form method="POST" action="{{ route('client.orders.cancel', $order) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Cancel this order? Your credit slot will be refunded.')"
                                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/[0.08] hover:bg-red-500/[0.15] text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-red-500/[0.15] transition-all">
                                                        <i data-lucide="x-circle" class="w-3 h-3"></i> Cancel
                                                    </button>
                                                </form>
                                            @else
                                                <div id="timer-wrap-{{ $order->id }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/[0.06] rounded-lg border border-indigo-500/[0.1]">
                                                    <i data-lucide="clock" class="w-3 h-3 text-indigo-400/60"></i>
                                                    <span class="countdown-timer text-[10px] font-mono font-bold text-indigo-400"
                                                        data-due="{{ $order->due_at->toIso8601String() }}"
                                                        data-order-id="{{ $order->id }}"
                                                        data-cancel-url="{{ route('client.orders.cancel', $order) }}">--:--</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-16 text-center">
                                <div class="w-14 h-14 bg-white/[0.03] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/[0.05]">
                                    <i data-lucide="inbox" class="w-6 h-6 text-slate-700"></i>
                                </div>
                                <p class="text-[10px] font-bold text-slate-700 uppercase tracking-[0.2em]">No Recent Orders</p>
                                <p class="text-[11px] text-slate-700 mt-1">Upload a document to get started</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <footer class="px-8 py-6 text-center border-t border-white/[0.04] bg-[#0b0b0f] mt-4">
            <p class="text-[9px] font-bold text-slate-700 uppercase tracking-[0.3em]">PlagExpert &bull; Advanced Plagiarism Prevention</p>
        </footer>
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
                        <h3 class="text-[15px] font-bold text-white">Request Top-up</h3>
                        <p class="text-[9px] text-slate-600 uppercase tracking-widest mt-0.5">Add Credits to Your Account</p>
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
                    <label class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-2.5">Select Package</label>
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
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Total Payable</p>
                        <p id="price-display" class="text-2xl font-extrabold text-white mt-0.5 font-mono">0</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">Rate</p>
                        <p class="text-[11px] text-indigo-400 font-bold font-mono mt-0.5">{{ number_format($client->price_per_file, 0) }} / slot</p>
                    </div>
                </div>

                <div class="p-4 bg-white/[0.02] border border-white/[0.05] rounded-2xl space-y-3">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-600">Payment Instructions</p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-emerald-500/[0.1] rounded-lg flex items-center justify-center text-emerald-500 flex-shrink-0">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">UPI ID</p>
                            <p class="text-sm font-bold text-white font-mono mt-0.5">your-upi@ybl</p>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-600 leading-relaxed">Send the exact amount to the UPI ID, then paste your <span class="text-indigo-400 font-semibold">Transaction / UTR Reference Number</span> below.</p>
                </div>

                <div>
                    <label class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-2">Transaction / UTR Reference</label>
                    <input type="text" name="transaction_id" required placeholder="e.g. 123456789012"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500/50 transition-colors placeholder-slate-700 font-mono">
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold uppercase tracking-[0.25em] rounded-xl transition-all flex justify-center items-center gap-2 shadow-lg shadow-indigo-500/20">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit Top-up Request
                </button>
            </form>
        </div>
    </div>

    {{-- 
         REFUND MODAL
     --}}
    <div id="refund-modal"
        class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-md p-7 shadow-2xl" onclick="event.stopPropagation()">

            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/[0.2]">
                        <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-bold text-white">Request Refund</h3>
                        <p class="text-[9px] text-slate-600 uppercase tracking-widest mt-0.5">Credit Slot Recovery</p>
                    </div>
                </div>
                <button onclick="document.getElementById('refund-modal').classList.add('hidden')"
                    class="text-slate-600 hover:text-slate-300 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="refund-form" action="#" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-2">Reason for Refund</label>
                    <textarea name="reason" rows="3" required
                        placeholder="Explain why you are requesting a refund..."
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500/50 transition-colors placeholder-slate-700 resize-none"></textarea>
                </div>
                <button type="submit"
                    class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold uppercase tracking-[0.25em] rounded-xl transition-all flex justify-center items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit Refund Request
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        //  Timers 
        function updateTimers() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const dueAt     = new Date(timer.dataset.due).getTime();
                const diff      = dueAt - Date.now();
                const orderId   = timer.dataset.orderId;
                const cancelUrl = timer.dataset.cancelUrl;
                const wrap      = document.getElementById('timer-wrap-' + orderId);

                if (diff <= 0 && wrap) {
                    wrap.outerHTML = `
                        <form method="POST" action="${cancelUrl}" onsubmit="return confirm('Cancel this order? Your credit slot will be refunded.')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/[0.08] hover:bg-red-500/[0.15] text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-red-500/[0.15] transition-all">
                                <i data-lucide="x-circle" class="w-3 h-3"></i> Cancel
                            </button>
                        </form>`;
                    lucide.createIcons();
                    return;
                }

                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                timer.textContent = `${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();

        //  Top-up 
        const pricePerSlot = {{ $client->price_per_file ?? 0 }};

        function setSlots(n) {
            document.getElementById('slot-input').value = n;
            updatePrice(n);
        }

        function updatePrice(val) {
            const n = parseInt(val) || 0;
            document.getElementById('price-display').textContent = '' + (n * pricePerSlot).toLocaleString('en-IN');
        }

        //  Refund Modal 
        function openRefundModal(orderId) {
            document.getElementById('refund-form').action = '/client/orders/' + orderId + '/refund';
            document.getElementById('refund-modal').classList.remove('hidden');
        }
    </script>
</body>
</html>