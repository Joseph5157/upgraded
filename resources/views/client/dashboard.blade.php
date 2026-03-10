<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .premium-card {
            background: #FAFBFC;
            border: 1px solid #E2E6EA;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        }

        .upload-area:hover {
            border-color: rgba(99, 102, 241, 0.4);
            background: rgba(99, 102, 241, 0.02);
        }

        .status-glow-pending {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
        }

        .status-glow-delivered {
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.3);
        }

        .status-glow-overdue {
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);
        }

        .sidebar-link-active {
            background: #EEF2FF;
            color: #4F6EF7;
            border-right: 2px solid #4F6EF7;
        }
    </style>
</head>

<body class="h-screen flex bg-[#F0F2F5] text-[#111827] overflow-hidden dark:bg-[#0f1117] dark:text-slate-300">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 h-full border-r border-[#E2E6EA] flex flex-col pt-8 bg-[#F7F8FA] dark:bg-[#0a0a0c] dark:border-[#1e2030]">
        <div class="px-8 mb-12">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="sparkles" class="w-5 h-5 text-[#1A1D23]"></i>
                </div>
                <span class="text-gray-900 font-bold text-lg tracking-tight dark:text-white">PlagExpert</span>
            </div>
        </div>

        <nav class="flex-1 space-y-2">
            <div class="flex items-center gap-4 px-8 py-4 text-sm font-medium sidebar-link-active transition-all dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500">
                <i data-lucide="layout-grid" class="w-5 h-5"></i> Dashboard
            </div>
            <div
                class="flex items-center justify-between px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-[#ECEEF2] cursor-not-allowed select-none">
                <div class="flex items-center gap-4">
                    <i data-lucide="history" class="w-5 h-5"></i> Order History
                </div>
                <span
                    class="text-[8px] font-black uppercase tracking-widest bg-[#EEF2FF] text-[#4F6EF7] border border-[#C7D2FE] px-1.5 py-0.5 rounded">Soon</span>
            </div>
            <a href="{{ route('client.subscription') }}"
                class="flex items-center gap-4 px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-[#ECEEF2] transition-all dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5">
                <i data-lucide="credit-card" class="w-5 h-5"></i> Subscription
            </a>
            <div
                class="flex items-center justify-between px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-[#ECEEF2] cursor-not-allowed select-none">
                <div class="flex items-center gap-4">
                    <i data-lucide="settings" class="w-5 h-5"></i> Settings
                </div>
                <span
                    class="text-[8px] font-black uppercase tracking-widest bg-[#EEF2FF] text-[#4F6EF7] border border-[#C7D2FE] px-1.5 py-0.5 rounded">Soon</span>
            </div>
        </nav>

        <div class="p-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 py-3 border border-[#E2E6EA] rounded-xl text-xs font-bold text-gray-500 hover:text-red-500 hover:bg-red-50 transition-all">
                    <i data-lucide="log-out" class="w-4 h-4"></i> SIGN OUT
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-[#F0F2F5] dark:bg-[#080810]">
        <!-- Top Bar -->
        <header
            class="h-20 border-b border-[#E2E6EA] flex items-center justify-between px-10 bg-[#FAFBFC] sticky top-0 z-10 dark:bg-[#0a0a0c] dark:border-[#1e2030]">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                    Good Morning, {{ auth()->user()->name }}
                    <span class="text-xl">&#128075;</span>
                </h1>
            </div>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3 pr-6 border-r border-[#E2E6EA]">
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Client ID</p>
                        <p class="text-xs font-mono text-[#4F6EF7]">ID-{{ str_pad($client->id, 4, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                    <div
                        class="w-10 h-10 bg-indigo-500/10 rounded-full flex items-center justify-center text-indigo-500 ring-4 ring-indigo-500/5">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                </div>
                <x-dark-mode-toggle />
                <div class="relative group cursor-pointer">
                    <i data-lucide="bell" class="w-5 h-5 hover:text-[#1A1D23] transition-colors"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-indigo-500 rounded-full ring-2 ring-[#F0F2F5]"></span>
                </div>
            </div>
        </header>

        <div class="p-10 max-w-7xl mx-auto space-y-10">
            @if(session('success'))
                <div
                    class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-500 text-sm font-semibold animate-in fade-in slide-in-from-top-4">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    {{ session('success') }}
                </div>
            @endif

            <div class="space-y-3">
                <x-announcements-banner />
            </div>

            {{-- Credit Status Badge --}}
            @php
                $remaining = $client->slots - $client->orders()->whereNotIn('status', ['cancelled'])->count();
            @endphp

            @if($remaining > 10)
                <div class="flex items-center gap-3 px-5 py-3.5 rounded-2xl border border-green-500/20 bg-green-500/5 dark:bg-green-500/10 dark:border-green-500/20">
                    <span
                        class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)] flex-shrink-0"></span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-green-500/70">Credit Status</p>
                        <p class="text-sm font-bold text-green-400">Credits Available: <span
                                class="font-mono">{{ $remaining }}</span></p>
                    </div>
                </div>
            @elseif($remaining > 0)
                <div class="flex items-center gap-3 px-5 py-3.5 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] dark:bg-amber-500/10 dark:border-amber-500/20">
                    <span
                        class="w-2 h-2 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.6)] flex-shrink-0 animate-pulse"></span>
                    <div>
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Credit Status</p>
                        <p class="text-sm font-bold text-amber-700">Low Credits: {{ $remaining }} remaining</p>
                    </div>
                    <span
                        class="ml-auto bg-amber-100 text-amber-600 border border-amber-200 text-[9px] font-bold uppercase px-2 py-1 rounded-lg">Low</span>
                </div>
            @else
                <div class="flex items-center gap-3 px-5 py-3.5 rounded-2xl border border-red-500/30 bg-red-500/5 dark:bg-red-500/10 dark:border-red-500/20">
                    <span
                        class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)] flex-shrink-0 animate-pulse"></span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-red-500/70">Credit Status</p>
                        <p class="text-sm font-bold text-red-400">0 Credits - Please Top Up</p>
                    </div>
                    <span
                        class="ml-auto text-[9px] font-black uppercase tracking-widest text-red-500/60 bg-red-500/10 border border-red-500/20 px-2 py-1 rounded-lg">Depleted</span>
                </div>
            @endif

            <!-- Dashboard Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="premium-card bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl shadow-sm p-6 relative overflow-hidden group dark:bg-[#0a0a0c] dark:border-[#1e2030]">
                    <div class="flex justify-between items-start mb-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest dark:text-slate-500">Credits Used:
                            <span class="font-mono">{{ $client->orders()->whereNotIn('status', ['cancelled'])->count() }}</span>
                        </p>
                        <div
                            class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-4xl font-extrabold text-gray-900 mb-1 font-mono dark:text-white">
                            {{ max(0, $client->slots - $client->orders()->whereNotIn('status', ['cancelled'])->count()) }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-1 dark:text-slate-600">Remaining Credits</p>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <button onclick="document.getElementById('topup-modal').classList.remove('hidden')"
                            class="bg-[#4F6EF7] text-white text-[10px] font-bold px-4 py-1.5 rounded-lg hover:bg-[#3B5BDB] transition-colors">
                            TOP UP
                        </button>
                    </div>
                </div>

                <div class="premium-card bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl shadow-sm p-6 relative overflow-hidden group dark:bg-[#0a0a0c] dark:border-[#1e2030]">
                    <div class="flex justify-between items-start mb-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest dark:text-slate-500">Active Orders</p>
                        <div class="w-8 h-8 bg-blue-500/10 rounded-lg flex items-center justify-center text-blue-500">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-extrabold text-gray-900 mb-1 font-mono dark:text-white">
                        {{ $orders->where('status', '!=', 'delivered')->count() }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-1 dark:text-slate-600">In Processing Flow</p>
                </div>

                <div class="premium-card bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl shadow-sm p-6 relative overflow-hidden group dark:bg-[#0a0a0c] dark:border-[#1e2030]">
                    <div class="flex justify-between items-start mb-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest dark:text-slate-500">Plan Status</p>
                        <div
                            class="w-8 h-8 @if($client->plan_expiry && $client->plan_expiry->isPast()) bg-red-500/10 text-red-500 @else bg-green-500/10 text-green-500 @endif rounded-lg flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-extrabold text-gray-900 mb-1 font-mono dark:text-white">
                        @if($client->plan_expiry && $client->plan_expiry->isPast()) Expired @else Professional @endif
                    </h3>
                    <p class="text-xs text-gray-400 mt-1 dark:text-slate-600">
                        @if($client->plan_expiry) {{ $client->plan_expiry->format('d M, Y') }} @else Perpetual @endif
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Upload Section -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="premium-card bg-[#FAFBFC] border border-[#E2E6EA] rounded-3xl p-8 dark:bg-[#0a0a0c] dark:border-[#1e2030]">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 mb-1 dark:text-white">Secure Upload</h2>
                                <p class="text-xs text-gray-400">Submit your document for non-repository scanning</p>
                            </div>
                            <div
                                class="w-12 h-12 bg-[#F0F2F5] rounded-2xl flex items-center justify-center border border-[#E2E6EA]">
                                <i data-lucide="shield" class="w-6 h-6 text-indigo-400"></i>
                            </div>
                        </div>

                        <form action="{{ route('client.dashboard.upload') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <label for="files"
                                class="upload-area group block border-2 border-dashed border-[#C7D2FE] bg-[#F5F7FF] hover:bg-[#EEF2FF] rounded-2xl p-12 text-center transition-all cursor-pointer dark:bg-[#0d0f1a] dark:border-[#2d3148]">
                                <input type="file" name="files[]" id="files" multiple required class="hidden"
                                    onchange="this.form.submit()">
                                <div
                                    class="w-20 h-20 bg-indigo-500/5 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-all border border-indigo-500/10 shadow-inner">
                                    <i data-lucide="file-plus" class="w-10 h-10 text-indigo-500"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">Drop files here or click</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">PDF, DOCX up
                                    to 50MB</p>
                            </label>
                        </form>

                        <div class="mt-8 grid grid-cols-2 gap-4">
                            <div class="bg-[#F0FDF4] border border-[#BBF7D0] text-green-700 text-[10px] font-bold rounded-xl p-4 flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-green-500/10 flex items-center justify-center text-green-500">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest leading-relaxed">AI
                                    Detection<br>Enabled</span>
                            </div>
                            <div class="bg-[#F0FDF4] border border-[#BBF7D0] text-green-700 text-[10px] font-bold rounded-xl p-4 flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-green-500/10 flex items-center justify-center text-green-500">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest leading-relaxed">No
                                    Repo<br>Mode</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent History -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest">Recent Activity</h2>
                        <span
                            class="bg-[#EEF2FF] text-[#4F6EF7] border border-[#C7D2FE] text-[8px] font-bold uppercase px-2 py-0.5 rounded cursor-not-allowed">Coming
                            Soon</span>
                    </div>

                    <div class="space-y-4">
                        @forelse($orders as $order)
                            <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-5 shadow-sm hover:shadow-md hover:bg-white hover:border-[#C7D2FE] transition-all group dark:bg-[#0a0a0c] dark:border-[#1e2030] dark:hover:border-indigo-500/20">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="bg-[#F0F2F5] border border-[#E2E6EA] text-gray-400 rounded-2xl w-12 h-12 flex items-center justify-center transition-all">
                                            <i data-lucide="file-text" class="w-6 h-6"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 line-clamp-1 dark:text-slate-200">
                                                {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Document' }}
                                            </h4>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1 dark:text-slate-600">
                                                {{ $order->created_at->format('d M, h:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[9px] font-bold uppercase
                                                                                        @if($order->status->value == 'delivered') bg-[#F0FDF4] text-green-600 border border-[#BBF7D0]
                                                                                        @elseif($order->is_overdue) bg-[#FEF2F2] text-red-600 border border-[#FECACA]
                                                                                        @elseif($order->status->value == 'processing') bg-[#EEF2FF] text-[#4F6EF7] border border-[#C7D2FE]
                                                                                        @else bg-[#F0F2F5] text-gray-500 border border-[#E2E6EA] @endif">
                                            @if($order->status->value == 'delivered')
                                                Ready
                                            @elseif($order->status->value == 'processing')
                                                Processing
                                            @elseif($order->is_overdue)
                                                Overdue
                                            @else
                                                Pending
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                @if($order->status->value === 'delivered')
                                    <div class="mt-4 pt-4 border-t border-[#E2E6EA] space-y-3">
                                        {{-- Score metrics + order-level actions --}}
                                        <div class="flex items-center justify-between">
                                            <div class="flex gap-4">
                                                @if($order->report?->ai_percentage !== null)
                                                    <div class="text-center">
                                                        <p class="text-[8px] font-bold text-gray-400 uppercase">AI Score</p>
                                                        <p class="text-xs font-bold text-red-400 font-mono">
                                                            {{ (int) $order->report?->ai_percentage }}%
                                                        </p>
                                                    </div>
                                                @endif
                                                @if($order->report?->plag_percentage !== null)
                                                    <div class="text-center">
                                                        <p class="text-[8px] font-bold text-gray-400 uppercase">Plagiarism</p>
                                                        <p class="text-xs font-bold text-blue-400 font-mono">
                                                            {{ (int) $order->report?->plag_percentage }}%
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex gap-2">
                                                @if($order->report)
                                                    <a href="{{ route('client.download', $order->token_view) }}"
                                                        class="p-2.5 bg-[#4F6EF7] text-white rounded-xl hover:bg-[#3B5BDB] active:scale-95 transition-all shadow-lg shadow-indigo-500/20">
                                                        <i data-lucide="download" class="w-4 h-4"></i>
                                                    </a>
                                                @endif
                                                <form method="POST" action="{{ route('client.orders.delete', $order) }}"
                                                    onsubmit="return confirm('Warning: This action is permanent. All files and reports for this order will be deleted forever and cannot be recovered. Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl border border-red-500/20 transition-all">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Per-file list with individual delete --}}
                                        @if($order->files->isNotEmpty())
                                            <div class="space-y-1.5 pt-1">
                                                @foreach($order->files as $orderFile)
                                                    <div class="flex items-center justify-between bg-[#F0F2F5] border border-[#E2E6EA] rounded-xl px-3 py-2">
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <i data-lucide="file" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
                                                            <span class="text-[11px] text-gray-500 truncate font-mono">{{ basename($orderFile->file_path) }}</span>
                                                        </div>
                                                        <form method="POST"
                                                            action="{{ route('client.orders.files.delete', [$order, $orderFile]) }}"
                                                            onsubmit="return confirm('Delete this file permanently?')"
                                                            class="flex-shrink-0 ml-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="p-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg border border-red-500/20 transition-all">
                                                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @elseif($order->status->value === 'cancelled')
                                    <div class="mt-4 pt-4 border-t border-[#E2E6EA] space-y-3">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                            <i data-lucide="ban" class="w-3 h-3 text-gray-400"></i> Order Cancelled
                                        </p>

                                        @if($order->refundRequest)
                                            @if($order->refundRequest->status === 'pending')
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/10 text-amber-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-amber-500/20">
                                                    <i data-lucide="clock" class="w-3 h-3"></i> Refund Pending Review
                                                </span>
                                            @elseif($order->refundRequest->status === 'approved')
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F0FDF4] text-green-600 border border-[#BBF7D0] text-[10px] font-bold rounded-lg">
                                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Refund Approved - Credit Returned
                                                </span>
                                            @elseif($order->refundRequest->status === 'rejected')
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-500/10 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20">
                                                    <i data-lucide="x-circle" class="w-3 h-3"></i> Refund Rejected
                                                    @if($order->refundRequest->admin_note)
                                                        - {{ $order->refundRequest->admin_note }}
                                                    @endif
                                                </span>
                                            @endif
                                        @else
                                            <button onclick="document.getElementById('refund-modal-{{ $order->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center gap-1.5 bg-[#EEF2FF] text-[#4F6EF7] border border-[#C7D2FE] text-[10px] font-bold rounded-lg px-3 py-1.5 hover:bg-[#E0E7FF] transition-all">
                                                <i data-lucide="refresh-ccw" class="w-3 h-3"></i> Request Refund
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Refund Modal --}}
                                    <div id="refund-modal-{{ $order->id }}"
                                        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 dark:bg-black/70"
                                        onclick="if(event.target===this)this.classList.add('hidden')">
                                        <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-3xl w-full max-w-md p-8 shadow-2xl dark:bg-[#0a0a0c] dark:border-[#1e2030]" onclick="event.stopPropagation()">
                                            <div class="flex justify-between items-center mb-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                                                        <i data-lucide="refresh-ccw" class="w-5 h-5"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-gray-900 font-bold dark:text-white">Request Refund</h3>
                                                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">Credit Slot Recovery</p>
                                                    </div>
                                                </div>
                                                <button onclick="document.getElementById('refund-modal-{{ $order->id }}').classList.add('hidden')"
                                                    class="text-gray-400 hover:text-gray-900 transition-colors">
                                                    <i data-lucide="x" class="w-5 h-5"></i>
                                                </button>
                                            </div>

                                            <form method="POST" action="{{ route('client.orders.refund', $order) }}" class="space-y-4">
                                                @csrf
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Reason (Optional)</label>
                                                    <textarea name="reason" rows="3" placeholder="Briefly describe why you are requesting a refund..."
                                                        class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl px-4 py-3 text-sm text-gray-900 focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-colors placeholder-gray-300 resize-none dark:bg-[#13151c] dark:border-[#1e2030] dark:text-white dark:placeholder-slate-600"></textarea>
                                                </div>
                                                <p class="text-[10px] text-gray-400 leading-relaxed">Your credit slot will be refunded after admin approval.</p>
                                                <button type="submit"
                                                    class="w-full py-3.5 bg-[#4F6EF7] text-white hover:bg-[#3B5BDB] text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-[#4F6EF7] transition-all flex justify-center items-center gap-2">
                                                    <i data-lucide="send" class="w-4 h-4"></i> Submit Refund Request
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                            @if($order->status->value === 'processing')
                                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span> Processing...
                                            @else
                                                <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-pulse"></span> In Queue...
                                            @endif
                                        </div>

                                        @if($order->due_at->isPast())
                                            <form method="POST" action="{{ route('client.orders.cancel', $order) }}">
                                                @csrf
                                                <button type="submit"
                                                    onclick="return confirm('Cancel this order? Your credit slot will be refunded.')"
                                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                                    <i data-lucide="x-circle" class="w-3 h-3"></i> Cancel Order
                                                </button>
                                            </form>
                                        @else
                                            <div id="timer-wrap-{{ $order->id }}" class="flex items-center gap-1.5 px-3 py-1 bg-[#EEF2FF] border border-[#C7D2FE] rounded-lg">
                                                <i data-lucide="clock" class="w-3 h-3 text-[#4F6EF7]"></i>
                                                <span class="countdown-timer text-[10px] font-mono text-[#4F6EF7]"
                                                    data-due="{{ $order->due_at->toIso8601String() }}"
                                                    data-order-id="{{ $order->id }}"
                                                    data-cancel-url="{{ route('client.orders.cancel', $order) }}">--:--</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="py-20 text-center">
                                <div
                                    class="w-16 h-16 bg-[#F0F2F5] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#E2E6EA]">
                                    <i data-lucide="inbox" class="w-8 h-8 text-[#9CA3AF]"></i>
                                </div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">No Recent Orders</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <footer class="p-10 text-center border-t border-[#E2E6EA] bg-[#F0F2F5] dark:bg-[#0a0a0c] dark:border-[#1e2030] dark:text-slate-700">
            <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">PlagExpert &bull; Advanced Plagiarism
                Prevention</p>
        </footer>
    </main>

    <script>
        lucide.createIcons();

        function updateTimers() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const diff       = new Date(timer.dataset.due).getTime() - Date.now();
                const orderId    = timer.dataset.orderId;
                const cancelUrl  = timer.dataset.cancelUrl;
                const wrap       = document.getElementById('timer-wrap-' + orderId);

                if (diff <= 0 && wrap) {
                    wrap.outerHTML = `
                        <form method="POST" action="${cancelUrl}" onsubmit="return confirm('Cancel this order? Your credit slot will be refunded.')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                <i data-lucide="x-circle" class="w-3 h-3"></i> Cancel Order
                            </button>
                        </form>`;
                    lucide.createIcons();
                    return;
                }

                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);
                timer.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            });
        }

        setInterval(updateTimers, 1000);
        updateTimers();
    </script>
    {{-- Top-up Modal --}}
    <div id="topup-modal"
        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 dark:bg-black/70"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl dark:bg-[#0a0a0c] dark:border-[#1e2030]"
            onclick="event.stopPropagation()">

            {{-- Header --}}
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 font-bold dark:text-white">Request Top-up</h3>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">Add Credits to Your
                            Account</p>
                    </div>
                </div>
                <button onclick="document.getElementById('topup-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-900 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('client.topup.store') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Package Selector --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Select
                        Package</label>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <button type="button" onclick="setSlots(50)"
                            class="slot-preset py-3 bg-[#F0F2F5] border border-[#E2E6EA] text-gray-600 hover:bg-[#EEF2FF] hover:text-[#4F6EF7] hover:border-[#C7D2FE] rounded-xl text-xs font-bold transition-all">
                            50 Slots
                        </button>
                        <button type="button" onclick="setSlots(100)"
                            class="slot-preset py-3 bg-[#F0F2F5] border border-[#E2E6EA] text-gray-600 hover:bg-[#EEF2FF] hover:text-[#4F6EF7] hover:border-[#C7D2FE] rounded-xl text-xs font-bold transition-all">
                            100 Slots
                        </button>
                        <button type="button" onclick="setSlots(200)"
                            class="slot-preset py-3 bg-[#F0F2F5] border border-[#E2E6EA] text-gray-600 hover:bg-[#EEF2FF] hover:text-[#4F6EF7] hover:border-[#C7D2FE] rounded-xl text-xs font-bold transition-all">
                            200 Slots
                        </button>
                    </div>
                    <input type="number" name="amount_requested" id="slot-input" min="1"
                        placeholder="Or enter custom amount..." oninput="updatePrice(this.value)"
                        class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl px-4 py-3 text-sm text-gray-900 focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-colors placeholder-gray-300 dark:bg-[#13151c] dark:border-[#1e2030] dark:text-white dark:placeholder-slate-600"
                        required>
                </div>

                {{-- Price Preview --}}
                <div class="p-4 bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Payable</p>
                        <p id="price-display" class="text-2xl font-bold text-gray-900 mt-0.5 font-mono">&#8377;0</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Rate</p>
                        <p class="text-xs text-[#4F6EF7] font-bold font-mono">
                            &#8377;{{ number_format($client->price_per_file, 0) }} /
                            slot</p>
                    </div>
                </div>

                {{-- UPI Payment Instructions --}}
                <div class="p-4 bg-[#F5F6F8] border border-[#E2E6EA] rounded-2xl space-y-3 dark:bg-[#13151c] dark:border-[#1e2030]">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Payment Instructions</p>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 bg-green-500/10 rounded-lg flex items-center justify-center text-green-500 flex-shrink-0">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">UPI ID</p>
                            <p class="text-sm font-bold text-gray-900 font-mono">your-upi@ybl</p>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 leading-relaxed">Send the exact amount above to the UPI ID,
                        then paste your <span class="text-[#4F6EF7]">Transaction / UTR Reference Number</span> below.
                        Your credits will be added after Admin verification.</p>
                </div>

                {{-- Transaction ID --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Transaction
                        / UTR Reference Number</label>
                    <input type="text" name="transaction_id" required placeholder="e.g. 123456789012"
                        class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl px-4 py-3 text-sm text-gray-900 focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-colors placeholder-gray-300 dark:bg-[#13151c] dark:border-[#1e2030] dark:text-white dark:placeholder-slate-600">
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-4 bg-[#4F6EF7] text-white hover:bg-[#3B5BDB] text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-[#4F6EF7] transition-all flex justify-center items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Submit Top-up Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const pricePerSlot = {{ $client->price_per_file ?? 0 }};

        function setSlots(n) {
            document.getElementById('slot-input').value = n;
            updatePrice(n);
        }

        function updatePrice(val) {
            const n = parseInt(val) || 0;
            document.getElementById('price-display').textContent = '\u20B9' + (n * pricePerSlot).toLocaleString('en-IN');
        }
    </script>
</body>

</html>