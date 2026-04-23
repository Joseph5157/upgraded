<x-admin-layout>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight dark:text-white">Profile Settings</h1>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mt-1 dark:text-slate-500">
                Manage your profile and login security
            </p>
        </div>
    </div>

    {{-- Flash Banners --}}
    @if(session('status') === 'profile-updated')
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> Profile updated successfully.
        </div>
    @endif

    {{-- Three-column card row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- CARD 1: Operator Identity --}}
        <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl p-8 flex flex-col items-center text-center gap-4 dark:bg-[#0a0a0c] dark:border-white/5">
            <div class="w-20 h-20 rounded-full bg-[#4F6EF7] flex items-center justify-center text-white text-2xl font-bold select-none">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="space-y-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                <p class="text-sm text-gray-400 font-mono">Portal ID: {{ $user->portal_number }}</p>
            </div>
            <span class="bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 text-[9px] font-bold uppercase tracking-widest rounded-full px-3 py-1">
                ADMIN
            </span>
            <div class="w-full border-t border-[#E2E6EA] dark:border-white/5 pt-4 mt-2 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-400 uppercase tracking-widest font-bold text-[10px]">Member Since</span>
                    <span class="text-gray-700 font-mono dark:text-slate-300">{{ $user->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-400 uppercase tracking-widest font-bold text-[10px]">Role</span>
                    <span class="text-red-500 font-bold text-[10px] uppercase">{{ $user->role }}</span>
                </div>
            </div>
        </div>

        {{-- CARD 2: Update Name --}}
        <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl p-8 dark:bg-[#0a0a0c] dark:border-white/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[#4F6EF7]/10 rounded-xl flex items-center justify-center text-[#4F6EF7]">
                    <i data-lucide="user-pen" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Update Name</h3>
                    <p class="text-[11px] text-gray-400">Change the name shown across the portal.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('patch')
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Full Name</label>
                    <input name="name" type="text" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Portal ID</label>
                    <input type="text" value="{{ $user->portal_number }}" disabled
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-[#F5F6F8] text-sm text-gray-400 dark:bg-white/5 dark:border-white/5">
                    <p class="text-[10px] text-gray-400 mt-1">Your Portal ID is fixed and used for login.</p>
                </div>
                    <button type="submit"
                    class="w-full bg-[#4F6EF7] hover:bg-[#3B5BDB] text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl transition-colors">
                    Save Changes
                </button>
            </form>
        </div>

        {{-- CARD 3: Telegram Login --}}
        <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl p-8 dark:bg-[#0a0a0c] dark:border-white/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-blue-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Telegram Login</h3>
                    <p class="text-[11px] text-gray-400">Your account is secured via Telegram login codes.</p>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-xl bg-green-500/10 border border-green-500/20">
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400">Telegram Connected</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                </div>
                    <p class="text-xs text-gray-400 dark:text-slate-500">
                    You log in using a one-time code sent to your Telegram account.
                    No password is required or stored.
                </p>
            </div>
        </div>
    </div>

</x-admin-layout>
