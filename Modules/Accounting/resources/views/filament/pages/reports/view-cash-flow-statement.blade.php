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
                        {{ __('accounting::reports.cash_flow_statement') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('accounting::reports.period') }}: {{ Carbon\Carbon::parse($startDate)->format('M j, Y') }} - {{ Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                    </p>
                </div>

                <div class="space-y-8">
                    <!-- Operating Activities Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                            {{ __('accounting::reports.operating_activities') }}
                        </h3>

                        @if(count($reportData['operatingLines']) > 0)
                            <div class="space-y-2">
                                @foreach($reportData['operatingLines'] as $line)
                                    <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                        <div class="flex items-center space-x-3">
                                            @if($line['accountCode'])
                                                <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                            @endif
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $line['description'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium {{ $line['isNegative'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ $line['amount'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-gray-900 dark:text-white">{{ __('accounting::reports.net_cash_from_operating') }}</span>
                                    <span class="font-bold {{ $reportData['isTotalOperatingNegative'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $reportData['totalOperating'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('accounting::reports.no_operating_activities') }}</p>
                        @endif
                    </div>

                    <!-- Investing Activities Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                            {{ __('accounting::reports.investing_activities') }}
                        </h3>

                        @if(count($reportData['investingLines']) > 0)
                            <div class="space-y-2">
                                @foreach($reportData['investingLines'] as $line)
                                    <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                        <div class="flex items-center space-x-3">
                                            @if($line['accountCode'])
                                                <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                            @endif
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $line['description'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium {{ $line['isNegative'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ $line['amount'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-gray-900 dark:text-white">{{ __('accounting::reports.net_cash_from_investing') }}</span>
                                    <span class="font-bold {{ $reportData['isTotalInvestingNegative'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $reportData['totalInvesting'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('accounting::reports.no_investing_activities') }}</p>
                        @endif
                    </div>

                    <!-- Financing Activities Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                            {{ __('accounting::reports.financing_activities') }}
                        </h3>

                        @if(count($reportData['financingLines']) > 0)
                            <div class="space-y-2">
                                @foreach($reportData['financingLines'] as $line)
                                    <div class="flex justify-between items-center py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm">
                                        <div class="flex items-center space-x-3">
                                            @if($line['accountCode'])
                                                <span class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $line['accountCode'] }}</span>
                                            @endif
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $line['description'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium {{ $line['isNegative'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ $line['amount'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-gray-900 dark:text-white">{{ __('accounting::reports.net_cash_from_financing') }}</span>
                                    <span class="font-bold {{ $reportData['isTotalFinancingNegative'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $reportData['totalFinancing'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('accounting::reports.no_financing_activities') }}</p>
                        @endif
                    </div>

                    <!-- Summary Section -->
                    <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ __('accounting::reports.net_change_in_cash') }}</span>
                                <span class="font-bold text-lg {{ $reportData['isNetChangeNegative'] ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ $reportData['netChangeInCash'] }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('accounting::reports.beginning_cash') }}</span>
                                <span class="text-gray-900 dark:text-white">{{ $reportData['beginningCash'] }}</span>
                            </div>

                            <div class="flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-4">
                                <span class="text-gray-900 dark:text-white font-bold text-lg">{{ __('accounting::reports.ending_cash') }}</span>
                                <span class="text-gray-900 dark:text-white font-bold text-lg">{{ $reportData['endingCash'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verification Note -->
                <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ __('accounting::reports.cash_flow_indirect_method') }}
                        </span>
                    </div>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        {{ __('accounting::reports.cash_flow_verification_note') }}
                    </p>
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('accounting::reports.no_report_generated') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('accounting::reports.select_dates_and_generate') }}
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
