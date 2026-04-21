<x-vendor-layout title="Settings">

    {{-- Flash Banners --}}
    @if(session('status') === 'profile-updated')
        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 text-sm font-semibold">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Profile updated successfully.
        </div>
    @endif

    {{-- Two-column grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ===== LEFT COLUMN (2/3) ===== --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- CARD 1: Update Profile --}}
            <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-6 dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#E2E6EA] dark:border-white/[0.06]">
                    <div class="w-8 h-8 bg-indigo-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-[#1A1D23] dark:text-white">Update Profile</h2>
                        <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-0.5 dark:text-slate-500">Display name &amp; Portal ID</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('patch')

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Full Name</label>
                            <input
                                id="name" name="name" type="text"
                                value="{{ old('name', $user->name) }}"
                                required autocomplete="name"
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all dark:bg-[#0f1117] dark:border-white/[0.08] dark:text-slate-200 dark:focus:border-indigo-500"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Portal ID</label>
                            <input type="text" value="{{ $user->portal_number }}" disabled
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-[#F5F6F8] text-sm text-gray-400 dark:bg-white/5 dark:border-white/5">
                            <p class="text-[10px] text-slate-500 mt-1">Your Portal ID cannot be changed.</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-5 py-2.5 rounded-xl transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- CARD 2: Telegram Login --}}
            <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-6 dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#E2E6EA] dark:border-white/[0.06]">
                    <div class="w-8 h-8 bg-blue-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-[#1A1D23] dark:text-white">Telegram Login</h2>
                        <p class="text-[10px] text-[#6B7280] uppercase tracking-widest font-semibold mt-0.5 dark:text-slate-500">Your account is secured via Telegram OTP</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-green-500/10 border border-green-500/20">
                        <span class="text-xs font-semibold text-green-600 dark:text-green-400">Telegram Connected</span>
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-[#6B7280] dark:text-slate-500">
                        You log in using a one-time code sent to your Telegram account.
                        No password is required or stored.
                    </p>
                </div>
            </div>

        </div>{{-- /left column --}}

        {{-- ===== RIGHT COLUMN (1/3) ===== --}}
        <div class="space-y-5">

            {{-- CARD 3: Vendor Profile Summary --}}
            <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-6 dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex flex-col items-center text-center gap-4">
                    {{-- Avatar --}}
                    <div class="w-16 h-16 rounded-full bg-indigo-600/20 border-2 border-indigo-600/30 flex items-center justify-center text-indigo-300 text-2xl font-bold select-none">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    {{-- Name & email --}}
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-[#1A1D23] dark:text-white">{{ $user->name }}</h3>
                        <p class="text-xs font-mono text-[#6B7280] dark:text-slate-400">ID: {{ $user->portal_number }}</p>
                    </div>

                    {{-- Role badge --}}
                    <span class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[9px] font-bold uppercase tracking-widest rounded-full px-3 py-1">
                        VENDOR
                    </span>

                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Agent</p>
                </div>

                <div class="mt-5 pt-5 border-t border-[#E2E6EA] dark:border-white/[0.06] space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Files Processed</span>
                        <span class="text-sm font-bold text-white tabular-nums font-mono">{{ $filesProcessed }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Member Since</span>
                        <span class="text-xs font-mono text-slate-300">{{ $memberSince->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Role</span>
                        <span class="text-[10px] font-bold text-indigo-400 uppercase">{{ $user->role }}</span>
                    </div>
                </div>
            </div>

            {{-- CARD 4: Security Tips --}}
            <div class="bg-[#FAFBFC] border border-[#E2E6EA] rounded-2xl p-6 dark:bg-[#13151c] dark:border-white/[0.06]">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                    <h3 class="text-sm font-semibold text-[#1A1D23] dark:text-white">Security Tips</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <div class="w-5 h-5 bg-emerald-500/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-xs text-[#6B7280] dark:text-slate-400">Your account is secured by Telegram OTP. No password is stored.</p>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-5 h-5 bg-emerald-500/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-xs text-[#6B7280] dark:text-slate-400">Never share your login OTP code with anyone.</p>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-5 h-5 bg-emerald-500/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-xs text-[#6B7280] dark:text-slate-400">If you lose access to Telegram, contact your admin for a re-invite.</p>
                    </li>
                </ul>
            </div>

        </div>{{-- /right column --}}
    </div>

    {{-- ===== DANGER ZONE ===== --}}
    <div class="border border-red-500/20 bg-red-500/[0.04] rounded-2xl p-6">
        <div class="flex items-start justify-between gap-6">
            <div class="space-y-1.5">
                <h3 class="text-sm font-semibold text-red-400">Danger Zone — Delete Account</h3>
                <p class="text-xs text-red-400/70 max-w-xl">
                    Once deleted, all your orders and files will be permanently removed.
                    This action cannot be undone.
                </p>
            </div>
            <button
                onclick="document.getElementById('vendor-delete-modal').classList.remove('hidden')"
                class="flex-shrink-0 bg-red-500/10 hover:bg-red-500 border border-red-500/20 text-red-400 hover:text-white text-[10px] font-bold uppercase tracking-widest px-4 py-2 rounded-xl transition-all">
                Delete Account
            </button>
        </div>
    </div>

    {{-- Delete Account Modal --}}
    <div id="vendor-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-[#13151c] border border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center text-red-400 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Confirm Deletion</h3>
                    <p class="text-[11px] text-slate-500">This action is permanent and cannot be reversed.</p>
                </div>
            </div>

            <p class="text-xs text-slate-400">
                To delete your account, contact your admin directly.
            </p>

            <div class="pt-2">
                <button
                    type="button"
                    onclick="document.getElementById('vendor-delete-modal').classList.add('hidden')"
                    class="w-full py-2.5 rounded-xl border border-white/[0.08] text-xs font-semibold text-slate-400 hover:bg-white/[0.04] transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>


</x-vendor-layout>
