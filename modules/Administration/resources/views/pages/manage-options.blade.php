<x-filament-panels::page xmlns:x-filament="http://www.w3.org/1999/html">
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit">
            Submit
        </x-filament::uton>
    </form>
</x-filament-panels::page>
