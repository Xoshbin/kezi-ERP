<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-4">{{ $this->getHeading() }}</h1>

    <div class="space-y-6">
        {{-- Filters Form --}}
        {{ $this->form }}

        {{-- Summary Cards --}}
        @if($reportData)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- Total COGS --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-banknotes class="w-8 h-8 text-primary-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.summary.total_cogs') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $reportData['total_cogs']->formatTo(app()->getLocale()) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Average Inventory --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-cube class="w-8 h-8 text-success-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.summary.average_inventory') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $reportData['average_inventory_value']->formatTo(app()->getLocale()) }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Turnover Ratio --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-arrow-path class="w-8 h-8 text-info-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.summary.turnover_ratio') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($reportData['inventory_turnover_ratio'], 2) }}x
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.annualized') }}: {{ number_format($annualizedTurnover, 2) }}x
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                {{-- Days Sales in Inventory --}}
                <x-filament::card>
                    <div class="flex items-center space-x-2">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-clock class="w-8 h-8 text-warning-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.summary.days_sales_inventory') }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($reportData['days_sales_inventory'], 0) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.days') }}
                            </p>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        @endif

        {{-- Period Information --}}
        @if($reportData)
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory::inventory_reports.turnover.period_info.title') }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.period_info.start_date') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $reportData['period_start']->format('M d, Y') }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.period_info.end_date') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $reportData['period_end']->format('M d, Y') }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('inventory::inventory_reports.turnover.period_info.period_length') }}
                            </p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $periodLength }} {{ __('inventory_reports.turnover.days') }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Turnover Analysis --}}
        @if($turnoverAnalysis)
            <x-filament::card>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory::inventory_reports.turnover.analysis.title') }}
                    </h3>

                    <div class="p-6 rounded-lg border-2
                        @if($turnoverAnalysis['color'] === 'success') border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20
                        @elseif($turnoverAnalysis['color'] === 'info') border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20
                        @elseif($turnoverAnalysis['color'] === 'warning') border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20
                        @else border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 @endif">

                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($turnoverAnalysis['color'] === 'success')
                                    <x-heroicon-o-check-circle class="w-8 h-8 text-green-600" />
                                @elseif($turnoverAnalysis['color'] === 'info')
                                    <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
                                @elseif($turnoverAnalysis['color'] === 'warning')
                                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-yellow-600" />
                                @else
                                    <x-heroicon-o-x-circle class="w-8 h-8 text-red-600" />
                                @endif
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold
                                    @if($turnoverAnalysis['color'] === 'success') text-green-900 dark:text-green-100
                                    @elseif($turnoverAnalysis['color'] === 'info') text-blue-900 dark:text-blue-100
                                    @elseif($turnoverAnalysis['color'] === 'warning') text-yellow-900 dark:text-yellow-100
                                    @else text-red-900 dark:text-red-100 @endif">
                                    {{ $turnoverAnalysis['description'] }}
                                </h4>
                                <p class="text-sm
                                    @if($turnoverAnalysis['color'] === 'success') text-green-700 dark:text-green-300
                                    @elseif($turnoverAnalysis['color'] === 'info') text-blue-700 dark:text-blue-300
                                    @elseif($turnoverAnalysis['color'] === 'warning') text-yellow-700 dark:text-yellow-300
                                    @else text-red-700 dark:text-red-300 @endif">
                                    {{ __('inventory::inventory_reports.turnover.analysis.ratio_explanation', ['ratio' => number_format($reportData['inventory_turnover_ratio'], 2)]) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Performance Benchmarks --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-600" />
                                <span class="text-sm font-medium text-green-900 dark:text-green-100">
                                    {{ __('inventory::inventory_reports.turnover.analysis.excellent') }}
                                </span>
                            </div>
                            <p class="text-xs text-green-700 dark:text-green-300 mt-1">
                                {{ __('inventory::inventory_reports.turnover.benchmarks.excellent') }}
                            </p>
                        </div>

                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                    {{ __('inventory::inventory_reports.turnover.analysis.good') }}
                                </span>
                            </div>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                {{ __('inventory::inventory_reports.turnover.benchmarks.good') }}
                            </p>
                        </div>

                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600" />
                                <span class="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                    {{ __('inventory::inventory_reports.turnover.analysis.average') }}
                                </span>
                            </div>
                            <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ __('inventory::inventory_reports.turnover.benchmarks.average') }}
                            </p>
                        </div>

                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-x-circle class="w-5 h-5 text-red-600" />
                                <span class="text-sm font-medium text-red-900 dark:text-red-100">
                                    {{ __('inventory::inventory_reports.turnover.analysis.poor') }}
                                </span>
                            </div>
                            <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                                {{ __('inventory::inventory_reports.turnover.benchmarks.poor') }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- No Data State --}}
        @if(!$reportData || ($reportData['total_cogs']->isZero() && $reportData['average_inventory_value']->isZero()))
            <x-filament::card>
                <div class="text-center py-8">
                    <x-heroicon-o-arrow-path class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('inventory::inventory_reports.turnover.no_data') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('inventory::inventory_reports.turnover.no_data_description') }}
                    </p>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
