{{-- Brand --}}
<div class="px-5 py-5 border-b border-white/[0.06] flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-indigo-600 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0">T</div>
        <div>
            <p class="text-sm font-bold text-white leading-none">PlagExpert</p>
            <p class="text-[9px] text-slate-500 mt-0.5 uppercase tracking-widest">Agent Portal</p>
        </div>
    </div>
    {{-- Close button (mobile drawer only) --}}
    <button onclick="closeSidebar()"
            class="md:hidden w-7 h-7 bg-white/[0.04] border border-white/[0.06] rounded-lg flex items-center justify-center text-slate-500 hover:text-white transition-colors"
            aria-label="Close menu">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

{{-- Nav --}}
<nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
    <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest px-3 mb-3">Navigation</p>

    <a href="{{ route('dashboard') }}"
        class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg class="sidebar-icon w-4 h-4 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-slate-500' }}"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Dashboard
    </a>

    <a href="{{ route('dashboard') }}#workspace"
        class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white">
        <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        Workspace
    </a>

    <a href="{{ route('dashboard') }}#files"
        class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white">
        <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        Files
    </a>

    <a href="{{ route('dashboard') }}#history"
        class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white">
        <svg class="sidebar-icon w-4 h-4 flex-shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        History
    </a>

    <a href="{{ route('vendor.earnings') }}"
        class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white {{ request()->routeIs('vendor.earnings') ? 'active' : '' }}">
        <svg class="sidebar-icon w-4 h-4 flex-shrink-0 {{ request()->routeIs('vendor.earnings') ? 'text-indigo-400' : 'text-slate-500' }}"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Earnings
    </a>

    <div class="pt-4 mt-2 border-t border-white/[0.06]">
        <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest px-3 mb-3">Account</p>
        <a href="{{ route('profile.edit') }}"
            class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <svg class="sidebar-icon w-4 h-4 flex-shrink-0 {{ request()->routeIs('profile.*') ? 'text-indigo-400' : 'text-slate-500' }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
        </a>
    </div>
</nav>

{{-- User at bottom --}}
<div class="px-4 py-4 border-t border-white/[0.06]">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-indigo-600/20 rounded-xl flex items-center justify-center text-indigo-300 text-xs font-bold border border-indigo-600/20 flex-shrink-0">
            {{ substr(auth()->user()->name, 0, 1) }}
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
            <p class="text-[9px] text-slate-500 truncate">Agent</p>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto flex-shrink-0">
            @csrf
            <button type="submit" class="text-slate-600 hover:text-red-400 transition-colors" title="Log out">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </button>
        </form>
    </div>
</div>
