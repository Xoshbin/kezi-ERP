<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-4">{{ $this->getHeading() }}</h1>

    <div class="space-y-6">
        {{-- Filters Form --}}
        {{ $this->form }}

        {{-- Summary Cards --}}
        @if($reportData)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                {{-- Critical Items --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-danger-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.reorder.summary.critical') }}
                            </p>
                            <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                                {{ count($reordersByStatus['critical']) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Low Stock Items --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-circle class="w-8 h-8 text-warning-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.reorder.summary.low_stock') }}
                            </p>
                            <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                                {{ count($reordersByStatus['low']) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Suggested Orders --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-information-circle class="w-8 h-8 text-info-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.reorder.summary.suggested') }}
                            </p>
                            <p class="text-2xl font-bold text-info-600 dark:text-info-400">
                                {{ count($reordersByStatus['suggested']) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Overstock Items --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-arrow-trending-up class="w-8 h-8 text-purple-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.reorder.summary.overstock') }}
                            </p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ count($reordersByStatus['overstock']) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Total Suggested Value --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-banknotes class="w-8 h-8 text-primary-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.reorder.summary.suggested_value') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $totalSuggestedValue->formatTo(app()->getLocale()) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        @endif

        {{-- Critical Items Alert --}}
        @if($reportData && count($reordersByStatus['critical']) > 0)
            <x-filament::card>
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                        <div>
                            <h4 class="text-lg font-semibold text-red-900 dark:text-red-100">
                                {{ __('inventory::inventory_reports.reorder.alerts.critical_title') }}
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                {{ __('inventory::inventory_reports.reorder.alerts.critical_description', ['count' => count($reordersByStatus['critical'])]) }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Reorder Status Table --}}
        @if($reportData && !empty($reportData['products']))
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory::inventory_reports.reorder.table.title') }}
                    </h3>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.product') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.location') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.current_quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.min_quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.max_quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.suggested_quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.status') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory::inventory_reports.reorder.table.estimated_cost') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($reportData['products'] as $product)
                                    <tr class="
                                        @if($product['reorder_status'] === 'critical') bg-red-50 dark:bg-red-900/10
                                        @elseif($product['reorder_status'] === 'low') bg-yellow-50 dark:bg-yellow-900/10
                                        @endif">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $product['product_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $product['location_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($product['current_quantity'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ number_format($product['min_quantity'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ number_format($product['max_quantity'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            @if($product['suggested_quantity'] > 0)
                                                <span class="font-semibold">{{ number_format($product['suggested_quantity'], 2) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($product['reorder_status'] === 'critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @elseif($product['reorder_status'] === 'low') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($product['reorder_status'] === 'suggested') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($product['reorder_status'] === 'overstock') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                                @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @endif">
                                                @if($product['reorder_status'] === 'critical')
                                                    <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                                                @elseif($product['reorder_status'] === 'low')
                                                    <x-heroicon-o-exclamation-circle class="w-3 h-3 mr-1" />
                                                @elseif($product['reorder_status'] === 'suggested')
                                                    <x-heroicon-o-information-circle class="w-3 h-3 mr-1" />
                                                @elseif($product['reorder_status'] === 'overstock')
                                                    <x-heroicon-o-arrow-trending-up class="w-3 h-3 mr-1" />
                                                @else
                                                    <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                                @endif
                                                {{ ucfirst($product['reorder_status']) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            @if($product['suggested_quantity'] > 0)
                                                {{ $product['unit_cost']->multipliedBy($product['suggested_quantity'])->formatTo(app()->getLocale()) }}
                                            @else
                                                -
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

        {{-- No Data State --}}
        @if(!$reportData || empty($reportData['products']))
            <x-filament::card>
                <div class="text-center py-8">
                    <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('inventory::inventory_reports.reorder.no_data') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('inventory::inventory_reports.reorder.no_data_description') }}
                    </p>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
