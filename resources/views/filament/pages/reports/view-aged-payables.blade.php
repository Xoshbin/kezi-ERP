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
                        {{ __('reports.aged_payables_report') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $reportData['companyName'] }} - 
                        {{ __('reports.as_of') }}: {{ Carbon\Carbon::parse($reportData['asOfDate'])->format('M j, Y') }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.vendor') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.current') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.1_30_days') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.31_60_days') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.61_90_days') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.90_plus_days') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('reports.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($reportData['reportLines'] as $line)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $line['partnerName'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        <span class="{{ $line['currentAmount'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                                            {{ $line['current'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        <span class="{{ $line['bucket1_30Amount'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500' }}">
                                            {{ $line['bucket1_30'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        <span class="{{ $line['bucket31_60Amount'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500' }}">
                                            {{ $line['bucket31_60'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        <span class="{{ $line['bucket61_90Amount'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500' }}">
                                            {{ $line['bucket61_90'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        <span class="{{ $line['bucket90_plusAmount'] > 0 ? 'text-red-800 dark:text-red-300' : 'text-gray-500' }}">
                                            {{ $line['bucket90_plus'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                        {{ $line['totalDue'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr class="font-semibold">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ __('reports.total') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ $reportData['totalCurrent'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ $reportData['totalBucket1_30'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ $reportData['totalBucket31_60'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ $reportData['totalBucket61_90'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ $reportData['totalBucket90_plus'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900 dark:text-white">
                                    {{ $reportData['grandTotalDue'] }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if(empty($reportData['reportLines']))
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ __('reports.no_outstanding_payables') }}
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
