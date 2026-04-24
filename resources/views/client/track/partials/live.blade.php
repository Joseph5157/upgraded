<div id="guest-link-track-live" data-pulse-url="{{ $pulseUrl }}" data-pulse-signature="{{ $signature }}">
    @php
        $downloadRoute = route('client.link.download', [$link->token, $order->token_view]);

        $statusClass = [
            'pending' => 'bg-yellow-500/10 text-yellow-400',
            'claimed' => 'bg-amber-500/10 text-amber-400',
            'processing' => 'bg-blue-500/10 text-blue-400',
            'delivered' => 'bg-green-500/10 text-green-400',
        ][$order->computed_status] ?? 'bg-slate-500/10 text-slate-400';

        $statusLabel = [
            'pending' => 'Queued',
            'claimed' => 'Reserved',
            'processing' => 'In progress',
            'delivered' => 'Delivered',
        ][$order->computed_status] ?? ucfirst($order->computed_status);

        $firstFile = $order->files->first();
        $firstFileName = $firstFile ? ($firstFile->original_name ?? basename($firstFile->file_path)) : null;
    @endphp

    <div class="max-w-2xl mx-auto space-y-8 mt-12">
        <div class="glass p-8 rounded-3xl shadow-2xl flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-2xl font-bold">Order #{{ $order->id }}</h1>
                @if($firstFileName)
                    <p class="text-slate-500 text-sm mt-1 truncate">{{ $firstFileName }}</p>
                @endif
                <p class="text-slate-400">
                    Status:
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </p>
            </div>
        </div>

        @if($order->computed_status === 'delivered')
            <div class="glass p-8 rounded-3xl border-2 border-green-500/30 text-center space-y-4">
                <h2 class="text-xl font-bold text-green-400">Your results are ready</h2>
                <p class="text-slate-400">Download the report bundle now. This download can be used only once.</p>

                @if(! $order->is_downloaded)
                    <a href="{{ $downloadRoute }}"
                        class="inline-block py-4 px-12 bg-green-600 hover:bg-green-500 text-white font-bold rounded-2xl shadow-lg shadow-green-500/25 transition-all transform hover:scale-[1.05]">
                        Download Report Bundle
                    </a>
                @else
                    <button disabled
                        class="inline-block py-4 px-12 bg-slate-700 text-slate-500 font-bold rounded-2xl cursor-not-allowed">
                        Report already downloaded
                    </button>
                @endif
            </div>
        @else
            <div class="glass p-8 rounded-3xl text-center space-y-4">
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                </div>
                <h2 class="text-xl font-bold">
                    {{ $order->computed_status === 'claimed' ? 'Reserved' : 'In progress' }}
                </h2>
                <p class="text-slate-400">
                    {{ $order->computed_status === 'claimed'
                        ? 'A vendor has reserved your order and will start work shortly.'
                        : 'Your order is being worked on. This page refreshes automatically.' }}
                </p>
                <div class="flex items-center justify-center gap-2 mt-1">
                    <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                    <span id="refresh-badge" class="text-[11px] font-semibold text-blue-400 tracking-wide">Checking for updates...</span>
                </div>
            </div>
        @endif

        <div class="glass p-8 rounded-3xl space-y-6">
            <h3 class="text-lg font-bold border-b border-slate-700 pb-4">Order Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="text-slate-500 uppercase tracking-wider font-bold text-[10px] mb-1">Uploaded At</p>
                    <p>{{ $order->created_at->format('M d, Y - H:i') }}</p>
                </div>
                <div>
                    <p class="text-slate-500 uppercase tracking-wider font-bold text-[10px] mb-1">Files Count</p>
                    <p>{{ $order->files_count }} files</p>
                </div>
            </div>
        </div>
    </div>
</div>
