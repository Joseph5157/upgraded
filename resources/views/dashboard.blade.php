<x-vendor-layout title="Dashboard">

    <div class="space-y-3">
        <x-announcements-banner />
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

        {{-- Available Pool --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-indigo-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-indigo-400 bg-indigo-400/5 border border-indigo-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Pool</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['available_pool'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Available Orders</p>
        </div>

        {{-- Active Jobs --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-blue-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-blue-400 bg-blue-400/5 border border-blue-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Active</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['active_jobs'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">In Progress</p>
        </div>

        {{-- Today Delivered --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-emerald-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Today</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['total_checked_today'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Delivered Today</p>
        </div>

        {{-- Total Delivered --}}
        <div class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-purple-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-purple-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-purple-400 bg-purple-400/5 border border-purple-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">All Time</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['total_delivered'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Total Delivered</p>
        </div>
    </div>

    {{-- ===== PRIMARY CONTENT GRID ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">

        {{-- LEFT COLUMN (2/3 width) --}}
        <div class="xl:col-span-2 space-y-4 sm:space-y-5 min-w-0">

            {{-- ===== MY WORKSPACE ===== --}}
            <div id="workspace" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-white/[0.04]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">My Workspace</h2>
                        @if($myWorkspace->count() > 0)
                            <span
                                class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $myWorkspace->count() }}</span>
                        @endif
                    </div>
                </div>

                {{-- Mobile cards --}}
                <div class="sm:hidden">
                    @forelse($myWorkspace as $order)
                        @php $isOverdue = $order->is_overdue; @endphp
                        @if ($loop->first)
                            <div class="px-4 pt-4 pb-2 border-t border-gray-100 dark:border-white/[0.04]">
                                <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @endif
                        <div class="min-w-[240px] max-w-[240px] snap-start rounded-2xl border border-white/[0.06] bg-black/10 dark:bg-white/[0.02] p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div
                                    class="w-9 h-9 bg-indigo-600/10 rounded-xl flex items-center justify-center text-indigo-400 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                @if($isOverdue)
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-red-400 bg-red-500/5 border border-red-500/10 px-2 py-1 rounded-full flex-shrink-0">
                                        <span class="w-1 h-1 bg-red-400 rounded-full"></span> Overdue
                                    </span>
                                @elseif($order->status->value === 'processing')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-blue-400 bg-blue-500/5 border border-blue-500/10 px-2 py-1 rounded-full flex-shrink-0">
                                        <span class="w-1 h-1 bg-blue-400 rounded-full animate-pulse"></span> Processing
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] px-2 py-1 rounded-full flex-shrink-0">
                                        <span class="w-1 h-1 bg-slate-500 rounded-full"></span> Pending
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 min-w-0">
                                <p class="text-xs font-semibold text-slate-200 truncate dark:text-slate-200">
                                    {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Document' }}
                                </p>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                    @if($order->client)
                                        <span class="text-[9px] text-gray-500 dark:text-slate-500 truncate">{{ $order->client->name }}</span>
                                    @endif
                                    <span class="text-[8px] font-bold px-1 py-0.5 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
                                </div>
                                @if($order->notes)
                                    <p class="text-[9px] text-amber-400/80 mt-1.5 leading-relaxed line-clamp-2 min-h-[2rem]">
                                        <i data-lucide="message-square" class="w-2.5 h-2.5 inline-block mr-0.5 -mt-0.5"></i>{{ $order->notes }}
                                    </p>
                                @else
                                    <div class="min-h-[2rem]"></div>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2 mt-3">
                                @if($order->files->first())
                                    <a href="{{ route('orders.files.download', [$order, $order->files->first()]) }}"
                                        class="inline-flex items-center justify-center gap-1 px-2.5 py-2 text-[10px] font-semibold text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 border border-gray-200 dark:bg-white/[0.05] dark:border-white/[0.08] dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] rounded-lg transition-all">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Download
                                    </a>
                                @else
                                    <div></div>
                                @endif
                                <form action="{{ route('orders.unclaim', $order) }}" method="POST" class="inline">
                                    @csrf
                                    <button
                                        class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-2 text-[10px] font-bold text-red-500 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all border border-red-500/20">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        Release
                                    </button>
                                </form>
                                @if($order->status->value == 'pending')
                                    <form action="{{ route('orders.status', $order) }}" method="POST" class="inline col-span-2">
                                        @csrf
                                        <input type="hidden" name="status" value="processing">
                                        <button
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-all shadow-lg shadow-emerald-600/10">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Start
                                        </button>
                                    </form>
                                @else
                                    <button
                                        onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.remove('hidden')"
                                        class="col-span-2 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-lg shadow-indigo-600/10">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Upload Reports
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if ($loop->last)
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="px-6 py-14 text-center border-t border-gray-100 dark:border-white/[0.04]">
                            <div class="flex flex-col items-center gap-3">
                                <div
                                    class="w-12 h-12 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] rounded-2xl flex items-center justify-center text-gray-400 dark:text-slate-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">No active jobs</p>
                                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Claim an order from the queue below</p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Desktop table --}}
                <div class="hidden sm:block overflow-x-auto">
                <table class="w-full min-w-0">
                    <thead>
                        <tr
                            class="text-[9px] text-gray-400 font-semibold uppercase tracking-widest border-b border-gray-100 dark:text-slate-600 dark:border-white/[0.04]">
                            <th class="text-left px-3 sm:px-6 py-3 font-semibold">File</th>
                            <th class="text-center px-2 sm:px-4 py-3 font-semibold hidden sm:table-cell">Status</th>
                            <th class="text-right px-3 sm:px-6 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @forelse($myWorkspace as $order)
                            @php $isOverdue = $order->is_overdue; @endphp
                            <tr class="hover:bg-gray-50 transition-colors group dark:hover:bg-white/[0.02]">
                                <td class="px-3 sm:px-6 py-3 sm:py-4">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-7 h-7 sm:w-8 sm:h-8 bg-indigo-600/10 rounded-lg flex items-center justify-center text-indigo-400 flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-slate-200 truncate max-w-[180px] dark:text-slate-200">
                                                {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Document' }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                @if($order->client)
                                                    <span
                                                        class="text-[9px] text-gray-500 dark:text-slate-500 truncate">{{ $order->client->name }}</span>
                                                @endif
                                                <span
                                                    class="text-[8px] font-bold px-1 py-0.5 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
                                            </div>
                                            @if($order->notes)
                                                <p class="text-[9px] text-amber-400/80 mt-1.5 leading-relaxed line-clamp-2 max-w-[220px]">
                                                    <i data-lucide="message-square" class="w-2.5 h-2.5 inline-block mr-0.5 -mt-0.5"></i>{{ $order->notes }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 sm:px-4 py-3 sm:py-4 text-center hidden sm:table-cell">
                                    @if($isOverdue)
                                        <span
                                            class="inline-flex items-center gap-1 text-[9px] font-bold text-red-400 bg-red-500/5 border border-red-500/10 px-2 py-1 rounded-full">
                                            <span class="w-1 h-1 bg-red-400 rounded-full"></span> Overdue
                                        </span>
                                    @elseif($order->status->value === 'processing')
                                        <span
                                            class="inline-flex items-center gap-1 text-[9px] font-bold text-blue-400 bg-blue-500/5 border border-blue-500/10 px-2 py-1 rounded-full">
                                            <span class="w-1 h-1 bg-blue-400 rounded-full animate-pulse"></span> Processing
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 text-[9px] font-bold text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] px-2 py-1 rounded-full">
                                            <span class="w-1 h-1 bg-slate-500 rounded-full"></span> Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5 sm:gap-2">
                                        @if($order->files->first())
                                            <a href="{{ route('orders.files.download', [$order, $order->files->first()]) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-semibold text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 border border-gray-200 dark:bg-white/[0.05] dark:border-white/[0.08] dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] rounded-lg transition-all">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                                Download
                                            </a>
                                        @endif
                                        @if($order->status->value == 'pending')
                                            <form action="{{ route('orders.status', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="processing">
                                                <button
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-all shadow-lg shadow-emerald-600/10">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Start
                                                </button>
                                            </form>
                                            <form action="{{ route('orders.unclaim', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <button
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-red-500 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all border border-red-500/20">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                    Release
                                                </button>
                                            </form>
                                        @else
                                            <button
                                                onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-lg shadow-indigo-600/10">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                                Upload
                                            </button>
                                            <form action="{{ route('orders.unclaim', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <button
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-red-500 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all border border-red-500/20">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                    Release
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-14 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="w-12 h-12 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] rounded-2xl flex items-center justify-center text-gray-400 dark:text-slate-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">No active jobs</p>
                                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Claim an order from the queue below
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{-- ===== AVAILABLE FILES ===== --}}
            <div id="files" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/[0.04]">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Available Queue</h2>
                        @if($availableFiles->count() > 0)
                            <span
                                class="bg-amber-500/10 text-amber-400 border border-amber-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $availableFiles->count() }}
                                waiting</span>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @forelse($availableFiles as $order)
                        @php $isUrgent = $order->due_at && $order->due_at->diffInMinutes(now(), false) > -5; @endphp
                        <div
                            class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-8 h-8 @if($isUrgent) bg-red-500/10 text-red-400 @else bg-gray-100 dark:bg-white/[0.05] text-gray-400 dark:text-slate-500 @endif rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 space-y-0.5">
                                    <p class="text-xs font-semibold text-slate-200 truncate dark:text-slate-200">
                                        {{ $order->files->first() ? basename($order->files->first()->file_path) : 'New Document' }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($order->client)
                                            <span
                                                class="text-[9px] text-gray-500 dark:text-slate-500 truncate">{{ $order->client->name }}</span>
                                        @endif
                                        <span
                                            class="text-[8px] font-bold px-1 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
                                        <span
                                            class="text-[8px] font-bold text-gray-500 dark:text-slate-500 bg-gray-100 dark:bg-white/[0.05] px-1 rounded">{{ $order->files_count }}
                                            {{ Str::plural('file', $order->files_count) }}</span>
                                        @if($isUrgent)
                                            <span
                                                class="text-[8px] font-bold text-red-400 bg-red-500/5 border border-red-500/10 px-1.5 rounded animate-pulse">Urgent</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <form action="{{ route('orders.claim', $order) }}" method="POST" class="flex-shrink-0">
                                @csrf
                                <button
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-[10px] font-bold text-black bg-amber-400 hover:bg-amber-300 rounded-xl transition-all shadow-md shadow-amber-400/10 group-hover:scale-105">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Claim
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-gray-400 dark:text-slate-500">Queue is empty</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">No new orders are available right now</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end left col --}}

        {{-- RIGHT COLUMN (1/3 width) --}}
        <div class="space-y-4 sm:space-y-5 min-w-0">

            {{-- Recent History --}}
            <div id="history" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/[0.04]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Deliveries</h2>
                    @if($recentHistory->count() > 0)
                        <span class="text-[9px] text-gray-500 dark:text-slate-500 font-semibold">{{ $recentHistory->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @forelse($recentHistory as $history)
                        <div
                            class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="w-6 h-6 bg-emerald-500/5 rounded-lg flex items-center justify-center text-emerald-600 flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-[11px] font-semibold text-gray-900 truncate group-hover:text-gray-900 transition-colors dark:text-slate-300">
                                        {{ $history->files->first() ? basename($history->files->first()->file_path) : 'Document' }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5 font-mono">
                                        {{ $history->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span
                                class="flex-shrink-0 text-[8px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded uppercase tracking-wider">Done</span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold">No deliveries yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end right col --}}

    </div>{{-- end grid --}}

    {{-- ===== UPLOAD MODALS ===== --}}
    @foreach($myWorkspace as $order)
        @if($order->status->value == 'processing')
            <div id="upload-modal-{{ $order->id }}"
                class="hidden fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4"
                onclick="if(event.target===this)this.classList.add('hidden')">

            <div class="bg-[#0f0f14] border border-white/[0.08] rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden"
                onclick="event.stopPropagation()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-7 pt-6 pb-5 border-b border-white/[0.06]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-500/[0.12] rounded-xl flex items-center justify-center border border-indigo-500/[0.2]">
                            <i data-lucide="upload-cloud" class="w-5 h-5 text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-bold text-white tracking-tight">Submit Results</h3>
                            <p class="text-[9px] text-slate-600 font-mono uppercase tracking-widest mt-0.5 truncate max-w-[240px]">
                                {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Order #' . $order->id }}
                            </p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.add('hidden')"
                        class="w-8 h-8 bg-white/[0.04] hover:bg-white/[0.08] text-slate-500 hover:text-white rounded-lg flex items-center justify-center transition-all border border-white/[0.06]">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Form --}}
                <form action="{{ route('orders.report', $order) }}" method="POST" enctype="multipart/form-data"
                    class="px-7 py-6 space-y-4">
                    @csrf

                    {{-- Info strip --}}
                    <div class="flex items-center gap-2.5 px-3.5 py-2.5 bg-indigo-500/[0.06] border border-indigo-500/[0.1] rounded-xl">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-indigo-400/70 flex-shrink-0"></i>
                        <p class="text-[10px] text-indigo-400/70 leading-relaxed">
                            Upload both the <span class="font-bold text-indigo-400">AI Detection</span> and
                            <span class="font-bold text-indigo-400">Plagiarism</span> report PDFs to complete this order.
                        </p>
                    </div>

                    {{-- AI Bypass Checkbox --}}
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-300 cursor-pointer w-fit">
                            <input type="checkbox" id="ai-skipped-{{ $order->id }}" name="ai_skipped" value="1" class="rounded bg-white/[0.04] border-white/[0.1] text-indigo-500 focus:ring-indigo-500/30" onchange="toggleAiBypass({{ $order->id }}, this.checked)">
                            AI Report could not be generated
                        </label>
                        <div id="ai-skip-reason-container-{{ $order->id }}" class="hidden">
                            <input type="text" name="ai_skip_reason" id="ai-skip-reason-input-{{ $order->id }}" placeholder="Brief reason (e.g. Not enough words)" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-xl px-3.5 py-2 text-[11px] text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500/50" oninput="checkUploadReady({{ $order->id }})">
                        </div>
                    </div>

                    {{-- TWO UPLOAD ZONES --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        {{-- AI Report --}}
                        <div id="ai-upload-container-{{ $order->id }}" class="space-y-2">
                            <label class="flex items-center gap-1.5 text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                AI Detection Report
                            </label>
                            <label id="ai-label-{{ $order->id }}"
                                class="group flex flex-col items-center justify-center gap-2 w-full bg-white/[0.03] hover:bg-red-500/[0.05] border-2 border-dashed border-white/[0.08] hover:border-red-500/30 rounded-2xl py-6 px-3 text-center cursor-pointer transition-all">
                                <input type="file" name="ai_report" accept=".pdf" required class="hidden"
                                    onchange="previewFile(this, 'ai-preview-{{ $order->id }}', 'ai-label-{{ $order->id }}', 'red', {{ $order->id }})">
                                <div id="ai-preview-{{ $order->id }}" class="flex flex-col items-center gap-1.5">
                                    <div class="w-9 h-9 bg-red-500/[0.08] rounded-xl flex items-center justify-center border border-red-500/[0.15] group-hover:scale-105 transition-all">
                                        <i data-lucide="file-scan" class="w-4 h-4 text-red-400/70"></i>
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider leading-tight">AI Report<br>PDF</span>
                                </div>
                            </label>
                        </div>

                        {{-- Plagiarism Report --}}
                        <div class="space-y-2">
                            <label class="flex items-center gap-1.5 text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                Plagiarism Report
                            </label>
                            <label id="plag-label-{{ $order->id }}"
                                class="group flex flex-col items-center justify-center gap-2 w-full bg-white/[0.03] hover:bg-amber-500/[0.05] border-2 border-dashed border-white/[0.08] hover:border-amber-500/30 rounded-2xl py-6 px-3 text-center cursor-pointer transition-all">
                                <input type="file" name="plag_report" accept=".pdf" required class="hidden"
                                    onchange="previewFile(this, 'plag-preview-{{ $order->id }}', 'plag-label-{{ $order->id }}', 'amber', {{ $order->id }})">
                                <div id="plag-preview-{{ $order->id }}" class="flex flex-col items-center gap-1.5">
                                    <div class="w-9 h-9 bg-amber-500/[0.08] rounded-xl flex items-center justify-center border border-amber-500/[0.15] group-hover:scale-105 transition-all">
                                        <i data-lucide="file-search" class="w-4 h-4 text-amber-400/70"></i>
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider leading-tight">Plag Report<br>PDF</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Ready strip (hidden until both files selected) --}}
                    <div id="progress-{{ $order->id }}" class="hidden items-center gap-2.5 px-3.5 py-2.5 bg-emerald-500/[0.06] border border-emerald-500/[0.12] rounded-xl">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0"></i>
                        <p class="text-[10px] text-emerald-400 font-semibold">Both reports selected — ready to submit.</p>
                    </div>

                    {{-- Upload progress bar (shown during XHR upload) --}}
                    <div id="upload-progress-{{ $order->id }}" class="hidden flex-col gap-1.5 px-3.5 py-2.5 bg-indigo-500/[0.06] border border-indigo-500/[0.12] rounded-xl">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] text-indigo-400 font-semibold">Uploading reports…</p>
                            <span id="upload-progress-text-{{ $order->id }}" class="text-[10px] text-indigo-400 font-bold tabular-nums">0%</span>
                        </div>
                        <div class="h-1.5 bg-white/[0.06] rounded-full overflow-hidden">
                            <div id="upload-progress-fill-{{ $order->id }}" class="h-full bg-indigo-500 rounded-full transition-[width] duration-150" style="width:0%"></div>
                        </div>
                    </div>

                    {{-- Error strip (shown on server-side failure) --}}
                    <div id="error-strip-{{ $order->id }}" class="hidden items-center gap-2.5 px-3.5 py-2.5 bg-red-500/[0.06] border border-red-500/[0.15] rounded-xl">
                        <svg class="w-3.5 h-3.5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p id="error-msg-{{ $order->id }}" class="text-[10px] text-red-400 font-semibold"></p>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-3 pt-1">
                        <button type="button" id="cancel-btn-{{ $order->id }}"
                            onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.add('hidden')"
                            class="px-5 py-2.5 text-[11px] font-semibold text-slate-500 hover:text-white bg-white/[0.04] hover:bg-white/[0.08] rounded-xl transition-all border border-white/[0.06]">
                            Cancel
                        </button>
                        <button type="button" id="submit-btn-{{ $order->id }}"
                            onclick="submitUploadForm({{ $order->id }})"
                            disabled
                            class="flex-1 py-2.5 text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            Submit Both Reports
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

    <script>
        const MAX_REPORT_SIZE = 100 * 1024 * 1024;

        function refreshAvailableQueue() {
            if (document.hidden) return;

            fetch(window.location.pathname + '?queue_refresh=' + Date.now(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const incomingQueue = doc.getElementById('files');
                    const currentQueue = document.getElementById('files');

                    if (!incomingQueue || !currentQueue) return;
                    currentQueue.outerHTML = incomingQueue.outerHTML;
                    if (window.lucide && lucide.createIcons) lucide.createIcons();
                })
                .catch(() => {
                    // Ignore transient fetch errors; next polling tick will retry.
                });
        }

        setInterval(refreshAvailableQueue, 30000);

        function setUploadError(orderId, message) {
            const errStrip = document.getElementById('error-strip-' + orderId);
            const errMsg   = document.getElementById('error-msg-' + orderId);
            if (!errStrip || !errMsg) return;
            errMsg.textContent = message;
            errStrip.classList.remove('hidden');
            errStrip.classList.add('flex');
        }

        function clearUploadError(orderId) {
            const errStrip = document.getElementById('error-strip-' + orderId);
            if (!errStrip) return;
            errStrip.classList.add('hidden');
            errStrip.classList.remove('flex');
        }

        function resetUploadUi(orderId) {
            const submitBtn   = document.getElementById('submit-btn-' + orderId);
            const cancelBtn   = document.getElementById('cancel-btn-' + orderId);
            const readyStrip  = document.getElementById('progress-' + orderId);
            const progressBar = document.getElementById('upload-progress-' + orderId);

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> Submit Both Reports';
            }
            if (cancelBtn) cancelBtn.disabled = false;
            if (progressBar) {
                progressBar.classList.add('hidden');
                progressBar.classList.remove('flex');
            }
            if (readyStrip) {
                readyStrip.classList.remove('hidden');
                readyStrip.classList.add('flex');
            }
            if (window.lucide && lucide.createIcons) lucide.createIcons();
        }

        function previewFile(input, previewId, labelId, color, orderId) {
            const file = input.files[0];
            if (!file) return;

            clearUploadError(orderId);

            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            if (!isPdf) {
                input.value = '';
                setUploadError(orderId, 'Only PDF files can be uploaded for vendor reports.');
                return;
            }

            if (file.size > MAX_REPORT_SIZE) {
                input.value = '';
                setUploadError(orderId, 'Each report must be 100MB or smaller.');
                return;
            }

            const preview = document.getElementById(previewId);
            const label   = document.getElementById(labelId);
            const name    = file.name.length > 24 ? file.name.slice(0, 21) + '...' : file.name;

            const colorMap = {
                red: {
                    text:         'text-red-400',
                    iconBg:       'bg-red-500/[0.12]',
                    iconBorder:   'border-red-500/[0.25]',
                    labelBorder:  'border-red-500/30',
                    labelBg:      'bg-red-500/[0.05]',
                },
                amber: {
                    text:         'text-amber-400',
                    iconBg:       'bg-amber-500/[0.12]',
                    iconBorder:   'border-amber-500/[0.25]',
                    labelBorder:  'border-amber-500/30',
                    labelBg:      'bg-amber-500/[0.05]',
                },
            };
            const c = colorMap[color];

            // Replace zone content with checkmark + filename
            preview.innerHTML = `
                <div class="w-9 h-9 ${c.iconBg} rounded-xl flex items-center justify-center border ${c.iconBorder}">
                    <svg class="w-4 h-4 ${c.text}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-[9px] font-bold ${c.text} tracking-wide text-center leading-tight max-w-full px-1 break-all">${name}</span>
            `;

            // Swap zone to "selected" state — solid border + tinted bg
            label.classList.remove('border-dashed', 'border-white/[0.08]', 'bg-white/[0.03]');
            label.classList.add(c.labelBorder, c.labelBg);

            checkUploadReady(orderId);
        }

        function toggleAiBypass(orderId, isSkipped) {
            const uploadContainer = document.getElementById('ai-upload-container-' + orderId);
            const reasonContainer = document.getElementById('ai-skip-reason-container-' + orderId);
            const aiInput = document.querySelector('#ai-label-' + orderId + ' input[type="file"]');
            
            if (isSkipped) {
                uploadContainer.classList.add('hidden');
                reasonContainer.classList.remove('hidden');
                if (aiInput) aiInput.value = '';
                
                const preview = document.getElementById('ai-preview-' + orderId);
                const label = document.getElementById('ai-label-' + orderId);
                if (preview) {
                    preview.innerHTML = `
                        <div class="w-9 h-9 bg-red-500/[0.08] rounded-xl flex items-center justify-center border border-red-500/[0.15] group-hover:scale-105 transition-all">
                            <i data-lucide="file-scan" class="w-4 h-4 text-red-400/70"></i>
                        </div>
                        <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider leading-tight">AI Report<br>PDF</span>
                    `;
                }
                if (label) {
                    label.classList.add('border-dashed', 'border-white/[0.08]', 'bg-white/[0.03]');
                    label.classList.remove('border-red-500/30', 'bg-red-500/[0.05]');
                }
                if (window.lucide && lucide.createIcons) lucide.createIcons();
            } else {
                uploadContainer.classList.remove('hidden');
                reasonContainer.classList.add('hidden');
                document.getElementById('ai-skip-reason-input-' + orderId).value = '';
            }
            checkUploadReady(orderId);
        }

        function checkUploadReady(orderId) {
            const aiInput      = document.querySelector('#ai-label-' + orderId + ' input[type="file"]');
            const plagInput    = document.querySelector('#plag-label-' + orderId + ' input[type="file"]');
            const skipCheckbox = document.getElementById('ai-skipped-' + orderId);
            const reasonInput  = document.getElementById('ai-skip-reason-input-' + orderId);
            const bar          = document.getElementById('progress-' + orderId);
            const btn          = document.getElementById('submit-btn-' + orderId);
            
            let aiReady   = false;
            let plagReady = false;

            if (skipCheckbox && skipCheckbox.checked) {
                if (reasonInput && reasonInput.value.trim().length > 0) aiReady = true;
            } else {
                if (aiInput && aiInput.files && aiInput.files.length > 0) aiReady = true;
            }

            if (plagInput && plagInput.files && plagInput.files.length > 0) plagReady = true;

            if (aiReady && plagReady) {
                if (bar) { bar.classList.remove('hidden'); bar.classList.add('flex'); }
                if (btn) btn.disabled = false;
            } else {
                if (bar) { bar.classList.add('hidden'); bar.classList.remove('flex'); }
                if (btn) btn.disabled = true;
            }
        }

        function submitUploadForm(orderId) {
            const modal       = document.getElementById('upload-modal-' + orderId);
            const form        = modal.querySelector('form');
            const submitBtn   = document.getElementById('submit-btn-' + orderId);
            const cancelBtn   = document.getElementById('cancel-btn-' + orderId);
            const readyStrip  = document.getElementById('progress-' + orderId);
            const progressBar = document.getElementById('upload-progress-' + orderId);
            const fill        = document.getElementById('upload-progress-fill-' + orderId);
            const pctText     = document.getElementById('upload-progress-text-' + orderId);

            clearUploadError(orderId);

            // Lock the UI
            submitBtn.disabled = true;
            cancelBtn.disabled = true;
            submitBtn.innerHTML = 'Uploading…';
            readyStrip.classList.add('hidden');
            progressBar.classList.remove('hidden');
            progressBar.classList.add('flex');

            // Refresh CSRF token first to handle long-lived sessions
            fetch('/csrf-refresh')
                .then(r => r.json())
                .then(data => {
                    const tokenField = form.querySelector('input[name="_token"]');
                    if (tokenField) tokenField.value = data.token;
                })
                .catch(() => { /* proceed with existing token on fetch failure */ })
                .finally(() => {
                    const xhr = new XMLHttpRequest();

                    xhr.upload.onprogress = function (e) {
                        if (!e.lengthComputable) return;
                        const pct = Math.round((e.loaded / e.total) * 100);
                        fill.style.width    = pct + '%';
                        pctText.textContent = pct + '%';
                    };

                    xhr.onload = function () {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            // Try to parse as JSON (our AJAX handler response)
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.error) {
                                    setUploadError(orderId, data.error);
                                    resetUploadUi(orderId);
                                    return;
                                }
                                // Success — stash the message for display after redirect
                                try { if (data.success) sessionStorage.setItem('upload_success', data.success); } catch (_) {}
                                window.location.href = data.redirect || '/dashboard';
                                return;
                            } catch (e) {
                                // Not JSON — XHR followed a normal redirect, navigate to final URL
                            }
                            window.location.href = '/dashboard';
                        } else {
                            // HTTP 4xx / 5xx — re-enable the form and show an inline error
                            resetUploadUi(orderId);

                            let msg = 'Upload failed. Please try again.';
                            if (xhr.status === 419) {
                                msg = 'Session expired — please refresh the page and try again.';
                            } else if (xhr.status === 422) {
                                try {
                                    const d = JSON.parse(xhr.responseText);
                                    msg = d.error || d.message || (d.errors && Object.values(d.errors)[0]?.[0]) || msg;
                                } catch (e) {}
                            } else if (xhr.status === 403) {
                                msg = 'You are not authorized to upload for this order.';
                            } else if (xhr.status === 413) {
                                msg = 'One of the files is too large for the server to accept. Please upload smaller PDFs.';
                            } else if (xhr.status >= 500) {
                                msg = 'Server error while saving reports. Please try again in a moment.';
                            }
                            setUploadError(orderId, msg);
                        }
                    };

                    xhr.onerror = function () {
                        resetUploadUi(orderId);
                        setUploadError(orderId, 'Network or storage connection error. Please try again.');
                    };

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send(new FormData(form));
                });
        }
    </script>

</x-vendor-layout>
