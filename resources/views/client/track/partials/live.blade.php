<div id="guest-link-track-live" data-pulse-url="{{ $pulseUrl ?? '' }}" data-pulse-signature="{{ $signature ?? '' }}">
    @php
        $downloadRoute = $link
            ? route('client.link.download', [$link->token, $order->token_view])
            : route('client.download', $order->token_view);

        $statusClass = [
            'pending' => 'badge-neutral badge-outline',
            'claimed' => 'badge-warning badge-outline',
            'processing' => 'badge-info badge-outline',
            'delivered' => 'badge-success badge-outline',
        ][$order->computed_status] ?? 'badge-neutral badge-outline';

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
                    <span class="badge badge-sm {{ $statusClass }} gap-1.5 text-[9px] font-bold uppercase tracking-[0.12em]">
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
        @elseif($order->computed_status === 'cancelled')
            <div class="glass p-8 rounded-3xl border-2 border-slate-500/30 text-center space-y-4">
                <h2 class="text-xl font-bold text-slate-400">Order Cancelled</h2>
                <p class="text-slate-500">This order has been cancelled and is no longer being processed.</p>
            </div>
        @else
            <div class="glass p-8 rounded-3xl text-center space-y-4">
                <ul class="steps steps-horizontal w-full mb-6">
                    <li class="step step-primary">Submitted</li>
                    <li class="step {{ $order->claimed_by ? 'step-primary' : '' }}">Claimed</li>
                    <li class="step {{ in_array($order->status->value, ['processing','delivered']) ? 'step-primary' : '' }}">Processing</li>
                    <li class="step {{ $order->status->value === 'delivered' ? 'step-primary' : '' }}">Ready</li>
                </ul>
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
