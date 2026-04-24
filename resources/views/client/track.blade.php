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
    @include('client.track.partials.live', [
        'link' => $link,
        'order' => $order,
        'pulseUrl' => $pulseUrl,
        'signature' => $signature,
    ])

    <script>
        (function () {
            const pollIntervalMs = 12000;
            let pollTimer = null;
            let inFlight = false;

            async function checkForTrackUpdates() {
                if (inFlight) {
                    return;
                }

                const liveEl = document.getElementById('guest-link-track-live');
                if (!liveEl) {
                    return;
                }

                const pulseUrl = liveEl.dataset.pulseUrl;
                const signature = liveEl.dataset.pulseSignature || '';
                inFlight = true;

                try {
                    const response = await fetch(`${pulseUrl}?signature=${encodeURIComponent(signature)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.status === 404) {
                        stopTrackPolling();
                        window.location.reload();
                        return;
                    }

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (payload.liveHtml) {
                        const current = document.getElementById('guest-link-track-live');
                        if (current) {
                            current.outerHTML = payload.liveHtml;
                        }
                    }
                } catch (error) {
                    // Ignore transient polling failures; the next tick will retry.
                } finally {
                    inFlight = false;
                }
            }

            function startTrackPolling() {
                if (pollTimer !== null) {
                    return;
                }

                checkForTrackUpdates();
                pollTimer = window.setInterval(checkForTrackUpdates, pollIntervalMs);
            }

            function stopTrackPolling() {
                if (pollTimer !== null) {
                    window.clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            window.__guestLinkTrackPolling = {
                start: startTrackPolling,
                stop: stopTrackPolling,
            };

            window.addEventListener('focus', checkForTrackUpdates);
            window.addEventListener('pageshow', checkForTrackUpdates);
            window.addEventListener('online', checkForTrackUpdates);

            startTrackPolling();
        })();
    </script>
</body>
</html>
