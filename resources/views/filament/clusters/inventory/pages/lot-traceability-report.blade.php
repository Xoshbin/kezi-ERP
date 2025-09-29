<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters Form --}}
        {{ $this->form }}

        {{-- Lot Summary --}}
        @if($reportData)
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory_reports.lot_trace.summary.title') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                        {{-- Lot Code --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.summary.lot_code') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $reportData['lot_code'] }}
                            </p>
                        </div>

                        {{-- Product --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.summary.product') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $reportData['product_name'] }}
                            </p>
                        </div>

                        {{-- Expiration Date --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.summary.expiration_date') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                @if($reportData['expiration_date'])
                                    {{ \Carbon\Carbon::parse($reportData['expiration_date'])->format('M d, Y') }}
                                @else
                                    {{ __('inventory_reports.lot_trace.no_expiration') }}
                                @endif
                            </p>
                        </div>

                        {{-- Current Quantity --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.summary.current_quantity') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($reportData['current_quantity'], 4) }}
                            </p>
                        </div>

                        {{-- Total Value --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.summary.total_value') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $reportData['total_value']->formatTo(app()->getLocale()) }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Movement Summary Cards --}}
        @if($reportData)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                {{-- Incoming Movements --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-arrow-down-tray class="w-8 h-8 text-success-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.movements.incoming') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($totalIncoming, 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ count($movementsByType['incoming']) }} {{ __('inventory_reports.lot_trace.movements.count') }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Outgoing Movements --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-arrow-up-tray class="w-8 h-8 text-danger-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.movements.outgoing') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($totalOutgoing, 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ count($movementsByType['outgoing']) }} {{ __('inventory_reports.lot_trace.movements.count') }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Internal Movements --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-arrow-right class="w-8 h-8 text-info-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.lot_trace.movements.internal') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($totalInternal, 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ count($movementsByType['internal']) }} {{ __('inventory_reports.lot_trace.movements.count') }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        @endif

        {{-- Movement History --}}
        @if($reportData && !empty($reportData['movements']))
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory_reports.lot_trace.movements.title') }}
                    </h3>
                    
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.date') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.type') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.from_location') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.to_location') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.reference') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.valuation_amount') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.lot_trace.movements.journal_entry') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($reportData['movements'] as $movement)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($movement['move_date'])->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($movement['move_type']->value === 'incoming') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($movement['move_type']->value === 'outgoing') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @endif">
                                                @if($movement['move_type']->value === 'incoming')
                                                    <x-heroicon-o-arrow-down-tray class="w-3 h-3 mr-1" />
                                                @elseif($movement['move_type']->value === 'outgoing')
                                                    <x-heroicon-o-arrow-up-tray class="w-3 h-3 mr-1" />
                                                @else
                                                    <x-heroicon-o-arrow-right class="w-3 h-3 mr-1" />
                                                @endif
                                                {{ ucfirst($movement['move_type']->value) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($movement['quantity'], 4) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $movement['from_location'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $movement['to_location'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $movement['reference'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            @if($movement['valuation_amount'])
                                                {{ $movement['valuation_amount']->formatTo(app()->getLocale()) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($movement['journal_entry_id'])
                                                <a href="#" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                                    <x-heroicon-o-document-text class="w-4 h-4 inline mr-1" />
                                                    JE-{{ $movement['journal_entry_id'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- No Selection State --}}
        @if(!$selectedProduct || !$selectedLot)
            <x-filament::card>
                <div class="text-center py-8">
                    <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('inventory_reports.lot_trace.no_selection') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('inventory_reports.lot_trace.no_selection_description') }}
                    </p>
                </div>
            </x-filament::card>
        @endif

        {{-- No Data State --}}
        @if($selectedProduct && $selectedLot && $reportData && empty($reportData['movements']))
            <x-filament::card>
                <div class="text-center py-8">
                    <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('inventory_reports.lot_trace.no_movements') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('inventory_reports.lot_trace.no_movements_description') }}
                    </p>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
