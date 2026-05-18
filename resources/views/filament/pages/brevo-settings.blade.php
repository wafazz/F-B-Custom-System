<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Save settings
        </x-filament::button>
    </form>

    <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <h3 class="text-sm font-bold text-amber-900">Send a test email</h3>
        <p class="mt-1 text-xs text-amber-800/80">
            Saves the current form values and sends a one-off message via Brevo's HTTP API.
            Useful for confirming the key + verified sender domain before relying on it for receipts.
        </p>

        <div class="mt-3 flex flex-wrap items-end gap-2">
            <div class="flex-1 min-w-[220px]">
                <label class="text-xs font-semibold text-amber-900">Recipient</label>
                <input
                    type="email"
                    wire:model="testEmail"
                    placeholder="you@example.com"
                    class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                />
            </div>
            <x-filament::button color="warning" wire:click="sendTest" type="button">
                Send test
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
