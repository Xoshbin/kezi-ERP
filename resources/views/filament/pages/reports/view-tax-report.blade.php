<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament-panels::form wire:submit="generateReport">
            {{ $this->form }}
        </x-filament-panels::form>

        <!-- Report Display -->
        @if($reportData)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('reports.tax_report') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $reportData['companyName'] }} -
                        {{ __('reports.period') }}: {{ Carbon\Carbon::parse($startDate)->format('M j, Y') }}
                        {{ __('reports.to') }} {{ Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                    </p>
                </div>

                <!-- Output Tax (Sales) Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('reports.output_tax_on_sales') }}
                    </h3>

                    @if(count($reportData['outputTaxLines']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.tax_name') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.tax_rate') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.net_amount') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.tax_amount') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($reportData['outputTaxLines'] as $line)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $line['taxName'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                                {{ number_format($line['taxRate'], 2) }}%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                                {{ $line['netAmount'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                                {{ $line['taxAmount'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('reports.total_output_tax') }}:
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $reportData['totalOutputTax'] }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            {{ __('reports.no_output_tax_transactions') }}
                        </div>
                    @endif
                </div>

                <!-- Input Tax (Purchases) Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('reports.input_tax_on_purchases') }}
                    </h3>

                    @if(count($reportData['inputTaxLines']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.tax_name') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.tax_rate') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.net_amount') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('reports.tax_amount') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($reportData['inputTaxLines'] as $line)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $line['taxName'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                                {{ number_format($line['taxRate'], 2) }}%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                                {{ $line['netAmount'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                                {{ $line['taxAmount'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('reports.total_input_tax') }}:
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $reportData['totalInputTax'] }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            {{ __('reports.no_input_tax_transactions') }}
                        </div>
                    @endif
                </div>

                <!-- Summary Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('reports.tax_summary') }}
                    </h3>

                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('reports.total_output_tax') }}
                                </div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $reportData['totalOutputTax'] }}
                                </div>
                            </div>

                            <div class="text-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('reports.total_input_tax') }}
                                </div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $reportData['totalInputTax'] }}
                                </div>
                            </div>

                            <div class="text-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('reports.net_tax_payable') }}
                                </div>
                                <div class="text-xl font-bold {{ $reportData['netTaxPayableRaw'] < 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $reportData['netTaxPayable'] }}
                                </div>
                                @if($reportData['netTaxPayableRaw'] < 0)
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">
                                        {{ __('reports.refundable') }}
                                    </div>
                                @else
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                                        {{ __('reports.payable') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
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
                    {{ __('reports.select_date_range_and_generate') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
