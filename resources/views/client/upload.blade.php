<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $client->name }} — {{ config('app.name') }}</title>
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

    {{-- ═══ SIDEBAR ═══ --}}
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
            <p class="text-[10px] font-mono mt-0.5 {{ $remaining > 10 ? 'text-emerald-400' : ($remaining > 0 ? 'text-amber-400' : 'text-red-400') }}">{{ $remaining }} slots remaining</p>
        </div>
    </aside>

    {{-- ═══ MAIN ═══ --}}
    <main class="flex-1 overflow-y-auto bg-[#070709] scrollbar-thin">

        {{-- HEADER --}}
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
                    <p class="text-[11px] font-mono font-bold mt-0.5 {{ $remaining > 10 ? 'text-emerald-400' : ($remaining > 0 ? 'text-amber-400' : 'text-red-400') }}">{{ $remaining }} left</p>
                </div>
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-500/[0.1] rounded-xl flex items-center justify-center text-indigo-400 ring-1 ring-indigo-500/20">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
            </div>
        </header>

        <div class="px-3 py-4 pb-16 md:pb-8 max-w-[1380px] mx-auto space-y-4 sm:px-6 sm:py-5 sm:space-y-5 xl:px-8 xl:py-6 xl:space-y-6">

            @php
                $consumed     = (int) $client->slots_consumed;
                $creditTone   = $remaining > 10
                    ? 'border-emerald-500/[0.16] bg-emerald-500/[0.05] text-emerald-300'
                    : ($remaining > 0
                        ? 'border-amber-500/[0.16] bg-amber-500/[0.05] text-amber-300'
                        : 'border-red-500/[0.16] bg-red-500/[0.05] text-red-300');
            @endphp

            {{-- Overview card --}}
            <div class="card rounded-[1.75rem] p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Client Portal</p>
                        <h2 class="text-[1.2rem] sm:text-[1.5rem] font-semibold text-white mt-2 tracking-tight leading-tight">Upload. Track. Download.</h2>
                    </div>
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-[0.18em] border border-emerald-500/[0.18] bg-emerald-500/[0.06] text-emerald-300 flex-shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Guest link active
                    </span>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3 rounded-2xl px-4 py-3 border border-white/[0.06] bg-white/[0.02]">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0"></i>
                        <p class="text-[12px] sm:text-[13px] font-medium text-slate-200 leading-6">This guest link is active for 24 hours. View and download your orders before it expires.</p>
                    </div>

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

                    <div class="flex items-center gap-3 rounded-2xl px-4 py-3 border {{ $creditTone }}">
                        <span class="w-2 h-2 rounded-full {{ $remaining > 10 ? 'bg-emerald-400' : ($remaining > 0 ? 'bg-amber-400' : 'bg-red-400') }} flex-shrink-0"></span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Credit Status</p>
                            <p class="text-[13px] font-semibold mt-1">
                                @if($remaining > 10) {{ $remaining }} credits available
                                @elseif($remaining > 0) {{ $remaining }} credits remaining
                                @else No credits — contact admin
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl px-4 py-3 border border-white/[0.06] bg-white/[0.02]">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 flex-shrink-0"></span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Link Window</p>
                            <p class="text-[13px] font-semibold mt-1 text-slate-200">
                                Expires {{ $link->expires_at?->format('d M Y, h:i A') ?? 'at 24 hours' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats row --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="card rounded-2xl p-4">
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
                <div class="card rounded-2xl p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Orders</p>
                    <div class="flex items-end justify-between mt-3 gap-3">
                        <div>
                            <h3 class="text-[2rem] font-extrabold text-white leading-none font-mono">{{ $orders->count() }}</h3>
                            <p class="text-[11px] text-slate-400 mt-2">Total submitted</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-500/[0.08] border border-blue-500/[0.12] flex items-center justify-center text-blue-400 flex-shrink-0">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MAIN GRID: UPLOAD + ACTIVITY --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

                {{-- UPLOAD --}}
                <div class="lg:col-span-7">
                    <div class="card rounded-3xl p-4 sm:p-5 xl:p-6">
                        <div class="flex justify-between items-start gap-3 mb-4 sm:mb-5">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">New Order</p>
                                <h2 class="text-[15px] sm:text-[17px] font-bold text-white tracking-tight mt-2">Secure Upload</h2>
                                <p class="text-[11px] text-slate-400 mt-1">Submit one document per order. Each upload consumes one credit immediately.</p>
                            </div>
                            <div class="w-10 h-10 sm:w-11 sm:h-11 bg-white/[0.03] rounded-2xl flex items-center justify-center border border-white/[0.06] flex-shrink-0">
                                <i data-lucide="shield" class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-400"></i>
                            </div>
                        </div>

                        @if($remaining <= 0)
                            <div class="border-2 border-dashed border-amber-500/20 rounded-3xl p-12 text-center bg-amber-500/[0.02]">
                                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-amber-500/20">
                                    <i data-lucide="alert-triangle" class="w-7 h-7 text-amber-500/60"></i>
                                </div>
                                <h3 class="text-amber-400 font-bold mb-1">No credits remaining</h3>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Contact admin for more slots</p>
                            </div>
                        @else
                            <form id="upload-form" action="{{ route('client.store', $link->token) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <label for="files" id="drop-zone"
                                    class="group block rounded-[1.25rem] sm:rounded-[1.5rem] px-4 sm:px-8 py-6 sm:py-7 text-center cursor-pointer transition-all border border-dashed border-indigo-500/[0.16] bg-indigo-500/[0.03] hover:border-indigo-400/40 hover:bg-indigo-500/[0.05]">
                                    <input type="file" name="file" id="files" required class="sr-only"
                                        accept=".pdf,.doc,.docx,.zip"
                                        onchange="handleFileSelect(this)">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-indigo-500/[0.08] rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 group-hover:scale-105 transition-all border border-indigo-500/[0.12]">
                                        <i data-lucide="file-plus" class="w-6 h-6 sm:w-8 sm:h-8 text-indigo-400"></i>
                                    </div>
                                    <h3 class="text-[13px] sm:text-[15px] font-bold text-white/90 mb-1.5">Drop a file here or click to browse</h3>
                                    <p class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase tracking-[0.18em]">PDF, DOCX, DOC, ZIP &middot; 1 file only &middot; 100MB max</p>
                                    <p id="selected-file-count" class="hidden text-[10px] text-emerald-400 font-bold mt-2 uppercase tracking-[0.18em]"></p>
                                </label>

                                <div id="upload-stage" class="hidden mt-3 space-y-3">
                                    <div id="file-preview" class="bg-white/[0.03] border border-white/[0.06] rounded-2xl divide-y divide-white/[0.04]"></div>

                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                                            <i data-lucide="message-square" class="w-3 h-3 text-indigo-400"></i>
                                            Instructions <span class="text-slate-600 font-medium normal-case tracking-normal ml-1">(optional)</span>
                                        </label>
                                        <textarea name="notes" rows="2"
                                            placeholder="e.g. Priority plagiarism scan on Chapter 2..."
                                            class="w-full bg-white/[0.03] border border-white/[0.07] hover:border-indigo-500/30 focus:border-indigo-500/50 rounded-xl px-4 py-3 text-[12px] text-white placeholder-slate-700 focus:outline-none transition-all resize-none leading-relaxed"></textarea>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button type="button" onclick="resetUpload()"
                                            class="flex items-center gap-1.5 px-4 py-2.5 bg-white/[0.04] hover:bg-white/[0.07] text-slate-500 hover:text-slate-300 text-[11px] font-semibold rounded-xl border border-white/[0.06] transition-all">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
                                        </button>
                                        <button type="submit" id="upload-submit-btn"
                                            class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[12px] font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/20 active:scale-[0.98]">
                                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                            Submit Order
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <div class="mt-4 grid grid-cols-2 gap-2 sm:gap-3">
                            <div class="p-3 sm:p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-slate-300 uppercase tracking-[0.14em] leading-tight">AI Detection<br>Enabled</span>
                            </div>
                            <div class="p-3 sm:p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/[0.12] flex items-center justify-center text-emerald-500 flex-shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-slate-300 uppercase tracking-[0.14em] leading-tight">No Repo<br>Mode</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RECENT ACTIVITY --}}
                <div class="lg:col-span-5 flex flex-col gap-4">
                    <div class="px-1">
                        <h2 class="text-[10px] sm:text-[11px] font-black text-white uppercase tracking-[0.18em]">Recent Activity</h2>
                    </div>

                    <div class="card rounded-3xl p-3 sm:p-4 overflow-y-auto scrollbar-thin max-h-[500px] space-y-2">
                        @forelse($orders as $order)
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-3 sm:p-4 group">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white/[0.04] rounded-xl flex items-center justify-center text-slate-500 group-hover:bg-indigo-500/[0.12] group-hover:text-indigo-400 transition-all border border-white/[0.05] flex-shrink-0">
                                            <i data-lucide="file-text" class="w-5 h-5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-[12px] sm:text-[13px] font-bold text-white truncate leading-snug max-w-[160px] sm:max-w-none">
                        {{ $order->files->first() ? ($order->files->first()->original_name ?? basename($order->files->first()->file_path)) : 'Document' }}
                                            </h4>
                                            @if($order->files_count > 1)
                                                <p class="text-[9px] text-indigo-300 font-bold uppercase tracking-widest mt-1">
                                                    + {{ $order->files_count - 1 }} more file{{ $order->files_count - 1 > 1 ? 's' : '' }}
                                                </p>
                                            @endif
                                            <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                                {{ $order->created_at->format('d M, h:i A') }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($order->status->value === 'delivered')
                                        <span class="status-badge bg-emerald-500/[0.1] text-emerald-400 border border-emerald-500/[0.15] flex-shrink-0">
                                            <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Ready
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

                                <div class="border-t border-white/[0.05] mt-3 pt-3">
                                            @if($order->status->value === 'delivered')
                                                <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('client.link.track', [$link->token, $order->token_view]) }}"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-indigo-500/[0.12] text-indigo-300 text-[9px] font-bold rounded-lg border border-indigo-500/[0.12] transition-all">
                                                <i data-lucide="eye" class="w-3 h-3"></i> View Status
                                            </a>
                                            @if($order->report?->ai_report_path && $order->report?->plag_report_path)
                                                <a href="{{ route('client.link.download', [$link->token, $order->token_view]) }}"
                                                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-500/[0.12] hover:bg-indigo-500/[0.2] text-indigo-300 text-[9px] font-bold rounded-lg border border-indigo-500/[0.2] transition-all">
                                                    <i data-lucide="archive" class="w-3 h-3"></i> Download Both
                                                </a>
                                            @endif
                                            @if($order->report?->ai_report_path)
                                                <a href="{{ route('client.link.download', [$link->token, $order->token_view]) }}?type=ai"
                                                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-red-500/[0.12] text-red-300 text-[9px] font-bold rounded-lg border border-red-500/[0.12] transition-all">
                                                    <i data-lucide="download" class="w-3 h-3"></i> AI Report
                                                </a>
                                            @endif
                                            @if($order->report?->plag_report_path)
                                                <a href="{{ route('client.link.download', [$link->token, $order->token_view]) }}?type=plag"
                                                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-amber-500/[0.12] text-amber-300 text-[9px] font-bold rounded-lg border border-amber-500/[0.12] transition-all">
                                                    <i data-lucide="download" class="w-3 h-3"></i> Plag Report
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2 text-[9px] text-slate-500 font-bold uppercase tracking-widest">
                                                @if($order->status->value === 'processing')
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full pulse-dot"></span> In progress...
                                                @elseif($order->status->value === 'claimed')
                                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Reserved...
                                                @else
                                                    <span class="w-1.5 h-1.5 bg-slate-600 rounded-full pulse-dot"></span> Queued...
                                                @endif
                                            </div>
                                            <a href="{{ route('client.link.track', [$link->token, $order->token_view]) }}"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/[0.03] hover:bg-indigo-500/[0.12] text-indigo-300 text-[9px] font-bold rounded-lg border border-indigo-500/[0.12] transition-all">
                                                <i data-lucide="eye" class="w-3 h-3"></i> View
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-14 text-center">
                                <div class="w-14 h-14 bg-white/[0.03] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/[0.05]">
                                    <i data-lucide="inbox" class="w-6 h-6 text-slate-700"></i>
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">No Orders Yet</p>
                                <p class="text-[11px] text-slate-500 mt-1">Upload a document to get started</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <footer class="px-8 py-6 text-center border-t border-white/[0.04] bg-[#0b0b0f] mt-4">
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.3em]">{{ config('app.name') }} &bull; Advanced plagiarism review</p>
        </footer>
    </main>

    <script>
        lucide.createIcons();

        function handleFileSelect(input) {
            const files   = Array.from(input.files);
            const count   = files.length;
            const stage   = document.getElementById('upload-stage');
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
            document.getElementById('files').value = '';
            document.getElementById('upload-stage').classList.add('hidden');
            document.getElementById('selected-file-count').classList.add('hidden');
            document.getElementById('file-preview').innerHTML = '';
        }

        const zone = document.getElementById('drop-zone');
        if (zone) {
            zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
            zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
            zone.addEventListener('drop', e => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                const input = document.getElementById('files');
                if (e.dataTransfer.files.length) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(e.dataTransfer.files[0]);
                    input.files = dataTransfer.files;
                    handleFileSelect(input);
                }
            });
        }

        document.getElementById('upload-form')?.addEventListener('submit', function () {
            const btn = document.getElementById('upload-submit-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Uploading...';
            }
        });
    </script>
</body>
</html>
