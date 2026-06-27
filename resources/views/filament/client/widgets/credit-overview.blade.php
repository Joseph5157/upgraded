<x-filament-widgets::widget>
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Credit Balance</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $balance }}</p>
            <p class="mt-1 text-xs text-blue-500">Available credits</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Files Submitted</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $total }}</p>
            <p class="mt-1 text-xs text-indigo-500">Total orders</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">In Progress</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $inProgress }}</p>
            <p class="mt-1 text-xs text-amber-500">Being processed</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Completed</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $completed }}</p>
            <p class="mt-1 text-xs text-green-500">Delivered</p>
        </div>
    </div>
</x-filament-widgets::widget>
