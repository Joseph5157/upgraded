<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Refund Requests</x-slot>
        <x-slot name="description">
            View your refund requests and submit new ones for eligible orders.
        </x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
