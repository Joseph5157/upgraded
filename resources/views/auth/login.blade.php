<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        @php
            $emailErrors = $errors->get('email');
            $isFrozenError = collect($emailErrors)->contains(fn($m) => str_contains(strtolower($m), 'frozen'));
        @endphp

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold" style="color:#111827;">Welcome back</h2>
            <p class="text-sm mt-1" style="color:#4b5563;">Sign in to your account to continue</p>
        </div>

        <!-- Frozen account alert -->
        @if($isFrozenError)
            <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5 w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-amber-800 text-sm">Account Frozen</p>
                    @foreach($emailErrors as $message)
                        <p class="text-sm text-amber-700 mt-0.5">{{ $message }}</p>
                    @endforeach
                    <a href="mailto:support@plagexpert.in" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium mt-2 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Support
                    </a>
                </div>
            </div>
        @endif

        <!-- Email -->
        <div class="mb-5">
            <label for="email" class="block text-sm font-semibold mb-1.5" style="color:#111827;">Email address</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                    style="color:#111827; background:#f9fafb; border: 1.5px solid #d1d5db;"
                    class="input-field w-full pl-10 pr-4 py-2.5 rounded-xl text-sm {{ $errors->has('email') && !$isFrozenError ? 'border-red-400 bg-red-50' : '' }}"
                />
            </div>
            @if(!$isFrozenError)
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            @endif
        </div>

        <!-- Password -->
        <div class="mb-5">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold" style="color:#111827;">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    style="color:#111827; background:#f9fafb; border: 1.5px solid #d1d5db;"
                    class="input-field w-full pl-10 pr-10 py-2.5 rounded-xl text-sm {{ $errors->has('password') ? 'border-red-400 bg-red-50' : '' }}"
                />
                <!-- Toggle password visibility -->
                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="mb-5">
            <label for="remember_me" class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                <input id="remember_me" type="checkbox" name="remember"
                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 transition">
                <span class="text-sm" style="color:#374151;">Keep me signed in</span>
            </label>
        </div>

        <!-- Cloudflare Turnstile -->
        <div class="mb-6">
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
            <x-input-error :messages="$errors->get('cf-turnstile-response')" class="mt-1.5" />
        </div>

        <!-- Submit -->
        <button type="submit" class="login-btn w-full py-3 px-4 text-white font-semibold text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Sign in to Portal
        </button>

    </form>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;
            } else {
                input.type = 'password';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
            }
        }
    </script>
</x-guest-layout>
