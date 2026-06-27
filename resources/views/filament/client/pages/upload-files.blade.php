<x-filament-panels::page>

    {{-- Credit balance strip --}}
    <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <span style="color:rgba(255,255,255,0.85);font-size:13px;">Available Credits</span>
        <span style="color:#fff;font-size:18px;font-weight:700;">{{ $creditBalance }}</span>
    </div>

    <form wire:submit="upload">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button
                type="submit"
                size="lg"
                class="w-full"
                wire:loading.attr="disabled"
                wire:target="upload,file"
            >
                <span wire:loading.remove wire:target="upload">Upload File</span>
                <span wire:loading wire:target="upload">Uploading...</span>
            </x-filament::button>
        </div>
    </form>

</x-filament-panels::page>
