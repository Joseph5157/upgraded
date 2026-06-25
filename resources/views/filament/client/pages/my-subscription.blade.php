<x-filament-panels::page>
    {{-- Credit Overview Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan Status</p>
                <p class="mt-1 text-xl font-bold {{ $this->getPlanStatus() === 'Active' ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    {{ $this->getPlanStatus() }}
                </p>
                @if($this->getPlanExpiry())
                    <p class="mt-1 text-xs text-gray-400">Expires: {{ $this->getPlanExpiry() }}</p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Credits Remaining</p>
                <p class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">
                    {{ $this->getCreditsRemaining() }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Files Processed</p>
                <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">
                    {{ $this->getCreditsUsed() }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Per File</p>
                <p class="mt-1 text-2xl font-bold text-gray-700 dark:text-gray-300">
                    {{ $this->getRatePerFile() }}
                </p>
                @if($lastPayment = $this->getLastPayment())
                    <p class="mt-1 text-xs text-gray-400">
                        Last: {{ $lastPayment['credits'] }} credits on {{ $lastPayment['date'] }}
                    </p>
                @endif
            </div>
        </x-filament::section>
    </div>

    {{-- Need More Credits? --}}
    <x-filament::section class="mb-6">
        <div class="flex items-center gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0" />
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Need more credits? Please contact your admin to add credits to your account.
            </p>
        </div>
    </x-filament::section>

    {{-- Tab Switcher --}}
    <div class="flex gap-2 mb-4">
        <x-filament::button
            :color="$this->activeTab === 'payments' ? 'primary' : 'gray'"
            size="sm"
            wire:click="$set('activeTab', 'payments')"
        >
            Payment History
        </x-filament::button>
        <x-filament::button
            :color="$this->activeTab === 'refunds' ? 'primary' : 'gray'"
            size="sm"
            wire:click="$set('activeTab', 'refunds')"
        >
            Refund History
        </x-filament::button>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            {{ $this->activeTab === 'payments' ? 'Payment History' : 'Refund History' }}
        </x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
