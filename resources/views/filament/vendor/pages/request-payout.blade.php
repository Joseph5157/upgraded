<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        {{-- Approved Balance --}}
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Approved Balance</p>
                <p class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">
                    ₹{{ number_format($this->getApprovedBalance(), 2) }}
                </p>
            </div>
        </x-filament::section>

        {{-- Pending Earnings --}}
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Earnings</p>
                <p class="mt-1 text-2xl font-bold text-warning-600 dark:text-warning-400">
                    ₹{{ number_format($this->getPendingBalance(), 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-400">Awaiting admin approval</p>
            </div>
        </x-filament::section>

        {{-- Request Status --}}
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Request Status</p>
                @if($this->hasPendingRequest())
                    @php $pending = $this->getPendingRequest(); @endphp
                    <p class="mt-1 text-lg font-bold text-warning-600 dark:text-warning-400">Pending</p>
                    <p class="mt-1 text-xs text-gray-400">
                        ₹{{ number_format($pending->amount_requested, 2) }} requested on {{ $pending->created_at->format('d M Y') }}
                    </p>
                @else
                    <p class="mt-1 text-lg font-bold text-gray-400">No Active Request</p>
                    @if($this->getApprovedBalance() > 0)
                        <p class="mt-1 text-xs text-gray-400">You can request a payout above</p>
                    @else
                        <p class="mt-1 text-xs text-gray-400">Build up your approved balance first</p>
                    @endif
                @endif
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Payout Request History</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
