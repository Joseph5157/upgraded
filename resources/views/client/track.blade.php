<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #{{ $order->id }} - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-200 min-h-screen p-4 sm:p-6 overflow-x-hidden">
    @php
        $downloadRoute = isset($link)
            ? route('client.link.download', [$link->token, $order->token_view])
            : route('client.download', $order->token_view);

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
    @endphp
    <div class="max-w-2xl mx-auto space-y-8 mt-12">
        <div class="glass p-8 rounded-3xl shadow-2xl flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-2xl font-bold">Order #{{ $order->id }}</h1>
                <p class="text-slate-400">
                    Status:
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </p>
            </div>
        </div>

        @if($order->status == 'delivered')
            <div class="glass p-8 rounded-3xl border-2 border-green-500/30 text-center space-y-4">
                <h2 class="text-xl font-bold text-green-400">Your results are ready</h2>
                <p class="text-slate-400">Download the report bundle now. This download can be used only once.</p>

                @if(!$order->is_downloaded)
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
                    {{ $order->status->value === 'claimed' ? 'Reserved' : 'In progress' }}
                </h2>
                <p class="text-slate-400">
                    {{ $order->status->value === 'claimed'
                        ? 'A vendor has reserved your order and will start work shortly.'
                        : 'Your order is being worked on. This page refreshes automatically.' }}
                </p>
                <div class="flex items-center justify-center gap-2 mt-1">
                    <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                    <span id="refresh-badge" class="text-[11px] font-semibold text-blue-400 tracking-wide">Checking for updates in 60s...</span>
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

    <script>
        // Auto-refresh with live badge - stops once delivered
        const status = "{{ $order->status }}";
        if (status !== 'delivered') {
            let refreshIn = 60;
            const badge = document.getElementById('refresh-badge');

            const pollTick = setInterval(() => {
                refreshIn--;
                if (badge) {
                    badge.textContent = refreshIn > 0
                        ? `Checking for updates in ${refreshIn}s...`
                        : 'Checking...';
                }
                if (refreshIn <= 0) {
                    clearInterval(pollTick);
                    window.location.reload();
                }
            }, 1000);
        }
    </script>
</body>

</html>
