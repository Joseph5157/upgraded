<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <script>document.documentElement.classList.add('dark');</script>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="/favicon.png">

        <title>PlagExpert — Sign In</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Cloudflare Turnstile -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <style>
            body { font-family: 'Inter', sans-serif; }
            .input-field {
                background: rgba(255,255,255,0.04);
                border: 1.5px solid rgba(255,255,255,0.08);
                color: #e2e8f0;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .input-field::placeholder { color: #475569; }
            .input-field:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99,102,241,0.18);
                outline: none;
                background: rgba(99,102,241,0.06);
            }
            .login-btn {
                background: linear-gradient(135deg, #4338ca, #6366f1);
                transition: all 0.2s ease;
            }
            .login-btn:hover {
                background: linear-gradient(135deg, #3730a3, #4f46e5);
                transform: translateY(-1px);
                box-shadow: 0 8px 25px rgba(99,102,241,0.35);
            }
            .login-btn:active { transform: translateY(0); }
            .panel-text-shadow { text-shadow: 0 2px 8px rgba(0,0,0,0.6); }
            ::-webkit-scrollbar { width: 4px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 99px; }
        </style>
    </head>
    <body class="font-sans antialiased" style="background:#0f1117;">
        <div class="min-h-screen flex">

            <!-- Left Panel — Branding -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col justify-between p-12" style="background: linear-gradient(150deg, #0c0a1e 0%, #11103a 35%, #1a1855 70%, #1e1a6e 100%);">

                <!-- Grid overlay -->
                <div class="absolute inset-0" style="background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 40px 40px;"></div>

                <!-- Decorative accent blobs -->
                <div class="absolute top-0 right-0 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(129,140,248,0.12) 0%, transparent 70%);"></div>
                <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(99,102,241,0.10) 0%, transparent 70%);"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full" style="background: radial-gradient(circle, rgba(168,85,247,0.06) 0%, transparent 70%);"></div>

                <!-- Logo -->
                <div class="relative z-10 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base" style="background:#4f46e5; color:#fff;">P</div>
                    <div>
                        <p class="text-sm font-bold panel-text-shadow" style="color:#ffffff;">PlagExpert</p>
                        <p class="text-[10px] uppercase tracking-widest" style="color:#64748b;">Agent Portal</p>
                    </div>
                </div>

                <!-- Center copy -->
                <div class="relative z-10 flex-1 flex flex-col justify-center py-12">
                    <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 mb-6 w-fit" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);">
                        <span class="w-2 h-2 rounded-full animate-pulse" style="background:#34d399;"></span>
                        <span class="text-xs font-semibold tracking-widest uppercase" style="color:#94a3b8;">Secure Client Portal</span>
                    </div>
                    <h1 class="text-4xl xl:text-5xl font-extrabold leading-tight mb-5 panel-text-shadow" style="color:#ffffff;">
                        Plagiarism &amp; AI Reports,<br>
                        <span style="color:#a5b4fc;">all in one place.</span>
                    </h1>
                    <p class="text-base leading-relaxed max-w-sm" style="color:#64748b;">
                        Upload, track, and receive your plagiarism and AI reports through a secure, fully API automated system — designed for speed, accuracy, and zero manual delays.
                    </p>
                </div>

                <!-- Bottom highlights -->
                <div class="relative z-10 flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(99,102,241,0.15);">
                            <svg class="w-3.5 h-3.5" style="color:#a5b4fc;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold panel-text-shadow" style="color:#ffffff;">100% Confidential</p>
                        </div>
                    </div>
                    <div class="w-px h-8" style="background:rgba(255,255,255,0.1);"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(52,211,153,0.12);">
                            <svg class="w-3.5 h-3.5" style="color:#34d399;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold panel-text-shadow" style="color:#ffffff;">Zero Repository Storage</p>
                        </div>
                    </div>
                    <div class="w-px h-8" style="background:rgba(255,255,255,0.1);"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(251,191,36,0.12);">
                            <svg class="w-3.5 h-3.5" style="color:#fbbf24;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold panel-text-shadow" style="color:#ffffff;">API-Integrated System</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel — Form -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12 sm:px-12" style="background:#0f1117;">

                <!-- Mobile logo -->
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm" style="background:#4f46e5; color:#fff;">P</div>
                    <div>
                        <p class="text-sm font-bold" style="color:#ffffff;">PlagExpert</p>
                        <p class="text-[10px] uppercase tracking-widest" style="color:#64748b;">Agent Portal</p>
                    </div>
                </div>

                <div class="w-full max-w-md">
                    <!-- Card -->
                    <div class="rounded-2xl p-7 sm:p-9" style="background:#13151c; border:1px solid rgba(255,255,255,0.06);">
                        {{ $slot }}
                    </div>
                </div>

                <p class="mt-8 text-center text-xs" style="color:#334155;">
                    &copy; {{ date('Y') }} PlagExpert. All rights reserved.
                </p>
            </div>

        </div>
    </body>
</html>
