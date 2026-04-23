<div id="files" class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#13151c] dark:border-white/[0.06]">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/[0.04]">
        <div class="flex items-center gap-2.5">
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Available Orders</h2>
            @if($availableFiles->count() > 0)
                <span class="bg-amber-500/10 text-amber-400 border border-amber-500/15 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $availableFiles->count() }} waiting</span>
            @endif
        </div>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
        @forelse($availableFiles as $order)
            <div class="order-card flex items-center justify-between gap-4 px-6 py-4 hover:bg-[#F0F2F5] transition-colors group dark:hover:bg-white/[0.02]" data-order-id="{{ $order->id }}">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 bg-gray-100 dark:bg-white/[0.05] text-gray-400 dark:text-slate-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-xs font-semibold text-gray-900 truncate dark:text-slate-200">
                            {{ $order->files->first() ? basename($order->files->first()->file_path) : 'New Document' }}
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if($order->client)
                                <span class="text-[9px] text-gray-500 dark:text-slate-500 truncate">{{ $order->client->name }}</span>
                            @endif
                            <span class="text-[8px] font-bold px-1 rounded @if($order->source === 'account') bg-blue-500/10 text-blue-400 @else bg-purple-500/10 text-purple-400 @endif">{{ strtoupper($order->source) }}</span>
                            <span class="text-[8px] font-bold text-gray-500 dark:text-slate-500 bg-gray-100 dark:bg-white/[0.05] px-1 rounded">{{ $order->files->count() }} {{ Str::plural('file', $order->files->count()) }}</span>
                        </div>
                    </div>
                </div>
                <button
                    onclick="ajaxAction('{{ route('orders.claim', $order) }}', this, 'claim', {{ $order->id }})"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-black bg-amber-400 hover:bg-amber-300 rounded-xl transition-all shadow-md shadow-amber-400/10 group-hover:scale-105"
                    data-order-id="{{ $order->id }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Claim order
                </button>
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-semibold text-gray-400 dark:text-slate-500">No available orders</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">New work will appear here when it is ready to claim</p>
            </div>
        @endforelse
    </div>
</div>
