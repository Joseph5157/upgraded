<tr class="hover:bg-gray-50 transition-colors group dark:hover:bg-white/[0.02]" data-order-id="{{ $order->id }}">
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
        @if($order->status->value === 'processing')
            <span
                class="inline-flex items-center gap-1 text-[9px] font-bold text-blue-400 bg-blue-500/5 border border-blue-500/10 px-2 py-1 rounded-full">
                <span class="w-1 h-1 bg-blue-400 rounded-full animate-pulse"></span> Processing
            </span>
        @elseif($order->status->value === 'claimed')
            <span
                class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-400 bg-amber-500/5 border border-amber-500/10 px-2 py-1 rounded-full">
                <span class="w-1 h-1 bg-amber-400 rounded-full"></span> Reserved
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
            @foreach($order->files as $file)
                <a href="{{ route('orders.files.download', [$order, $file]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-semibold text-gray-500 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 border border-gray-200 dark:bg-white/[0.05] dark:border-white/[0.08] dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/[0.08] rounded-lg transition-all"
                    title="{{ basename($file->file_path) }}">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ $order->files->count() > 1 ? 'File '.$loop->iteration : 'Download' }}
                </a>
            @endforeach
            @if($order->status->value === 'claimed')
                <button
                    onclick="ajaxAction('{{ route('orders.status', $order) }}', this, 'status', {{ $order->id }}, 'processing')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-amber-600 bg-amber-500/10 hover:bg-amber-500/20 rounded-lg transition-all border border-amber-500/20"
                    data-order-id="{{ $order->id }}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Mark Processing
                </button>
            @endif
            @if($order->status->value === 'processing')
                <button
                    onclick="document.getElementById('upload-modal-{{ $order->id }}').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-lg shadow-indigo-600/10">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Upload
                </button>
            @endif
            <button
                onclick="ajaxAction('{{ route('orders.unclaim', $order) }}', this, 'unclaim', {{ $order->id }})"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-red-500 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all border border-red-500/20"
                data-order-id="{{ $order->id }}">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                Release
            </button>
        </div>
    </td>
</tr>
