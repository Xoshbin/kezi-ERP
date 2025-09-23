@php
    $status = $status ?? 'empty';
@endphp

@if($status === 'empty')
    <span class="text-gray-500 text-sm">{{ $message }}</span>
@elseif($status === 'error')
    <span class="text-red-500 text-sm">{{ $message }}</span>
@elseif($status === 'calculated')
    <div class="space-y-2">
        @if($totalCost)
            <div class="flex justify-between items-center p-3 bg-blue-50 border border-blue-200 rounded-md">
                <span class="text-sm font-medium text-blue-800">{{ __('Total Estimated Cost') }}:</span>
                <span class="text-lg font-bold text-blue-900">{{ $totalCost->getAmount() }} {{ $totalCost->getCurrency() }}</span>
            </div>
        @endif

        @if($hasErrors)
            <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-red-800">{{ __('Cost Issues Detected') }}</h4>
                        <p class="text-xs text-red-700 mt-1">{{ __('Some products have cost determination issues. Please review individual product lines.') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($warnings))
            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">{{ __('Warnings') }}</h4>
                        <ul class="mt-1 space-y-1">
                            @foreach($warnings as $warning)
                                <li class="flex items-start">
                                    <span class="text-yellow-400 mr-2 mt-0.5">•</span>
                                    <span class="text-xs text-yellow-700">{{ $warning }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
