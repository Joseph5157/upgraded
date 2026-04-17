<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        // Force dark mode as default
        document.documentElement.classList.add('dark');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Settings — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .premium-card {
            background: #FAFBFC;
            border: 1px solid #E2E6EA;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        }
        .sidebar-link-active {
            background: #EEF2FF;
            color: #4F6EF7;
            border-right: 2px solid #4F6EF7;
        }
    </style>
</head>

<body class="h-screen flex bg-[#F0F2F5] text-[#111827] overflow-hidden overflow-x-hidden dark:bg-[#0f1117] dark:text-slate-300">

    <!-- Sidebar -->
    <aside class="hidden md:flex w-64 flex-shrink-0 h-full border-r border-[#E2E6EA] flex-col pt-8 bg-[#F7F8FA] dark:bg-[#0a0a0c] dark:border-[#1e2030]">
        <div class="px-8 mb-12">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="sparkles" class="w-5 h-5 text-[#1A1D23]"></i>
                </div>
                <span class="text-gray-900 font-bold text-lg tracking-tight dark:text-white">PlagExpert</span>
            </div>
        </div>

        <nav class="flex-1 space-y-2">
            <a href="{{ route('client.dashboard') }}"
                class="flex items-center gap-4 px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-[#ECEEF2] transition-all dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5">
                <i data-lucide="layout-grid" class="w-5 h-5"></i> Dashboard
            </a>
            <div class="flex items-center justify-between px-8 py-4 text-sm font-medium text-gray-500 cursor-not-allowed select-none dark:text-slate-600">
                <div class="flex items-center gap-4">
                    <i data-lucide="history" class="w-5 h-5"></i> Order History
                </div>
                <span class="text-[8px] font-black uppercase tracking-widest bg-[#EEF2FF] text-[#4F6EF7] border border-[#C7D2FE] px-1.5 py-0.5 rounded dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20">Soon</span>
            </div>
            <a href="{{ route('client.subscription') }}"
                class="flex items-center gap-4 px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-[#ECEEF2] transition-all dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5">
                <i data-lucide="credit-card" class="w-5 h-5"></i> Subscription
            </a>
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-3 px-8 py-4 text-sm bg-[#EEF2FF] text-[#4F6EF7] border-r-2 border-[#4F6EF7] font-semibold sidebar-link-active dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500">
                <i data-lucide="settings" class="w-5 h-5"></i> Settings
            </a>
        </nav>

        <div class="p-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 py-3 border border-[#E2E6EA] rounded-xl text-xs font-bold text-gray-500 hover:text-red-500 hover:bg-red-50 transition-all dark:border-[#1e2030] dark:text-slate-500 dark:hover:text-red-400 dark:hover:bg-red-500/10">
                    <i data-lucide="log-out" class="w-4 h-4"></i> SIGN OUT
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#F0F2F5] dark:bg-[#080810] w-full min-w-0">

        <!-- Top Bar -->
        <header class="h-20 border-b border-[#E2E6EA] flex items-center justify-between px-4 sm:px-10 bg-[#FAFBFC] sticky top-0 z-10 dark:bg-[#0a0a0c] dark:border-[#1e2030]">
            {{-- Mobile Menu Button --}}
            <button class="md:hidden w-8 h-8 flex items-center justify-center text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white mr-3" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Account Settings</h1>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">Manage your profile and security</p>
            </div>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-full flex items-center justify-center text-indigo-500 ring-4 ring-indigo-500/5">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold text-gray-800 dark:text-slate-200">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-gray-400">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Mobile Menu Dropdown --}}
        <div id="mobile-menu" class="hidden md:hidden bg-[#FAFBFC] dark:bg-[#0a0a0c] border-b border-[#E2E6EA] dark:border-[#1e2030]">
            <nav class="px-4 py-3 space-y-1">
                <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-400 hover:bg-[#ECEEF2] dark:hover:bg-white/5">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i> Dashboard
                </a>
                <a href="{{ route('client.subscription') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-400 hover:bg-[#ECEEF2] dark:hover:bg-white/5">
                    <i data-lucide="credit-card" class="w-4 h-4"></i> Subscription
                </a>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-[#EEF2FF] text-[#4F6EF7] dark:bg-indigo-500/10 dark:text-indigo-400">
                    <i data-lucide="settings" class="w-4 h-4"></i> Settings
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2 pt-2 border-t border-[#E2E6EA] dark:border-[#1e2030]">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 border border-[#E2E6EA] dark:border-[#1e2030]">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                    </button>
                </form>
            </nav>
        </div>

        <div class="p-10 pb-24 md:pb-0 max-w-5xl mx-auto space-y-8">

            {{-- Success Flashes --}}
            @if(session('status') === 'profile-updated')
                <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-600 text-sm font-semibold dark:text-green-400">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    Profile updated successfully.
                </div>
            @endif
            @if(session('status') === 'password-updated')
                <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-600 text-sm font-semibold dark:text-green-400">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    Password updated successfully.
                </div>
            @endif

            {{-- Two-column cards --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                {{-- CARD 1: Update Profile Information --}}
                <div class="premium-card rounded-2xl p-8 dark:bg-[#0a0a0c] dark:border-[#1e2030]">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500">
                            <i data-lucide="user-pen" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Profile Information</h2>
                            <p class="text-[11px] text-gray-400">Update your name and email address.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                        @csrf
                        @method('patch')

                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">Full Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                required
                                autocomplete="name"
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-[#1e2030] dark:text-slate-200 dark:focus:border-indigo-500"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">Email Address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                autocomplete="username"
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-[#1e2030] dark:text-slate-200 dark:focus:border-indigo-500"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full bg-[#4F6EF7] hover:bg-[#3B5BDB] text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                {{-- CARD 2: Change Password --}}
                <div class="premium-card rounded-2xl p-8 dark:bg-[#0a0a0c] dark:border-[#1e2030]">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500">
                            <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Change Password</h2>
                            <p class="text-[11px] text-gray-400">Use a strong password of at least 8 characters.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                        @csrf
                        @method('put')

                        {{-- Current Password --}}
                        <div>
                            <label for="current_password" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">Current Password</label>
                            <input
                                id="current_password"
                                name="current_password"
                                type="password"
                                autocomplete="current-password"
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-[#1e2030] dark:text-slate-200 dark:focus:border-indigo-500"
                            >
                            @error('current_password', 'updatePassword')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div>
                            <label for="password" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">New Password</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-[#1e2030] dark:text-slate-200 dark:focus:border-indigo-500"
                            >
                            @error('password', 'updatePassword')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">Confirm New Password</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#4F6EF7]/30 focus:border-[#4F6EF7] transition-all dark:bg-[#111827] dark:border-[#1e2030] dark:text-slate-200 dark:focus:border-indigo-500"
                            >
                            @error('password_confirmation', 'updatePassword')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full bg-[#4F6EF7] hover:bg-[#3B5BDB] text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- CARD 3: Danger Zone --}}
            <div class="border border-red-200 bg-red-50 rounded-2xl p-8 dark:bg-red-500/5 dark:border-red-500/20">
                <div class="flex items-start justify-between gap-6">
                    <div class="space-y-2">
                        <h2 class="text-sm font-bold text-red-600 dark:text-red-400">Delete Account</h2>
                        <p class="text-xs text-red-500/80 dark:text-red-400/70 max-w-md">
                            Once deleted, all your orders and files will be permanently removed. This cannot be undone.
                        </p>
                    </div>
                    <button
                        onclick="document.getElementById('delete-account-modal').classList.remove('hidden')"
                        class="flex-shrink-0 bg-red-500 hover:bg-red-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition-colors">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Bottom Nav --}}
        <nav class="fixed bottom-0 left-0 right-0 z-30 md:hidden bg-[#09090c] border-t border-white/[0.06]" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="flex items-center">

                {{-- Home --}}
                <a href="{{ route('client.dashboard') }}"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Home</span>
                </a>

                {{-- Orders --}}
                <button onclick="showComingSoon()"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-600">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Orders</span>
                </button>

                {{-- Credits --}}
                <a href="{{ route('client.subscription') }}"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Credits</span>
                </a>

                {{-- Profile --}}
                <a href="{{ route('profile.edit') }}"
                   class="flex-1 flex flex-col items-center gap-1 py-3 text-indigo-400">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Profile</span>
                </a>

            </div>
        </nav>

        {{-- Coming Soon Toast --}}
        <div id="coming-soon-toast"
             class="fixed bottom-24 left-1/2 -translate-x-1/2 z-50 hidden md:hidden bg-[#1e1e2e] border border-indigo-500/20 text-indigo-300 text-xs font-semibold px-5 py-3 rounded-2xl shadow-xl">
            Order History coming soon
        </div>
    </main>

    {{-- Delete Account Confirmation Modal --}}
    <div id="delete-account-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0f1117] border border-[#E2E6EA] dark:border-[#1e2030] rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-500/10 rounded-xl flex items-center justify-center text-red-500">
                    <i data-lucide="triangle-alert" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Confirm Account Deletion</h3>
                    <p class="text-[11px] text-gray-400">This action is permanent and cannot be reversed.</p>
                </div>
            </div>

            <p class="text-xs text-gray-500 dark:text-slate-400">
                Please enter your password to confirm you want to permanently delete your account and all associated data.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('delete')

                <div>
                    <label for="delete_password" class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">Your Password</label>
                    <input
                        id="delete_password"
                        name="password"
                        type="password"
                        placeholder="Enter your current password"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:border-red-400 transition-all dark:bg-[#111827] dark:border-[#1e2030] dark:text-slate-200"
                    >
                    @error('password', 'userDeletion')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="button"
                        onclick="document.getElementById('delete-account-modal').classList.add('hidden')"
                        class="flex-1 py-2.5 rounded-xl border border-[#E2E6EA] text-xs font-semibold text-gray-600 hover:bg-[#F0F2F5] transition-colors dark:border-[#1e2030] dark:text-slate-400 dark:hover:bg-white/5">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition-colors">
                        Yes, Delete My Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function showComingSoon() {
            const toast = document.getElementById('coming-soon-toast');
            if (!toast) return;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2500);
        }
    </script>
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>

</html>
