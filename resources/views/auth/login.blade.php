<x-guest-layout>
    <x-auth-session-status class="mb-6" :status="session('status')" />

    @if(session('error') || request()->boolean('expired'))
        <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/20">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
                {{ session('error') ?: 'Your session expired. Please sign in again.' }}
            </p>
        </div>
    @endif

    {{-- Step 1: Enter Portal ID --}}
    @if(!session('otp_sent'))

        @if($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20">
                <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold" style="color:#0f172a;">Welcome back</h2>
            <p class="text-sm mt-1" style="color:#64748b;">Enter your Portal ID to receive a login code</p>
        </div>

        <form method="POST" action="{{ route('login.send-otp') }}">
            @csrf
            <div class="mb-5">
                <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#64748b;">
                    Portal ID
                </label>
                <input
                    type="number"
                    name="portal_number"
                    value="{{ old('portal_number') }}"
                    placeholder="e.g. 1001"
                    autofocus
                    required
                    class="w-full px-4 py-3 rounded-xl border text-sm font-mono text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                    style="border-color:#E2E8F0; background:#fff;"
                >
                <p class="text-xs mt-2" style="color:#94a3b8;">
                    Your Portal ID was sent to you when your account was created.
                </p>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white transition"
                style="background:#4F6EF7;">
                Send Login Code
            </button>
        </form>

        <p class="text-center text-xs mt-6" style="color:#94a3b8;">
            Don't have an account? Contact your admin.
        </p>

    {{-- Step 2: Enter OTP --}}
    @else

        @if($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20">
                <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="mb-8 text-center">
            <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center" style="background:#EEF2FF;">
                <svg class="w-8 h-8" style="color:#4F6EF7;" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold" style="color:#0f172a;">Check your Telegram</h2>
            <p class="text-sm mt-1" style="color:#64748b;">
                We sent a 6-digit code to your Telegram account
            </p>
        </div>

        <form method="POST" action="{{ route('login.verify-otp') }}">
            @csrf
            <input type="hidden" name="portal_number" value="{{ session('portal_number') }}">

            <div class="mb-5">
                <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#64748b;">
                    Login Code
                </label>
                <input
                    type="text"
                    name="otp"
                    placeholder="000000"
                    maxlength="6"
                    autofocus
                    required
                    autocomplete="one-time-code"
                    class="w-full px-4 py-3 rounded-xl border text-center text-2xl font-mono tracking-widest text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                    style="border-color:#E2E8F0; background:#fff; letter-spacing: 0.5em;"
                >
                <p class="text-xs mt-2 text-center" style="color:#94a3b8;">
                    Code expires in 5 minutes
                </p>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white transition mb-3"
                style="background:#4F6EF7;">
                Verify and Log In
            </button>
        </form>

        <form method="GET" action="{{ route('login') }}">
            <button type="submit"
                class="w-full py-2.5 px-4 rounded-xl font-semibold text-sm transition"
                style="color:#64748b; background:#F1F5F9;">
                Use a different Portal ID
            </button>
        </form>

    @endif

</x-guest-layout>
