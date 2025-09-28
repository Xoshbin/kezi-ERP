@php use Carbon\Carbon; @endphp
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
                            <x-heroicon-o-banknotes class="w-8 h-8 text-primary-500"/>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.aging.summary.total_value') }}
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
                            <x-heroicon-o-cube class="w-8 h-8 text-success-500"/>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.aging.summary.total_quantity') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($reportData['total_quantity'], 2) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Average Age --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-clock class="w-8 h-8 text-warning-500"/>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.aging.summary.average_age') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($averageAge, 0) }} {{ __('inventory_reports.aging.days') }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Expiring Soon --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-danger-500"/>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory_reports.aging.summary.expiring_soon') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ count($expiringLots) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        @endif

        {{-- Age Distribution --}}
        @if($bucketData)
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory_reports.aging.buckets.title') }}
                    </h3>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.buckets.age_range') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.buckets.quantity') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.buckets.value') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.buckets.percentage') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.buckets.products') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($bucketData as $bucket)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $bucket['label'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ number_format($bucket['quantity'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $bucket['value']->formatTo(app()->getLocale()) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between text-sm">
                                                    <span>{{ number_format($bucket['value_percentage'], 1) }}%</span>
                                                </div>
                                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                                    <div class="bg-primary-600 h-2 rounded-full"
                                                         style="width: {{ $bucket['value_percentage'] }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $bucket['product_count'] }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="row"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.buckets.total') }}
                                </th>
                                <td class="px-6 py-3 text-left text-xs font-medium text-gray-900 dark:text-white">
                                    {{ number_format($reportData['total_quantity'], 2) }}
                                </td>
                                <td class="px-6 py-3 text-left text-xs font-medium text-gray-900 dark:text-white">
                                    {{ $reportData['total_value']->formatTo(app()->getLocale()) }}
                                </td>
                                <td class="px-6 py-3 text-left text-xs font-medium text-gray-900 dark:text-white">
                                    100.0%
                                </td>
                                <td class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ collect($bucketData)->sum('product_count') }}
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Expiring Lots --}}
        @if($expiringLots && count($expiringLots) > 0)
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory_reports.aging.expiration.title') }}
                    </h3>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.expiration.lot_code') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.expiration.product') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.expiration.expiration_date') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.expiration.days_until_expiration') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('inventory_reports.aging.expiration.quantity_on_hand') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($expiringLots as $lot)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $lot['lot_code'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $lot['product_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ Carbon::parse($lot['expiration_date'])->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($lot['days_until_expiration'] < 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ __('inventory_reports.aging.expired') }} ({{ abs($lot['days_until_expiration']) }} {{ __('inventory_reports.aging.days_ago') }})
                                                </span>
                                        @elseif($lot['days_until_expiration'] <= 7)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ $lot['days_until_expiration'] }} {{ __('inventory_reports.aging.days') }}
                                                </span>
                                        @elseif($lot['days_until_expiration'] <= 30)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    {{ $lot['days_until_expiration'] }} {{ __('inventory_reports.aging.days') }}
                                                </span>
                                        @else
                                            <span class="text-gray-900 dark:text-white">
                                                    {{ $lot['days_until_expiration'] }} {{ __('inventory_reports.aging.days') }}
                                                </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ number_format($lot['quantity_on_hand'], 2) }}
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
        @if(!$reportData || ($reportData['total_quantity'] == 0 && count($expiringLots) == 0))
            <x-filament::card>
                <div class="text-center py-8">
                    <x-heroicon-o-clock class="mx-auto h-12 w-12 text-gray-400"/>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('inventory_reports.aging.no_data') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('inventory_reports.aging.no_data_description') }}
                    </p>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
