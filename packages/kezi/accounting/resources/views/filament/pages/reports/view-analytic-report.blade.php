<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if($reportData)
            <x-filament::section>
                <div class="p-4 text-center text-gray-500">
                    {{-- Placeholder for actual report content --}}
                    <p>{{ __('accounting::reports.report_under_construction') }}</p>
                    <p class="text-sm mt-2">{{ $reportData['message'] ?? '' }}</p>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="p-4 text-center text-gray-500">
                    {{ __('accounting::reports.click_generate_to_view') }}
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
