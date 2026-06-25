<x-filament-panels::page>
    {{-- Contact Admin Info --}}
    <x-filament::section class="mb-6">
        <div class="flex items-center gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0" />
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Self-service top-up is no longer available. Please contact your admin to add credits to your account.
            </p>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Topup Request History</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
