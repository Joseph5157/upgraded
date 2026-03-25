<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
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
                background: #ffffff;
                border: 1.5px solid #dbe3f0;
                color: #0f172a;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .input-field::placeholder { color: #94a3b8; }
            .input-field:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 4px rgba(99,102,241,0.12);
                outline: none;
                background: #ffffff;
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
            .panel-text-shadow { text-shadow: 0 12px 34px rgba(67, 56, 202, 0.18); }
            .hero-shell {
                background:
                    radial-gradient(circle at 76% 20%, rgba(108, 99, 255, 0.24), transparent 24%),
                    radial-gradient(circle at 85% 75%, rgba(88, 76, 255, 0.18), transparent 28%),
                    linear-gradient(135deg, #17103c 0%, #221755 48%, #24185b 100%);
            }
            .hero-copy {
                max-width: 680px;
            }
            .hero-copy p {
                text-wrap: balance;
            }
            ::-webkit-scrollbar { width: 4px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 99px; }

            @media (max-width: 640px) {
                .mobile-turnstile {
                    transform: scale(0.94);
                    transform-origin: center top;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased" style="background:#f8fafc;">
        <div class="min-h-screen flex">

            <!-- Left Panel — Branding -->
            <div class="hero-shell hidden lg:flex lg:w-1/2 relative overflow-hidden" >

                <!-- Grid overlay -->
                <div class="absolute inset-0 opacity-18" style="background-image: linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px); background-size: 40px 40px;"></div>

                <!-- Decorative accent blobs -->
                <div class="absolute top-0 right-0 w-[34rem] h-[34rem] rounded-full" style="background: radial-gradient(circle, rgba(129,140,248,0.24) 0%, transparent 70%);"></div>
                <div class="absolute bottom-[-6rem] left-[-4rem] w-[28rem] h-[28rem] rounded-full" style="background: radial-gradient(circle, rgba(99,102,241,0.16) 0%, transparent 72%);"></div>

                <div class="relative z-10 flex flex-col min-h-screen w-full px-14 py-14 xl:px-16 xl:py-16">

                    <!-- TOP: Logo -->
                    <div>
                        <a href="/" class="inline-flex items-center">
                            <x-application-logo class="h-16 w-auto rounded-sm bg-white px-2 py-1 shadow-[0_12px_30px_rgba(0,0,0,0.22)]" />
                        </a>
                    </div>

                    <!-- MIDDLE: Hero copy — vertically centred in the remaining space -->
                    <div class="flex-1 flex flex-col justify-center py-10 hero-copy">
                        <div class="inline-flex items-center gap-3 rounded-full px-5 py-2.5 mb-10 w-fit shadow-[inset_0_1px_0_rgba(255,255,255,0.10)] mx-auto" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.16);">
                            <span class="w-2.5 h-2.5 rounded-full animate-pulse shadow-[0_0_0_5px_rgba(52,211,153,0.12)]" style="background:#48c7a8;"></span>
                            <span class="text-[0.8rem] font-semibold tracking-[0.18em] uppercase" style="color:#f8fafc;">Secure Client Portal</span>
                        </div>
                        <h1 class="text-[4rem] leading-[1.02] font-extrabold mb-7 panel-text-shadow tracking-[-0.045em] text-center" style="color:#f8fafc;">
                            <span style="color:#f8fafc;">Plagiarism &amp; AI Reports,</span><br>
                            <span style="color:#b8b7ff;">all in one place.</span>
                        </h1>
                        <p class="text-[1.08rem] leading-[1.85] max-w-[38rem] font-medium text-center mx-auto" style="color:#d4d0e7;">
                            Upload, track, and receive your plagiarism and AI reports through a secure, fully API automated system — designed for speed, accuracy, and zero manual delays.
                        </p>
                    </div>

                    <!-- BOTTOM: Feature highlights -->
                    <div class="border-t pt-8 pb-2" style="border-color:rgba(255,255,255,0.10);">
                        <div class="grid grid-cols-3 gap-5 items-center">
                            <div class="flex items-center gap-4 pr-4">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 shadow-[inset_0_1px_0_rgba(255,255,255,0.35)]" style="background:#4ca89c;">
                                    <svg class="w-4 h-4" style="color:#ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <p class="text-[0.95rem] font-semibold leading-tight" style="color:#ffffff;">100% Confidential</p>
                            </div>
                            <div class="flex items-center gap-4 px-4 border-x" style="border-color:rgba(255,255,255,0.10);">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 shadow-[inset_0_1px_0_rgba(255,255,255,0.35)]" style="background:#4ca89c;">
                                    <svg class="w-4 h-4" style="color:#ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <p class="text-[0.95rem] font-semibold leading-tight" style="color:#ffffff;">Zero Repository Storage</p>
                            </div>
                            <div class="flex items-center gap-4 pl-4">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 shadow-[inset_0_1px_0_rgba(255,255,255,0.35)]" style="background:#4ca89c;">
                                    <svg class="w-4 h-4" style="color:#ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <p class="text-[0.95rem] font-semibold leading-tight" style="color:#ffffff;">API-Integrated System</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right Panel — Form -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-4 py-8 sm:px-10 sm:py-12 xl:px-12" style="background:#f8fafc;">

                <!-- Mobile logo -->
                <div class="lg:hidden mb-5 flex items-center justify-center">
                    <a href="/" class="inline-flex items-center">
                        <x-application-logo class="h-12 sm:h-14 w-auto rounded-sm bg-white px-2 py-1 shadow-[0_12px_30px_rgba(0,0,0,0.22)]" />
                    </a>
                </div>

                <div class="w-full max-w-[32rem]">
                    <!-- Card -->
                    <div class="rounded-[1.75rem] p-5 sm:p-9 shadow-[0_24px_60px_rgba(15,23,42,0.10)]" style="background:#ffffff; border:1px solid #e2e8f0;">
                        {{ $slot }}
                    </div>
                </div>

                <p class="mt-5 sm:mt-8 text-center text-xs" style="color:#64748b;">
                    &copy; {{ date('Y') }} PlagExpert. All rights reserved.
                </p>
            </div>

        </div>
    </body>
</html>
