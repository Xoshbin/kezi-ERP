<div class="space-y-4">
    @if(empty($costLayers))
        <div class="text-center py-8">
            <x-heroicon-o-cube class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                {{ __('inventory_reports.valuation.cost_layers_modal.no_layers') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('inventory_reports.valuation.cost_layers_modal.no_layers_description') }}
            </p>
        </div>
    @else
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('inventory_reports.valuation.cost_layers_modal.purchase_date') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('inventory_reports.valuation.cost_layers_modal.quantity') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('inventory_reports.valuation.cost_layers_modal.cost_per_unit') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('inventory_reports.valuation.cost_layers_modal.total_value') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($costLayers as $layer)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $layer['purchase_date']->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ number_format($layer['quantity'], 4) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $layer['cost_per_unit']->formatTo(app()->getLocale()) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $layer['total_value']->formatTo(app()->getLocale()) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="row" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('inventory_reports.valuation.cost_layers_modal.total') }}
                        </th>
                        <td class="px-6 py-3 text-left text-xs font-medium text-gray-900 dark:text-white">
                            {{ number_format(collect($costLayers)->sum('quantity'), 4) }}
                        </td>
                        <td class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ __('inventory_reports.valuation.cost_layers_modal.weighted_avg') }}
                        </td>
                        <td class="px-6 py-3 text-left text-xs font-medium text-gray-900 dark:text-white">
                            @php
                                $totalValue = collect($costLayers)->reduce(function ($carry, $layer) {
                                    return $carry ? $carry->plus($layer['total_value']) : $layer['total_value'];
                                });
                            @endphp
                            {{ $totalValue ? $totalValue->formatTo(app()->getLocale()) : '0.00' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
