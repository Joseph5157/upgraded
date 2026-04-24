<div id="vendor-dashboard-live" class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-indigo-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-indigo-400 bg-indigo-400/5 border border-indigo-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Pool</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white" data-stat="available_pool">{{ $stats['available_pool'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Available Orders</p>
        </div>

        <div class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-blue-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-blue-400 bg-blue-400/5 border border-blue-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Active</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white" data-stat="active_jobs">{{ $stats['active_jobs'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">In Progress</p>
        </div>

        <div class="group bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-3 sm:p-5 hover:border-emerald-500/30 transition-all duration-200 dark:bg-[#13151c] dark:border-white/[0.06]">
            <div class="flex items-start justify-between mb-3 sm:mb-4">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded-lg uppercase tracking-wider">Today</span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-[#1A1D23] tabular-nums dark:text-white" data-stat="total_checked_today">{{ $stats['total_checked_today'] }}</p>
            <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-1 dark:text-slate-500 hidden sm:block">Delivered Today</p>
        </div>

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <div class="lg:col-span-2 space-y-4 sm:space-y-5 min-w-0">
            @include('dashboard.partials.workspace', ['myWorkspace' => $myWorkspace])
            @include('dashboard.partials.available-queue')
        </div>

        <div class="space-y-4 sm:space-y-5 min-w-0">
            <div id="history" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/[0.04]">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Deliveries</h2>
                    @if($recentHistory->count() > 0)
                        <span class="text-[9px] text-gray-500 dark:text-slate-500 font-semibold">{{ $recentHistory->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @forelse($recentHistory as $history)
                        <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-6 h-6 bg-emerald-500/5 rounded-lg flex items-center justify-center text-emerald-600 flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-gray-900 truncate group-hover:text-gray-900 transition-colors dark:text-slate-300">
                                        {{ $history->files->first() ? ($history->files->first()->original_name ?? basename($history->files->first()->file_path)) : 'Document' }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5 font-mono">
                                        {{ $history->delivered_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span class="flex-shrink-0 text-[8px] font-bold text-emerald-400 bg-emerald-400/5 border border-emerald-400/10 px-1.5 py-0.5 rounded uppercase tracking-wider">Done</span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold">No deliveries yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
