<button
    onclick="toggleTheme()"
    id="theme-toggle"
    class="flex items-center gap-2 px-3 py-2 rounded-xl border border-[#E2E6EA]
           bg-[#FAFBFC] hover:bg-[#ECEEF2] transition-all
           dark:bg-[#1e2030] dark:border-[#2d3148] dark:hover:bg-[#252840]"
    title="Toggle dark mode">
    <i data-lucide="sun"
       id="icon-sun"
       class="w-4 h-4 text-amber-500 dark:hidden"></i>
    <i data-lucide="moon"
       id="icon-moon"
       class="w-4 h-4 text-indigo-400 hidden dark:block"></i>
    <span class="text-[10px] font-bold uppercase tracking-widest
                 text-gray-500 dark:text-slate-400">
        <span class="dark:hidden">Light</span>
        <span class="hidden dark:inline">Dark</span>
    </span>
</button>

<script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (window.lucide) lucide.createIcons();
    }
</script>
