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
                        {{ __('reports.partner_ledger') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $reportData['partnerName'] }} -
                        {{ __('reports.period') }}: {{ Carbon\Carbon::parse($startDate)->format('M j, Y') }}
                        {{ __('reports.to') }} {{ Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                    </p>
                </div>

                <!-- Opening Balance -->
                <div class="mb-6 p-4 bg-[var(--color-info-50)] dark:bg-[var(--color-info-900)]/20 border border-[var(--color-info-200)] dark:border-[var(--color-info-800)] rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-[var(--color-info-800)] dark:text-[var(--color-info-200)]">
                            {{ __('reports.opening_balance') }}
                        </span>
                        <span class="text-sm font-bold text-[var(--color-info-900)] dark:text-[var(--color-info-100)] {{ $reportData['openingBalanceAmount'] < 0 ? 'text-[var(--color-danger-600)] dark:text-[var(--color-danger-400)]' : '' }}">
                            {{ $reportData['openingBalance'] }}
                        </span>
                    </div>
                </div>

                @if(count($reportData['transactionLines']) > 0)
                    <!-- Transaction Lines -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('reports.date') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('reports.reference') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('reports.type') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('reports.debit') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('reports.credit') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('reports.balance') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($reportData['transactionLines'] as $line)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ Carbon\Carbon::parse($line['date'])->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $line['reference'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($line['transactionType'] === 'Invoice') bg-[var(--color-success-100)] text-[var(--color-success-800)] dark:bg-[var(--color-success-900)]/20 dark:text-[var(--color-success-400)]
                                                @elseif($line['transactionType'] === 'Vendor Bill') bg-[var(--color-warning-100)] text-[var(--color-warning-800)] dark:bg-[var(--color-warning-900)]/20 dark:text-[var(--color-warning-400)]
                                                @elseif($line['transactionType'] === 'Payment') bg-[var(--color-info-100)] text-[var(--color-info-800)] dark:bg-[var(--color-info-900)]/20 dark:text-[var(--color-info-400)]
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                                                @endif">
                                                {{ $line['transactionType'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono text-gray-900 dark:text-white">
                                            @if($line['debitAmount'] > 0)
                                                {{ $line['debit'] }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono text-gray-900 dark:text-white">
                                            @if($line['creditAmount'] > 0)
                                                {{ $line['credit'] }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono font-medium {{ $line['balanceAmount'] < 0 ? 'text-[var(--color-danger-600)] dark:text-[var(--color-danger-400)]' : 'text-gray-900 dark:text-white' }}">
                                            {{ $line['balance'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Closing Balance -->
                    <div class="mt-6 p-4 bg-[var(--color-success-50)] dark:bg-[var(--color-success-900)]/20 border border-[var(--color-success-200)] dark:border-[var(--color-success-800)] rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-[var(--color-success-800)] dark:text-[var(--color-success-200)]">
                                {{ __('reports.closing_balance') }}
                            </span>
                            <span class="text-lg font-bold {{ $reportData['closingBalanceAmount'] < 0 ? 'text-[var(--color-danger-600)] dark:text-[var(--color-danger-400)]' : 'text-[var(--color-success-900)] dark:text-[var(--color-success-100)]' }}">
                                {{ $reportData['closingBalance'] }}
                            </span>
                        </div>
                        @if($reportData['closingBalanceAmount'] > 0)
                            <p class="text-xs text-[var(--color-success-700)] dark:text-[var(--color-success-300)] mt-1">
                                {{ __('reports.customer_owes_us') }}
                            </p>
                        @elseif($reportData['closingBalanceAmount'] < 0)
                            <p class="text-xs text-[var(--color-danger-700)] dark:text-[var(--color-danger-300)] mt-1">
                                {{ __('reports.we_owe_vendor') }}
                            </p>
                        @else
                            <p class="text-xs text-[var(--color-success-700)] dark:text-[var(--color-success-300)] mt-1">
                                {{ __('reports.account_balanced') }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 dark:text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-medium mb-2">{{ __('reports.no_transactions') }}</h3>
                            <p class="text-sm">{{ __('reports.no_transactions_found_for_period') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('reports.no_report_generated') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('reports.select_partner_and_generate') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
