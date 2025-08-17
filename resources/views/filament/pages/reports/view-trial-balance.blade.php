<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <form wire:submit="generateReport">
            {{ $this->form }}
        </form>

        <!-- Report Display -->
        @if($reportData)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <!-- Balance Status Banner -->
                <div class="mb-6">
                    @if($reportData['isBalanced'])
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-green-800 dark:text-green-200 font-medium">{{ __('reports.trial_balance_balanced') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-red-800 dark:text-red-200 font-medium">{{ __('reports.trial_balance_not_balanced') }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Report Header -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('reports.trial_balance_report') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $reportData['companyName'] }} -
                        {{ __('reports.as_of') }}: {{ Carbon\Carbon::parse($reportData['asOfDate'])->format('M j, Y') }}
                    </p>
                </div>

                <!-- Trial Balance Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.account') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.debit') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.credit') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($reportData['reportLines'] as $line)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $line['accountCode'] }} - {{ $line['accountName'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                        @if($line['debitAmount'] > 0)
                                            {{ $line['debit'] }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                        @if($line['creditAmount'] > 0)
                                            {{ $line['credit'] }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('reports.no_account_balances_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <!-- Totals Row -->
                        @if(count($reportData['reportLines']) > 0)
                            <tfoot class="bg-gray-50 dark:bg-gray-800">
                                <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                    <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                        {{ __('reports.total') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $reportData['totalDebit'] }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $reportData['totalCredit'] }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Balance Verification -->
                @if(count($reportData['reportLines']) > 0)
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <strong>{{ __('reports.balance_verification') }}:</strong>
                            {{ __('reports.total_debits') }}: {{ $reportData['totalDebit'] }} |
                            {{ __('reports.total_credits') }}: {{ $reportData['totalCredit'] }} |
                            {{ __('reports.difference') }}:
                            @if($reportData['isBalanced'])
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ __('reports.balanced') }}</span>
                            @else
                                <span class="text-red-600 dark:text-red-400 font-medium">
                                    {{ number_format(abs($reportData['totalDebitAmount'] - $reportData['totalCreditAmount']), 2) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <!-- No Report Generated Yet -->
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('reports.no_report_generated') }}
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('reports.select_date_and_generate') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
