<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Date Form -->
        <form wire:submit="generateReport">
            {{ $this->form }}
        </form>

        <!-- Report Display -->
        @if($reportData)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('reports.balance_sheet') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('reports.as_of') }}: {{ Carbon\Carbon::parse($asOfDate)->format('M j, Y') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Assets Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                            {{ __('reports.assets') }}
                        </h3>

                        @if(count($reportData['assetLines']) > 0)
                            <div class="space-y-2">
                                @foreach($reportData['assetLines'] as $line)
                                    <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $line['accountName'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $line['balance'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-gray-900 dark:text-white">{{ __('reports.total_assets') }}</span>
                                    <span class="text-gray-900 dark:text-white font-bold text-lg">
                                        {{ $reportData['totalAssets'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('reports.no_asset_accounts') }}</p>
                        @endif
                    </div>

                    <!-- Liabilities and Equity Section -->
                    <div>
                        <!-- Liabilities -->
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                            {{ __('reports.liabilities') }}
                        </h3>

                        @if(count($reportData['liabilityLines']) > 0)
                            <div class="space-y-2 mb-6">
                                @foreach($reportData['liabilityLines'] as $line)
                                    <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $line['accountName'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $line['balance'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-6">
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-gray-900 dark:text-white">{{ __('reports.total_liabilities') }}</span>
                                    <span class="text-gray-900 dark:text-white font-semibold">
                                        {{ $reportData['totalLiabilities'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic mb-6">{{ __('reports.no_liability_accounts') }}</p>
                        @endif

                        <!-- Equity -->
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                            {{ __('reports.equity') }}
                        </h3>

                        <div class="space-y-2">
                            @if(count($reportData['equityLines']) > 0)
                                @foreach($reportData['equityLines'] as $line)
                                    <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $line['accountName'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $line['balance'] }}
                                        </span>
                                    </div>
                                @endforeach
                            @endif

                            <!-- Current Year Earnings -->
                            <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-mono text-gray-500 dark:text-gray-400">CYE</span>
                                    <span class="text-sm text-gray-900 dark:text-white">{{ __('reports.current_year_earnings') }}</span>
                                </div>
                                <span class="text-sm font-medium {{ $reportData['isCurrentYearLoss'] ? 'text-[var(--color-danger-600)] dark:text-[var(--color-danger-400)]' : 'text-gray-900 dark:text-white' }}">
                                    {{ $reportData['currentYearEarnings'] }}
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                            <div class="flex justify-between items-center font-semibold">
                                <span class="text-gray-900 dark:text-white">{{ __('reports.total_equity') }}</span>
                                <span class="text-gray-900 dark:text-white font-semibold">
                                    {{ $reportData['totalEquity'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Total Liabilities and Equity -->
                        <div class="border-t-2 border-gray-300 dark:border-gray-600 mt-6 pt-4">
                            <div class="flex justify-between items-center font-bold">
                                <span class="text-gray-900 dark:text-white text-lg">{{ __('reports.total_liabilities_and_equity') }}</span>
                                <span class="text-gray-900 dark:text-white font-bold text-lg">
                                    {{ $reportData['totalLiabilitiesAndEquity'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Balance Verification -->
                <div class="mt-8 p-4 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-success-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-success-800 dark:text-success-200">
                            {{ __('reports.balance_sheet_balanced') }}
                        </span>
                    </div>
                    <p class="text-sm text-success-700 dark:text-success-300 mt-1">
                        {{ __('reports.assets_equal_liabilities_equity') }}: {{ $reportData['totalAssets'] }}
                    </p>
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('reports.no_report_generated') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('reports.select_date_and_generate') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
