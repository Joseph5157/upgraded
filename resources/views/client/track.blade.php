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
    <div class="max-w-2xl mx-auto space-y-8 mt-12">
        <div class="glass p-8 rounded-3xl shadow-2xl flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-2xl font-bold">Order #{{ $order->id }}</h1>
                <p class="text-slate-400">Status:
                    @php
                        $statusClass = [
                            'pending' => 'bg-yellow-500/10 text-yellow-400',
                            'claimed' => 'bg-amber-500/10 text-amber-400',
                            'processing' => 'bg-blue-500/10 text-blue-400',
                            'delivered' => 'bg-green-500/10 text-green-400',
                            'overdue' => 'bg-red-500/10 text-red-400',
                        ][$order->computed_status];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusClass }}">
                        {{ $order->computed_status }}
                    </span>
                </p>
            </div>

            <div class="text-center md:text-right">
                <p class="text-slate-500 text-sm mb-1 uppercase tracking-widest font-bold">Time Remaining</p>
                <div id="countdown" class="text-4xl font-mono font-bold text-white tracking-tighter tabular-nums">
                    --:--
                </div>
            </div>
        </div>

        @if($order->status == 'delivered')
            <div class="glass p-8 rounded-3xl border-2 border-green-500/30 text-center space-y-4">
                <h2 class="text-xl font-bold text-green-400">Your results are ready!</h2>
                <p class="text-slate-400">You can download your report bundle below. Please note that downloads are restricted
                    to one-time only.</p>

                @if(!$order->is_downloaded)
                    <a href="{{ route('client.download', $order->token_view) }}"
                        class="inline-block py-4 px-12 bg-green-600 hover:bg-green-500 text-white font-bold rounded-2xl shadow-lg shadow-green-500/25 transition-all transform hover:scale-[1.05]">
                        Download Results Bundle
                    </a>
                @else
                    <button disabled
                        class="inline-block py-4 px-12 bg-slate-700 text-slate-500 font-bold rounded-2xl cursor-not-allowed">
                        Report Already Downloaded
                    </button>
                @endif
            </div>
        @else
            <div class="glass p-8 rounded-3xl text-center space-y-4">
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                </div>
                <h2 class="text-xl font-bold">{{ $order->status->value === 'claimed' ? 'Reserved...' : 'Processing...' }}</h2>
                <p class="text-slate-400">{{ $order->status->value === 'claimed' ? 'Your order has been reserved by a vendor and is waiting to be started.' : 'Our agents are working on your documents. This page checks for updates automatically.' }}</p>
                <div class="flex items-center justify-center gap-2 mt-1">
                    <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                    <span id="refresh-badge" class="text-[11px] font-semibold text-blue-400 tracking-wide">Checking for updates in 60s…</span>
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
        const dueTime = new Date("{{ $order->due_at->toIso8601String() }}").getTime();
        const countdownElement = document.getElementById('countdown');
        const status = "{{ $order->status }}";

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = dueTime - now;

            if (distance < 0) {
                if (status !== 'delivered') {
                    countdownElement.innerHTML = `<span class="text-amber-500 text-2xl">Finalizing...</span>`;
                    countdownElement.classList.add('animate-pulse');
                } else {
                    countdownElement.innerHTML = "00:00";
                }
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            countdownElement.innerHTML =
                (minutes < 10 ? "0" : "") + minutes + ":" +
                (seconds < 10 ? "0" : "") + seconds;
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();

        // Auto-refresh with live badge — stops once delivered
        if (status !== 'delivered') {
            let refreshIn = 60;
            const badge = document.getElementById('refresh-badge');

            const pollTick = setInterval(() => {
                refreshIn--;
                if (badge) {
                    badge.textContent = refreshIn > 0
                        ? `Checking for updates in ${refreshIn}s…`
                        : 'Checking…';
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
