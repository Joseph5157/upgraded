<x-filament-panels::page>
    {{-- Tab Switcher --}}
    <div class="flex gap-2 mb-4">
        <x-filament::button
            :color="$this->activeTab === 'clients' ? 'primary' : 'gray'"
            size="sm"
            wire:click="$set('activeTab', 'clients')"
        >
            Client Pricing
        </x-filament::button>
        <x-filament::button
            :color="$this->activeTab === 'vendors' ? 'primary' : 'gray'"
            size="sm"
            wire:click="$set('activeTab', 'vendors')"
        >
            Vendor Payout Rates
        </x-filament::button>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            {{ $this->activeTab === 'clients' ? 'Client Pricing' : 'Vendor Payout Rates' }}
        </x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
