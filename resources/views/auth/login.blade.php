<x-guest-layout>
    <x-auth-session-status class="mb-6" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-6 rounded-xl p-4" style="background:#fef3c7; border:1px solid #fcd34d;">
            <p class="text-sm font-semibold" style="color:#92400e;">
                {{ $errors->first() }}
            </p>
        </div>
    @endif

    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold" style="color:#0f172a;">Welcome back</h2>
        <p class="text-sm mt-1" style="color:#64748b;">Sign in using your Telegram account</p>
    </div>

    <div class="rounded-xl p-5 mb-6 text-center" style="background:#f1f5f9; border:1px solid #e2e8f0;">
        <p class="text-sm font-medium mb-1" style="color:#475569;">Open Telegram and send</p>
        <p class="text-lg font-bold" style="color:#0f172a;">
            /login to @{{ config('services.telegram.bot_username', 'YourBotUsername') }}
        </p>
    </div>

    <a
        href="https://t.me/{{ config('services.telegram.bot_username') }}?text=/login"
        target="_blank"
        class="flex items-center justify-center gap-3 w-full py-3 px-4 rounded-xl font-semibold text-sm text-white transition"
        style="background:#2AABEE;"
    >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
        </svg>
        Open Telegram & Send /login
    </a>

    <p class="text-center text-xs mt-6" style="color:#94a3b8;">
        Don't have an account? Contact your admin.
    </p>
</x-guest-layout>
