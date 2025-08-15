<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <form wire:submit="generateReport">
            {{ $this->form }}
        </form>

        <!-- Report Display -->
        @if($reportData)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('reports.general_ledger') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $reportData['companyName'] }} -
                        {{ __('reports.period') }}: {{ Carbon\Carbon::parse($reportData['startDate'])->format('M j, Y') }}
                        {{ __('reports.to') }} {{ Carbon\Carbon::parse($reportData['endDate'])->format('M j, Y') }}
                    </p>
                </div>

                @if(count($reportData['accounts']) > 0)
                    <div class="space-y-8">
                        @foreach($reportData['accounts'] as $account)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <!-- Account Header -->
                                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                                {{ $account['accountCode'] }} - {{ $account['accountName'] }}
                                            </h3>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('reports.opening_balance') }}</p>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $account['openingBalance'] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transaction Lines -->
                                @if(count($account['transactionLines']) > 0)
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.date') }}
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.reference') }}
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.description') }}
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.contra_account') }}
                                                    </th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.debit') }}
                                                    </th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.credit') }}
                                                    </th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                        {{ __('reports.balance') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                                @foreach($account['transactionLines'] as $line)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                            {{ Carbon\Carbon::parse($line['date'])->format('M j, Y') }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                            {{ $line['reference'] }}
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                            {{ $line['description'] }}
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                            {{ $line['contraAccount'] }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                                            @if($line['debitAmount'] > 0)
                                                                {{ $line['debit'] }}
                                                            @else
                                                                <span class="text-gray-400">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                                            @if($line['creditAmount'] > 0)
                                                                {{ $line['credit'] }}
                                                            @else
                                                                <span class="text-gray-400">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                                            {{ $line['balance'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <!-- Account Footer -->
                                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('reports.closing_balance') }}
                                        </span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $account['closingBalance'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-medium mb-2">{{ __('reports.no_data') }}</h3>
                            <p class="text-sm">{{ __('reports.no_transactions_found') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
