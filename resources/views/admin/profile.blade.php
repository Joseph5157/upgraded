<x-admin-layout>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight dark:text-white">Operator Settings</h1>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mt-1 dark:text-slate-500">
                Manage your credentials and account security
            </p>
        </div>
    </div>

    {{-- Flash Banners --}}
    @if(session('status') === 'profile-updated')
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> Profile updated successfully.
        </div>
    @endif
    @if(session('status') === 'password-updated')
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> Password updated successfully.
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
                <p class="text-sm text-gray-400 font-mono">{{ $user->email }}</p>
            </div>
            <span class="bg-red-500/10 text-red-500 border border-red-500/20 text-[9px] font-bold uppercase tracking-widest rounded-full px-3 py-1">
                SYSTEM_ROOT
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

        {{-- CARD 2: Update Profile --}}
        <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl p-8 dark:bg-[#0a0a0c] dark:border-white/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[#4F6EF7]/10 rounded-xl flex items-center justify-center text-[#4F6EF7]">
                    <i data-lucide="user-pen" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Update Profile</h3>
                    <p class="text-[11px] text-gray-400">Change your name or email address.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 dark:text-slate-500">Full Name</label>
                    <input
                        id="name" name="name" type="text"
                        value="{{ old('name', $user->name) }}"
                        required autocomplete="name"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200 dark:focus:border-[#4F6EF7]"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 dark:text-slate-500">Email Address</label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email', $user->email) }}"
                        required autocomplete="username"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200 dark:focus:border-[#4F6EF7]"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full bg-[#4F6EF7] hover:bg-[#3B5BDB] text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl transition-colors mt-2">
                    Save Changes
                </button>
            </form>
        </div>

        {{-- CARD 3: Change Password --}}
        <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl p-8 dark:bg-[#0a0a0c] dark:border-white/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[#4F6EF7]/10 rounded-xl flex items-center justify-center text-[#4F6EF7]">
                    <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Change Password</h3>
                    <p class="text-[11px] text-gray-400">Use a strong password of at least 8 characters.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 dark:text-slate-500">Current Password</label>
                    <input
                        id="current_password" name="current_password" type="password"
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200 dark:focus:border-[#4F6EF7]"
                    >
                    @error('current_password', 'updatePassword')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 dark:text-slate-500">New Password</label>
                    <input
                        id="admin_new_password" name="password" type="password"
                        autocomplete="new-password"
                        oninput="updateStrength(this.value)"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200 dark:focus:border-[#4F6EF7]"
                    >
                    {{-- Password strength indicator --}}
                    <div class="mt-2 space-y-1">
                        <div class="w-full h-1 rounded-full bg-[#E2E6EA] dark:bg-white/10 overflow-hidden">
                            <div id="strength-bar" class="h-full rounded-full transition-all duration-300 w-0"></div>
                        </div>
                        <p id="strength-label" class="text-[10px] font-bold uppercase tracking-widest text-gray-400"></p>
                    </div>
                    @error('password', 'updatePassword')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 dark:text-slate-500">Confirm New Password</label>
                    <input
                        id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200 dark:focus:border-[#4F6EF7]"
                    >
                    @error('password_confirmation', 'updatePassword')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full bg-[#4F6EF7] hover:bg-[#3B5BDB] text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl transition-colors mt-2">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    {{-- CARD 4: Danger Zone --}}
    <div class="border border-red-500/20 bg-red-500/5 rounded-2xl p-8 dark:border-red-500/20 dark:bg-red-500/5">
        <div class="flex items-start justify-between gap-6">
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-red-500 uppercase tracking-wide">Danger Zone &mdash; Delete Account</h3>
                <p class="text-xs text-red-400/80 max-w-xl">
                    Once deleted, all your orders and files will be permanently removed.
                    This action cannot be undone and will immediately terminate your operator access.
                </p>
            </div>
            <button
                onclick="document.getElementById('admin-delete-modal').classList.remove('hidden')"
                class="flex-shrink-0 bg-red-500 hover:bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest px-5 py-2.5 rounded-xl transition-colors">
                Delete Account
            </button>
        </div>
    </div>

    {{-- Delete Account Modal --}}
    <div id="admin-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-[#FAFBFC] dark:bg-[#0a0a0c] border border-[#E2E6EA] dark:border-white/5 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center text-red-500">
                    <i data-lucide="triangle-alert" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Confirm Deletion</h3>
                    <p class="text-[11px] text-gray-400">This action is permanent and cannot be reversed.</p>
                </div>
            </div>

            <p class="text-xs text-gray-500 dark:text-slate-400">
                Enter your password to permanently delete this operator account and all associated data.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('delete')

                <div>
                    <label for="admin_delete_password" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 dark:text-slate-500">Your Password</label>
                    <input
                        id="admin_delete_password" name="password" type="password"
                        placeholder="Enter your current password"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:border-red-400 transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200"
                    >
                    @error('password', 'userDeletion')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="button"
                        onclick="document.getElementById('admin-delete-modal').classList.add('hidden')"
                        class="flex-1 py-2.5 rounded-xl border border-[#E2E6EA] dark:border-white/5 text-xs font-semibold text-gray-600 dark:text-slate-400 hover:bg-[#F0F2F5] dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold uppercase tracking-widest transition-colors">
                        Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateStrength(value) {
            const bar   = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');
            const len   = value.length;

            if (len === 0) {
                bar.style.width = '0%';
                bar.className   = 'h-full rounded-full transition-all duration-300 w-0';
                label.textContent = '';
                return;
            }

            if (len < 8) {
                bar.style.width   = '33%';
                bar.className     = 'h-full rounded-full transition-all duration-300 bg-red-500';
                label.textContent = 'Weak';
                label.className   = 'text-[10px] font-bold uppercase tracking-widest text-red-500';
            } else if (len <= 12) {
                bar.style.width   = '66%';
                bar.className     = 'h-full rounded-full transition-all duration-300 bg-amber-400';
                label.textContent = 'Medium';
                label.className   = 'text-[10px] font-bold uppercase tracking-widest text-amber-400';
            } else {
                bar.style.width   = '100%';
                bar.className     = 'h-full rounded-full transition-all duration-300 bg-green-500';
                label.textContent = 'Strong';
                label.className   = 'text-[10px] font-bold uppercase tracking-widest text-green-500';
            }
        }
    </script>

</x-admin-layout>
