<x-filament-panels::page>
    <form wire:submit="upload">
        {{ $this->form }}

        <div class="mt-6">
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
