<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-2">
            <x-filament::button type="submit">
                Save settings
            </x-filament::button>

            <x-filament::button color="gray" wire:click="testConnection" type="button">
                Test connection
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
