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
            <div class="p-2 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-800">{{ __('Some products have cost determination issues. Please review individual product lines.') }}</p>
            </div>
        @endif

        @if(!empty($warnings))
            <div class="p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                <p class="text-sm font-medium text-yellow-800">{{ __('Warnings') }}:</p>
                @foreach($warnings as $warning)
                    <p class="text-xs text-yellow-700">• {{ $warning }}</p>
                @endforeach
            </div>
        @endif
    </div>
@endif
