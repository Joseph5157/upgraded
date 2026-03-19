<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} — Sign In</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Cloudflare Turnstile -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <style>
            .panel-text-shadow {
                text-shadow: 0 2px 8px rgba(0,0,0,0.6);
            }
            .input-field {
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .input-field:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
                outline: none;
            }
            .login-btn {
                background: linear-gradient(135deg, #4338ca, #6366f1);
                transition: all 0.2s ease;
            }
            .login-btn:hover {
                background: linear-gradient(135deg, #3730a3, #4f46e5);
                transform: translateY(-1px);
                box-shadow: 0 8px 25px rgba(99,102,241,0.4);
            }
            .login-btn:active {
                transform: translateY(0);
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-white">
        <div class="min-h-screen flex">

            <!-- Left Panel — Branding -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col justify-between p-12" style="background: linear-gradient(150deg, #0f0c29 0%, #1a1744 35%, #2d2a6e 70%, #312e81 100%); background-image: linear-gradient(150deg, #0f0c29 0%, #1a1744 35%, #2d2a6e 70%, #312e81 100%), linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: cover, 40px 40px, 40px 40px;">

                <!-- Decorative accent blobs -->
                <div class="absolute top-0 right-0 w-72 h-72 rounded-full" style="background: radial-gradient(circle, rgba(129,140,248,0.15) 0%, transparent 70%);"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full" style="background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);"></div>

                <!-- Logo -->
                <div class="relative z-10">
                    <a href="/" class="inline-block">
                        <x-application-logo class="w-32 h-auto drop-shadow-lg" />
                    </a>
                </div>

                <!-- Center copy -->
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 mb-6" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25);">
                        <span class="w-2 h-2 rounded-full animate-pulse" style="background:#34d399;"></span>
                        <span class="text-xs font-semibold tracking-widest uppercase panel-text-shadow" style="color:#ffffff;">Portal Access</span>
                    </div>
                    <h1 class="text-4xl xl:text-5xl font-extrabold leading-tight mb-4 panel-text-shadow" style="color:#ffffff;">
                        Your workspace,<br>
                        <span style="color:#a5b4fc;">all in one place.</span>
                    </h1>
                    <p class="text-lg leading-relaxed max-w-sm panel-text-shadow" style="color:#cbd5e1;">
                        Manage orders, track progress, and collaborate — securely and efficiently.
                    </p>
                </div>

                <!-- Bottom stats -->
                <div class="relative z-10 flex items-center gap-8">
                    <div>
                        <p class="text-2xl font-bold panel-text-shadow" style="color:#ffffff;">100%</p>
                        <p class="text-xs mt-0.5" style="color:#94a3b8;">Secure Access</p>
                    </div>
                    <div class="w-px h-10" style="background:rgba(255,255,255,0.2);"></div>
                    <div>
                        <p class="text-2xl font-bold panel-text-shadow" style="color:#ffffff;">24 / 7</p>
                        <p class="text-xs mt-0.5" style="color:#94a3b8;">Availability</p>
                    </div>
                    <div class="w-px h-10" style="background:rgba(255,255,255,0.2);"></div>
                    <div>
                        <p class="text-2xl font-bold panel-text-shadow" style="color:#ffffff;">Fast</p>
                        <p class="text-xs mt-0.5" style="color:#94a3b8;">Processing</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel — Form -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12 sm:px-12 bg-white">

                <!-- Mobile logo -->
                <div class="lg:hidden mb-8">
                    <a href="/">
                        <x-application-logo class="w-28 h-auto mx-auto" />
                    </a>
                </div>

                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>

                <p class="mt-10 text-center text-xs text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>

        </div>
    </body>
</html>
