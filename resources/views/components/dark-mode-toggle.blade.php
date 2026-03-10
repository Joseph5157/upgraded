<button
    onclick="toggleTheme()"
    id="theme-toggle"
    class="w-full flex items-center gap-2 px-3 py-2 rounded-2xl border transition-all
           bg-[#1A1D23] border-[#2d3148] hover:bg-[#252840] text-white
           dark:bg-[#F0F2F5] dark:border-[#E2E6EA] dark:hover:bg-[#E8ECF0] dark:text-gray-700"
    title="Toggle dark mode">
    <i data-lucide="moon"
       id="icon-moon"
       class="w-4 h-4 text-indigo-400 dark:hidden"></i>
    <i data-lucide="sun"
       id="icon-sun"
       class="w-4 h-4 text-amber-500 hidden dark:block"></i>
    <span class="text-[10px] font-bold uppercase tracking-widest">
        <span class="dark:hidden">Dark</span>
        <span class="hidden dark:inline">Light</span>
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
