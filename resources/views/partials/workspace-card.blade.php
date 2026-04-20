<div class="order-card min-w-[240px] max-w-[240px] snap-start rounded-2xl border border-white/[0.06] bg-black/10 dark:bg-white/[0.02] p-3" data-order-id="{{ $order->id }}">
    <div class="flex items-start justify-between gap-2">
        <div
            class="w-9 h-9 bg-indigo-600/10 rounded-xl flex items-center justify-center text-indigo-400 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        @if($order->status->value === 'processing')
            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-blue-400 bg-blue-500/5 border border-blue-500/10 px-2 py-1 rounded-full flex-shrink-0">
                <span class="w-1 h-1 bg-blue-400 rounded-full animate-pulse"></span> Processing
            </span>
        @elseif($order->status->value === 'claimed')
            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-400 bg-amber-500/5 border border-amber-500/10 px-2 py-1 rounded-full flex-shrink-0">
                <span class="w-1 h-1 bg-amber-400 rounded-full"></span> Reserved
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

    {{-- Per-file downloads (multi-file orders) --}}
    @if($order->files->count() > 1)
        <div class="mt-3 space-y-1">
            @foreach($order->files as $file)
                <a href="{{ route('orders.files.download', [$order, $file]) }}"
                    class="w-full inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[9px] font-semibold text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 border border-gray-200 dark:bg-white/[0.05] dark:border-white/[0.08] dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] rounded-lg transition-all">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="truncate">{{ basename($file->file_path) }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 gap-2 mt-3">
        @if($order->files->first() && $order->files->count() === 1)
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
        <button
            onclick="ajaxAction('{{ route('orders.unclaim', $order) }}', this, 'unclaim', {{ $order->id }})"
            class="w-full inline-flex items-center justify-center gap-1 px-4 py-2.5 text-xs font-bold text-red-500 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all border border-red-500/20"
            data-order-id="{{ $order->id }}">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            Release
        </button>
        <div></div>
        @if($order->status->value === 'processing')
            <button
                onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.remove('hidden')"
                class="col-span-2 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-lg shadow-indigo-600/10">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Reports
            </button>
        @endif
    </div>
</div>
