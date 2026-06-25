<x-filament-panels::page>
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Credits Remaining</p>
                <p class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">
                    {{ number_format($this->getTotalCreditsRemaining()) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Received</p>
                <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
                    ₹{{ number_format($this->getTotalReceived(), 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Credits Used</p>
                <p class="mt-1 text-2xl font-bold text-danger-600 dark:text-danger-400">
                    {{ number_format($this->getTotalCreditsUsed()) }}
                </p>
            </div>
        </x-filament::section>
    </div>

    {{-- Client Balances Table --}}
    <x-filament::section>
        <x-slot name="heading">Client Balances</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
