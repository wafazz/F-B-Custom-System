<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-2">
            @foreach ($this->getCachedFormActions() ?? [] as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
