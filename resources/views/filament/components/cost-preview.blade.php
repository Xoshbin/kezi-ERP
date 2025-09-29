@php
    $status = $status ?? 'empty';
@endphp

@if($status === 'empty')
    <span class="text-gray-500 text-sm">{{ $message }}</span>
@elseif($status === 'error')
    <span class="text-red-500 text-sm">{{ $message }}</span>
@elseif($status === 'valid')
    <div class="p-3 bg-green-50 border border-green-200 rounded-md">
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-green-800">{{ __('Unit Cost') }}:</span>
                <span class="text-sm font-semibold text-green-900">{{ $unitCost->getAmount() }} {{ $unitCost->getCurrency() }}</span>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-green-800">{{ __('Total Cost') }}:</span>
                <span class="text-base font-bold text-green-900">{{ $totalCost->getAmount() }} {{ $totalCost->getCurrency() }}</span>
            </div>

            <div class="flex justify-between items-center pt-1 border-t border-green-200">
                <span class="text-xs font-medium text-green-700">{{ __('Cost Source') }}:</span>
                <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">{{ $costSource->label() }}</span>
            </div>
        </div>

        @if(!empty($warnings))
            <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-2">
                        <p class="text-sm font-medium text-yellow-800">{{ __('Warnings') }}:</p>
                        @foreach($warnings as $warning)
                            <p class="text-xs text-yellow-700">• {{ $warning }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@elseif($status === 'invalid')
    <div class="p-3 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <div class="flex items-center">
                    <h4 class="text-sm font-medium text-red-800">{{ __('Cost Information Unavailable') }}</h4>
                </div>
                <div class="mt-1">
                    <p class="text-xs text-red-700 leading-relaxed">{{ $message }}</p>
                </div>

                @if(!empty($suggestedActions))
                    <div class="mt-3">
                        <h5 class="text-xs font-semibold text-red-800 mb-1">{{ __('Suggested Actions') }}:</h5>
                        <ul class="space-y-1">
                            @foreach($suggestedActions as $action)
                                <li class="flex items-start">
                                    <span class="text-red-400 mr-2 mt-0.5">•</span>
                                    <span class="text-xs text-red-700 leading-relaxed">{{ $action }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($establishmentSteps))
                    <div class="mt-3">
                        <h5 class="text-xs font-semibold text-red-800 mb-1">{{ __('Cost Establishment Steps') }}:</h5>
                        <ol class="space-y-1">
                            @foreach($establishmentSteps as $step)
                                <li class="flex items-start">
                                    <span class="text-xs text-red-700 leading-relaxed">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if(!empty($requirements))
                    <div class="mt-3">
                        <h5 class="text-xs font-semibold text-red-800 mb-1">{{ __('Requirements') }}:</h5>
                        <ul class="space-y-1">
                            @foreach($requirements as $key => $requirement)
                                <li class="flex items-start">
                                    <span class="text-red-400 mr-2 mt-0.5">•</span>
                                    <span class="text-xs text-red-700 leading-relaxed">{{ $requirement }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
