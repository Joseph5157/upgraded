<div id="workspace" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-white/[0.04]">
        <div class="flex items-center gap-2.5">
            <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">My Workspace</h2>
            @if($myWorkspace->count() > 0)
                <span class="workspace-count-badge bg-indigo-500/10 text-indigo-400 border border-indigo-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $myWorkspace->count() }}</span>
            @endif
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden">
        @if($myWorkspace->isNotEmpty())
            <div class="flex flex-col gap-3 px-4 pt-4 pb-2">
                @foreach($myWorkspace as $order)
                    @include('partials.workspace-card', ['order' => $order])
                @endforeach
            </div>
        @else
            <div class="px-4 pt-4 pb-3 border-t border-gray-100 dark:border-white/[0.04]">
                <div class="rounded-2xl border border-dashed border-white/[0.08] bg-black/10 dark:bg-white/[0.02] px-4 py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] rounded-2xl flex items-center justify-center text-gray-400 dark:text-slate-500 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-500 dark:text-slate-300">No active jobs</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Claim an order from the queue below</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full min-w-0">
            <thead>
                <tr class="text-[9px] text-gray-400 font-semibold uppercase tracking-widest border-b border-gray-100 dark:text-slate-600 dark:border-white/[0.04]">
                    <th class="text-left px-3 sm:px-6 py-3 font-semibold">File</th>
                    <th class="text-center px-2 sm:px-4 py-3 font-semibold hidden sm:table-cell">Status</th>
                    <th class="text-right px-3 sm:px-6 py-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                @forelse($myWorkspace as $order)
                    @include('partials.workspace-row', ['order' => $order])
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-white/[0.05] border border-gray-200 dark:border-white/[0.08] rounded-2xl flex items-center justify-center text-gray-400 dark:text-slate-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">No active jobs</p>
                                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">Claim an order from the queue below</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
