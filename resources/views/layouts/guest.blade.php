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
            .brand-gradient {
                background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 75%, #6366f1 100%);
            }
            .grid-pattern {
                background-image:
                    linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
                background-size: 40px 40px;
            }
            .glow-circle {
                background: radial-gradient(circle, rgba(99,102,241,0.4) 0%, transparent 70%);
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
            <div class="hidden lg:flex lg:w-1/2 brand-gradient grid-pattern relative overflow-hidden flex-col justify-between p-12">

                <!-- Decorative glow blobs -->
                <div class="absolute top-[-80px] left-[-80px] w-80 h-80 glow-circle opacity-60"></div>
                <div class="absolute bottom-[-60px] right-[-60px] w-96 h-96 glow-circle opacity-40"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] glow-circle opacity-20"></div>

                <!-- Logo -->
                <div class="relative z-10">
                    <a href="/" class="inline-block">
                        <x-application-logo class="w-32 h-auto drop-shadow-lg" />
                    </a>
                </div>

                <!-- Center copy -->
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-1.5 mb-6">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-white/80 text-xs font-medium tracking-wide uppercase">Portal Access</span>
                    </div>
                    <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4">
                        Your workspace,<br>
                        <span class="text-indigo-300">all in one place.</span>
                    </h1>
                    <p class="text-indigo-200 text-lg leading-relaxed max-w-sm">
                        Manage orders, track progress, and collaborate — securely and efficiently.
                    </p>
                </div>

                <!-- Bottom stats -->
                <div class="relative z-10 flex items-center gap-8">
                    <div>
                        <p class="text-white text-2xl font-bold">100%</p>
                        <p class="text-indigo-300 text-xs mt-0.5">Secure Access</p>
                    </div>
                    <div class="w-px h-10 bg-white/20"></div>
                    <div>
                        <p class="text-white text-2xl font-bold">24 / 7</p>
                        <p class="text-indigo-300 text-xs mt-0.5">Availability</p>
                    </div>
                    <div class="w-px h-10 bg-white/20"></div>
                    <div>
                        <p class="text-white text-2xl font-bold">Fast</p>
                        <p class="text-indigo-300 text-xs mt-0.5">Processing</p>
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
