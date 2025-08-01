<x-filament-panels::page>
    {{--
        Here, we call our Livewire component and pass the page's public $record
        property into the component's own $record property.
    --}}
    <livewire:bank-reconciliation-matcher :record="$this->record" />
</x-filament-panels::page>
