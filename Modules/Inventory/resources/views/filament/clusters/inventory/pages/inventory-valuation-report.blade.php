<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-4">{{ $this->getHeading() }}</h1>

    <div class="space-y-6">
        {{-- Filters Form --}}
        {{ $this->form }}

        {{-- Summary Cards --}}
        @if($reportData)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- Total Value --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-banknotes class="w-8 h-8 text-primary-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.summary.total_value') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $reportData['total_value']->formatTo(app()->getLocale()) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Total Quantity --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-cube class="w-8 h-8 text-success-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.summary.total_quantity') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($reportData['total_quantity'], 2) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Product Count --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-squares-2x2 class="w-8 h-8 text-info-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.summary.product_count') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ count($reportData['by_product']) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- As of Date --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-calendar class="w-8 h-8 text-warning-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.summary.as_of_date') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $reportData['as_of_date']->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        @endif

        {{-- GL Reconciliation --}}
        @if($reconciliationData)
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory_reports.valuation.reconciliation.title') }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.reconciliation.gl_balance') }}
                            </p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $reconciliationData['inventory_account_balance']->formatTo(app()->getLocale()) }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.reconciliation.calculated_value') }}
                            </p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $reconciliationData['calculated_valuation']->formatTo(app()->getLocale()) }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.valuation.reconciliation.difference') }}
                            </p>
                            <p class="text-xl font-bold {{ $reconciliationData['is_reconciled'] ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $reconciliationData['reconciliation_difference']->formatTo(app()->getLocale()) }}
                            </p>
                            @if($reconciliationData['is_reconciled'])
                                <p class="text-sm text-success-600">
                                    <x-heroicon-o-check-circle class="inline w-4 h-4" />
                                    {{ __('inventory_reports.valuation.reconciliation.reconciled') }}
                                </p>
                            @else
                                <p class="text-sm text-danger-600">
                                    <x-heroicon-o-exclamation-triangle class="inline w-4 h-4" />
                                    {{ __('inventory_reports.valuation.reconciliation.not_reconciled') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Product Details Table --}}
        <x-filament::card>
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('inventory_reports.valuation.table.title') }}
                </h3>

                @if($this->getProductDetails())
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.valuation.table.product') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.valuation.table.valuation_method') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.valuation.table.quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.valuation.table.unit_cost') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.valuation.table.total_value') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('inventory_reports.valuation.table.cost_layers') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->getProductDetails() as $product)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $product['product_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($product['valuation_method'] === 'AVCO') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($product['valuation_method'] === 'FIFO') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @endif">
                                                {{ $product['valuation_method'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($product['quantity'], 4) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($product['unit_cost'], 2) }} {{ $reportData['currency'] ?? 'IQD' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $product['total_value']->formatTo(app()->getLocale()) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $product['cost_layers_count'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-cube class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('inventory_reports.valuation.no_data') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('inventory_reports.valuation.no_data_description') }}
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
