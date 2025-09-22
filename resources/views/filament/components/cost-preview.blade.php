@php
    $status = $status ?? 'empty';
@endphp

@if($status === 'empty')
    <span class="text-gray-500 text-sm">{{ $message }}</span>
@elseif($status === 'error')
    <span class="text-red-500 text-sm">{{ $message }}</span>
@elseif($status === 'valid')
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">{{ __('Unit Cost') }}:</span>
            <span class="text-sm text-gray-900">{{ $unitCost->getAmount() }} {{ $unitCost->getCurrency() }}</span>
        </div>

        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">{{ __('Total Cost') }}:</span>
            <span class="text-sm font-semibold text-gray-900">{{ $totalCost->getAmount() }} {{ $totalCost->getCurrency() }}</span>
        </div>

        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">{{ __('Cost Source') }}:</span>
            <span class="text-sm text-blue-600">{{ $costSource->label() }}</span>
        </div>

        @if(!empty($warnings))
            <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-2">
                        <p class="text-sm text-yellow-800">{{ __('Warnings') }}:</p>
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
                <svg class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-2">
                <p class="text-sm font-medium text-red-800">{{ __('Cost Information Unavailable') }}</p>
                <p class="text-xs text-red-700">{{ $message }}</p>

                @if(!empty($suggestedActions))
                    <div class="mt-2">
                        <p class="text-xs font-medium text-red-800">{{ __('Suggested Actions') }}:</p>
                        @foreach($suggestedActions as $action)
                            <p class="text-xs text-red-700">• {{ $action }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
