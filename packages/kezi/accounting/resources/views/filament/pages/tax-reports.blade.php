<x-filament-panels::page>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model="report_type">
                    <option value="">Select Report Type</option>
                    @foreach($this->getGenerators() as $class => $label)
                        <option value="{{ $class }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model="start_date" />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model="end_date" />
            </x-filament::input.wrapper>
            
            <div class="flex items-end">
                <x-filament::button wire:click="generate">
                    Generate
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    @if($report_data)
        <x-filament::section>
            <h2 class="text-xl font-bold mb-4">{{ $report_data['report_name'] ?? 'Report' }}</h2>
            <p class="text-sm text-gray-500 mb-6">Period: {{ $report_data['period'] ?? '' }} ({{ $report_data['currency'] ?? '' }})</p>

            @if(isset($report_data['boxes']))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($report_data['boxes'] as $boxId => $box)
                        <div class="p-4 border rounded bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Box {{ $boxId }}</span>
                                <span class="text-sm text-gray-500">{{ $box['label'] }}</span>
                            </div>
                            <div class="text-2xl font-bold">
                                {{ number_format($box['value'], 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4">
                    <pre>{{ json_encode($report_data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
