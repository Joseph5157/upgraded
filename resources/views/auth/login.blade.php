<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Sign In</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-12">

    <div class="w-full max-w-sm">

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 px-8 py-10 text-center">

            {{-- Logo --}}
            <div class="flex justify-center mb-5">
                <img src="{{ asset('images/logo.png.jpeg') }}"
                     alt="{{ config('app.name') }}"
                     class="h-12 w-auto rounded-lg shadow-sm">
            </div>

            {{-- Heading --}}
            <h1 class="text-2xl font-bold text-slate-900 mb-1">Welcome back</h1>
            <p class="text-sm text-slate-500 mb-8">Sign in using your Telegram account</p>

            {{-- Error (invalid / expired link) --}}
            @if ($errors->has('link') || $errors->has('telegram'))
                <div class="mb-6 rounded-xl px-4 py-3 flex items-center gap-2.5 text-left"
                     style="background:#fef2f2; border:1px solid #fca5a5;">
                    <svg class="w-4 h-4 flex-shrink-0" style="color:#ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-red-700">
                        {{ $errors->first('link') ?: $errors->first('telegram') }}
                    </p>
                </div>
            @endif

            {{-- Instruction --}}
            <p class="text-sm text-slate-600 mb-2">Open Telegram and send</p>
            <div class="inline-flex items-center gap-2 rounded-lg px-4 py-2 mb-6"
                 style="background:#f0f4ff; border:1px solid #c7d2fe;">
                <svg class="w-4 h-4 flex-shrink-0" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="text-sm font-semibold" style="color:#4338ca;">
                    /login to @{{ config('services.telegram.bot_username', 'YourBotUsername') }}
                </span>
            </div>

            {{-- Deep-link button --}}
            @php
                $botUsername = config('services.telegram.bot_username', 'YourBotUsername');
            @endphp
            <a href="tg://resolve?domain={{ $botUsername }}&text=/login"
               class="flex items-center justify-center gap-2.5 w-full py-3 px-4 rounded-xl text-white text-sm font-semibold transition-all"
               style="background:linear-gradient(135deg,#229ED9,#0d7abf);">
                {{-- Telegram paper-plane icon --}}
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.16 13.67l-2.965-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.993.889z"/>
                </svg>
                Open Telegram &amp; Send /login
            </a>

            {{-- Footer note --}}
            <p class="mt-6 text-xs text-slate-400">
                Don&rsquo;t have an account? Contact your admin.
            </p>

        </div>

    </div>

</body>
</html>
