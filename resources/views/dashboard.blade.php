<x-vendor-layout title="Dashboard">

    <div class="space-y-3">
        <x-announcements-banner />
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Available Pool --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-5 hover:border-indigo-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span
                    class="text-[9px] font-bold text-indigo-400 bg-indigo-400/5 border border-indigo-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Pool</span>
            </div>
            <p class="text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['available_pool'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500">Available Orders</p>
        </div>

        {{-- Active Jobs --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-5 hover:border-blue-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 bg-blue-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span
                    class="text-[9px] font-bold text-blue-400 bg-blue-400/5 border border-blue-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Active</span>
            </div>
            <p class="text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['active_jobs'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500">In Progress</p>
        </div>

        {{-- Total Checked Today --}}
        <div
            class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-5 hover:border-emerald-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-4">
                <div class="w-9 h-9 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span
                    class="text-[9px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Today</span>
            </div>
            <p class="text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white">{{ $stats['total_checked_today'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500">Delivered</p>
        </div>

        {{-- Overdue --}}
        <div
            class="group bg-[#FAFBFC] border @if($stats['overdue_count'] > 0) border-red-500/20 @else border-[#E2E6EA] @endif rounded-2xl p-5 hover:border-red-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-4">
                <div
                    class="w-9 h-9 @if($stats['overdue_count'] > 0) bg-red-500/10 @else bg-[#F0F2F5] @endif rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 @if($stats['overdue_count'] > 0) text-red-400 @else text-[#9CA3AF] @endif"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                @if($stats['overdue_count'] > 0)
                    <span
                        class="text-[9px] font-bold text-red-400 bg-red-400/5 border border-red-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider animate-pulse">Alert</span>
                @endif
            </div>
            <p
                class="text-3xl font-bold @if($stats['overdue_count'] > 0) text-red-400 @else text-[#1A1D23] dark:text-white @endif tabular-nums">
                {{ $stats['overdue_count'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500">Overdue Tasks</p>
        </div>
    </div>

    {{-- ===== PRIMARY CONTENT GRID ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- LEFT COLUMN (2/3 width) --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- ===== MY WORKSPACE ===== --}}
            <div id="workspace" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-[#E2E6EA]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                        <h2 class="text-sm font-semibold text-[#1A1D23]">My Workspace</h2>
                        @if($myWorkspace->count() > 0)
                            <span
                                class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $myWorkspace->count() }}</span>
                        @endif
                    </div>
                    @if($myWorkspace->count() > 0)
                        @php
                            $minutes = $myWorkspace->map(function($order) {
                                return $order->due_at ? max(0, now()->diffInMinutes($order->due_at, false)) : 0;
                            })->sort()->values()->pipe(function($sorted) {
                                $count = $sorted->count();
                                if ($count === 0) return 0;
                                $middle = floor($count / 2);
                                return $count % 2 ? $sorted[$middle] : ($sorted[$middle - 1] + $sorted[$middle]) / 2;
                            });
                        @endphp
                        <div class="flex items-center gap-1.5 text-[10px] font-semibold @if($minutes < 5) text-red-400 @else text-[#6B7280] @endif">
                            <svg class="w-3 h-3 @if($minutes < 5) text-red-500 @else text-indigo-500 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ~{{ round($minutes) }} min ETA (Median)
                        </div>
                    @endif
                </div>

                {{-- Table --}}
                <table class="w-full">
                    <thead>
                        <tr
                            class="text-[9px] text-[#9CA3AF] font-semibold uppercase tracking-widest border-b border-[#E2E6EA] dark:text-slate-600 dark:border-white/[0.04]">
                            <th class="text-left px-6 py-3 font-semibold">File</th>
                            <th class="text-center px-4 py-3 font-semibold">Timer</th>
                            <th class="text-center px-4 py-3 font-semibold">Status</th>
                            <th class="text-right px-6 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E6EA] dark:divide-white/[0.04]">
                        @forelse($myWorkspace as $order)
                            @php $isOverdue = $order->is_overdue; @endphp
                            <tr class="hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 bg-indigo-600/10 rounded-lg flex items-center justify-center text-indigo-400 flex-shrink-0">
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
                                                        class="text-[9px] text-[#6B7280] truncate dark:text-slate-500">{{ $order->client->name }}</span>
                                                @endif
                                                <span
                                                    class="text-[8px] font-bold px-1 py-0.5 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($isOverdue)
                                        <span
                                            class="text-[9px] font-bold text-red-400 bg-red-500/5 border border-red-500/10 px-2 py-1 rounded-lg animate-pulse">Overdue</span>
                                    @else
                                        <span
                                            class="workspace-timer text-xs font-mono font-bold text-indigo-400 tabular-nums bg-indigo-500/5 border border-indigo-500/10 px-2 py-1 rounded-lg"
                                            data-due="{{ $order->due_at?->toIso8601String() }}">--:--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
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
                                            class="inline-flex items-center gap-1 text-[9px] font-bold text-[#6B7280] bg-[#F0F2F5] border border-[#E2E6EA] px-2 py-1 rounded-full">
                                            <span class="w-1 h-1 bg-slate-500 rounded-full"></span> Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($order->files->first())
                                            <a href="{{ route('orders.files.download', [$order, $order->files->first()]) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-semibold text-[#6B7280] hover:text-[#1A1D23] bg-[#F0F2F5] hover:bg-[#ECEEF2] border border-[#E2E6EA] rounded-lg transition-all">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
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
                                            class="w-12 h-12 bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl flex items-center justify-center text-[#9CA3AF]">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-[#6B7280]">No active jobs</p>
                                            <p class="text-[10px] text-[#9CA3AF] mt-0.5">Claim an order from the queue below
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ===== AVAILABLE FILES ===== --}}
            <div id="files" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[#E2E6EA]">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-[#1A1D23]">Available Queue</h2>
                        @if($availableFiles->count() > 0)
                            <span
                                class="bg-amber-500/10 text-amber-400 border border-amber-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $availableFiles->count() }}
                                waiting</span>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-[#E2E6EA]">
                    @forelse($availableFiles as $order)
                        @php $isUrgent = $order->due_at && $order->due_at->diffInMinutes(now(), false) > -5; @endphp
                        <div
                            class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-8 h-8 @if($isUrgent) bg-red-500/10 text-red-400 @else bg-[#F0F2F5] text-[#6B7280] @endif rounded-xl flex items-center justify-center flex-shrink-0">
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
                                        @if($order->client)<span
                                            class="text-[9px] text-[#6B7280]">{{ $order->client->name }}</span><span
                                        class="text-[#9CA3AF] text-[9px]">·</span>@endif
                                        <span
                                            class="text-[9px] text-[#6B7280] font-mono">{{ $order->created_at->diffForHumans() }}</span>
                                        <span
                                            class="text-[8px] font-bold px-1 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
                                        <span
                                            class="text-[8px] font-bold text-[#6B7280] bg-[#F0F2F5] px-1 rounded">{{ $order->files_count }}
                                            {{ Str::plural('file', $order->files_count) }}</span>
                                        @if($isUrgent)<span
                                        class="text-[8px] font-bold text-red-400 bg-red-500/5 border border-red-500/10 px-1.5 rounded animate-pulse">Urgent</span>@endif
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
                            <p class="text-sm font-semibold text-[#9CA3AF]">Queue is empty</p>
                            <p class="text-[10px] text-[#9CA3AF] mt-0.5">No new orders are available right now</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end left col --}}

        {{-- RIGHT COLUMN (1/3 width) --}}
        <div class="space-y-5">

            {{-- Recent History --}}
            <div id="history" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#E2E6EA]">
                    <h2 class="text-sm font-semibold text-[#1A1D23]">Recent Deliveries</h2>
                    @if($recentHistory->count() > 0)
                        <span class="text-[9px] text-[#6B7280] font-semibold">{{ $recentHistory->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-[#E2E6EA]">
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
                                        class="text-[11px] font-semibold text-[#1A1D23] truncate group-hover:text-[#1A1D23] transition-colors dark:text-slate-300">
                                        {{ $history->files->first() ? basename($history->files->first()->file_path) : 'Document' }}
                                    </p>
                                    <p class="text-[9px] text-[#9CA3AF] mt-0.5 font-mono">
                                        {{ $history->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span
                                class="flex-shrink-0 text-[8px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded uppercase tracking-wider">Done</span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-[10px] text-[#9CA3AF] font-semibold">No deliveries yet</p>
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
                class="hidden fixed inset-0 bg-black/70 backdrop-blur-md z-50 flex items-center justify-center p-4"
                onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-3xl w-full max-w-md shadow-2xl overflow-hidden dark:bg-[#1a1c24] dark:border-white/10"
                    onclick="event.stopPropagation()">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-7 pt-7 pb-5 border-b border-[#E2E6EA] dark:border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-indigo-600/10 rounded-xl flex items-center justify-center dark:bg-indigo-600/20">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-[#1A1D23] dark:text-white">Submit Results</h3>
                                <p class="text-[9px] text-[#6B7280] uppercase tracking-widest mt-0.5 truncate max-w-[200px] dark:text-slate-500">
                                    {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Order #' . $order->id }}
                                </p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.add('hidden')"
                            class="w-7 h-7 bg-[#F0F2F5] hover:bg-[#ECEEF2] text-[#6B7280] hover:text-[#1A1D23] rounded-lg flex items-center justify-center transition-all dark:bg-white/5 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    {{-- Form --}}
                    <form action="{{ route('orders.report', $order) }}" method="POST" enctype="multipart/form-data"
                        class="px-7 py-6 space-y-5">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-bold text-[#6B7280] uppercase tracking-widest dark:text-slate-500">AI Score
                                    (%)</label>
                                <input type="number" name="ai_percentage" step="0.01" min="0" max="100" placeholder="0 – 100"
                                    class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl py-2.5 px-3 text-sm text-[#1A1D23] placeholder-slate-700 focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-all dark:bg-white/5 dark:border-white/[0.08] dark:text-white">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-bold text-[#6B7280] uppercase tracking-widest dark:text-slate-500">Plag Score
                                    (%)</label>
                                <input type="number" name="plag_percentage" step="0.01" min="0" max="100" placeholder="0 – 100"
                                    class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl py-2.5 px-3 text-sm text-[#1A1D23] placeholder-slate-700 focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-all dark:bg-white/5 dark:border-white/[0.08] dark:text-white">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold text-[#6B7280] uppercase tracking-widest dark:text-slate-500">Report PDF</label>
                            <label
                                class="block w-full bg-[#F0F2F5] hover:bg-[#ECEEF2] border border-dashed border-[#E2E6EA] hover:border-indigo-500/30 rounded-xl py-6 text-center cursor-pointer transition-all group dark:bg-white/5 dark:border-white/[0.08] dark:hover:bg-white/[0.08]">
                                <svg class="w-6 h-6 text-[#9CA3AF] group-hover:text-indigo-400 mx-auto mb-2 transition-colors dark:text-slate-500"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p
                                    class="text-[10px] font-semibold text-[#9CA3AF] group-hover:text-[#6B7280] transition-colors dark:text-slate-500 dark:group-hover:text-slate-300">
                                    Click to upload PDF</p>
                                <input type="file" name="report" accept=".pdf" required class="hidden">
                            </label>
                        </div>
                        <div class="flex gap-3">
                            <button type="button"
                                onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.add('hidden')"
                                class="px-5 py-2.5 text-xs font-semibold text-[#6B7280] hover:text-[#1A1D23] bg-[#F0F2F5] hover:bg-[#ECEEF2] rounded-xl transition-all dark:bg-white/5 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10">Cancel</button>
                            <button type="submit"
                                class="flex-1 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Submit Results
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Workspace timers --}}
    <script>
        function updateWorkspaceTimers() {
            document.querySelectorAll('.workspace-timer').forEach(el => {
                if (!el.dataset.due) return;
                const diff = new Date(el.dataset.due).getTime() - Date.now();
                if (diff <= 0) { el.textContent = '00:00'; return; }
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            });
        }
        setInterval(updateWorkspaceTimers, 1000);
        updateWorkspaceTimers();
    </script>

</x-vendor-layout>