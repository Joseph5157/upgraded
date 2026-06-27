<x-filament-widgets::widget>
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending Earnings</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $pending }} ₹</p>
            <p class="mt-1 text-xs text-amber-500">Awaiting approval</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Approved Payable</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $approved }} ₹</p>
            <p class="mt-1 text-xs text-green-500">Ready to pay out</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Delivered Today</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $today }}</p>
            <p class="mt-1 text-xs text-blue-500">Today's completions</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Delivered</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $total }}</p>
            <p class="mt-1 text-xs text-gray-500">All time</p>
        </div>
    </div>
</x-filament-widgets::widget>
