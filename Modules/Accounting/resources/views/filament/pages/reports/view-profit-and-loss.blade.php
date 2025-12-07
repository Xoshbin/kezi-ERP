<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Date Range Form -->
        <form wire:submit="generateReport">
            {{ $this->form }}
        </form>

        <!-- Report Display -->
        @if($reportData)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('accounting::reports.profit_and_loss_statement') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('accounting::reports.period') }}: {{ Carbon\Carbon::parse($startDate)->format('M j, Y') }} - {{ Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                    </p>
                </div>

                <!-- Revenue Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                        {{ __('accounting::reports.revenue') }}
                    </h3>

                    @if(count($reportData['revenueLines']) > 0)
                        <div class="space-y-2">
                            @foreach($reportData['revenueLines'] as $line)
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
                                <span class="text-gray-900 dark:text-white">{{ __('accounting::reports.total_revenue') }}</span>
                                <span class="text-gray-900 dark:text-white font-semibold">
                                    {{ $reportData['totalRevenue'] }}
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('accounting::reports.no_revenue_transactions') }}</p>
                    @endif
                </div>

                <!-- Expenses Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                        {{ __('accounting::reports.expenses') }}
                    </h3>

                    @if(count($reportData['expenseLines']) > 0)
                        <div class="space-y-2">
                            @foreach($reportData['expenseLines'] as $line)
                                <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $line['accountName'] }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                        {{ $line['balance'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                            <div class="flex justify-between items-center font-semibold">
                                <span class="text-gray-900 dark:text-white">{{ __('accounting::reports.total_expenses') }}</span>
                                <span class="text-gray-900 dark:text-white font-semibold">
                                    {{ $reportData['totalExpenses'] }}
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('accounting::reports.no_expense_transactions') }}</p>
                    @endif
                </div>

                <!-- Net Income Section -->
                <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('accounting::reports.net_income') }}
                        </h3>
                        <span class="text-xl font-bold {{ $reportData['isNetLoss'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $reportData['netIncome'] }}
                        </span>
                    </div>
                    @if($reportData['isNetLoss'])
                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ __('accounting::reports.net_loss_note') }}
                        </p>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('accounting::reports.no_report_generated') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('accounting::reports.select_date_range_and_generate') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
