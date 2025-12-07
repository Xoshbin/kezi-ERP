@php use Filament\Facades\Filament; @endphp
<div>
    {{-- Bank Statement Information --}}
    <div class="mb-6 bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900">{{ __('accounting::bank_statement.statement_details') }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('docs.show', ['slug' => 'User Guide/payments']) }}" target="_blank"
                   class="inline-flex items-center text-xs text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16h6M4 6h16M4 18h16"/>
                    </svg>
                    {{ __('Payments Guide') }}
                </a>
                <span class="text-sm text-gray-500">{{ __('accounting::bank_statement.currency') }}:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--color-info-100)] text-[var(--color-info-800)]">
                    {{ $bankStatement->currency->code ?? Filament::getTenant()?->currency?->code }}
                </span>
            </div>
        </div>
        <dl class="flex flex-row justify-between w-full space-x-8">
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.reference') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->reference ?? '-' }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.date') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ optional($bankStatement->date)->format('Y-m-d') ?? '-' }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.starting_balance') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->starting_balance ? \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($bankStatement->starting_balance) : '0.000' }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.ending_balance') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->ending_balance ? \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($bankStatement->ending_balance) : '0.000' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Main Reconciliation Interface --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Bank Transactions Table --}}
        @if($bankStatement->exists)
            <h3 class="text-md font-semibold mb-2">{{ __('accounting::bank_statement.bank_transactions') }}</h3>
            <livewire:accounting.bank-transactions-table :bank-statement="$bankStatement"/>
        @else
            <div class="p-6 border rounded-lg text-sm text-gray-500">{{ __('accounting::bank_statement.no_bank_statement_lines') }}</div>
        @endif

        {{-- System Payments Table --}}
        @if($bankStatement->exists)
            <h3 class="text-md font-semibold mb-2">{{ __('accounting::bank_statement.system_payments') }}</h3>
            <livewire:accounting.system-payments-table :bank-statement="$bankStatement"/>
        @else
            <div class="p-6 border rounded-lg text-sm text-gray-500">{{ __('accounting::bank_statement.no_unreconciled_payments') }}</div>
        @endif
    </div>

    {{-- Summary Section --}}
    <div class="mt-6 bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('accounting::bank_statement.reconciliation_summary') }}</h3>
            <div class="text-xs text-gray-500">
                {{ __('accounting::bank_statement.all_amounts_in_currency', ['currency' => $bankStatement->currency->code]) }}
            </div>
        </div>
        <dl class="flex flex-row justify-between w-full space-x-8">
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.bank_total') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $this->summary['bankTotalFormatted'] }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.system_total') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $this->summary['systemTotalFormatted'] }}</dd>
                @if(!empty($selectedPayments))
                    <div class="mt-1 text-xs text-gray-400">
                        {{ __('accounting::bank_statement.includes_currency_conversions') }}
                    </div>
                @endif
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.difference') }}</dt>
                <dd class="mt-1 text-sm font-bold {{ $this->summary['isBalanced'] ? 'text-[var(--color-success-600)]' : 'text-[var(--color-danger-600)]' }}">
                    {{ $this->summary['differenceFormatted'] }}
                </dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('accounting::bank_statement.status') }}</dt>
                <dd class="mt-1">
                    @if($this->summary['isBalanced'])
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--color-success-100)] text-[var(--color-success-800)]">
                            {{ __('accounting::bank_statement.balanced') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--color-danger-100)] text-[var(--color-danger-800)]">
                            {{ __('accounting::bank_statement.not_balanced') }}
                        </span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    {{-- Action Buttons --}}
    <div class="mt-6 flex justify-end gap-2">
        <x-filament::button
                color="gray"
                wire:click="$refresh"
        >
            {{ __('accounting::bank_statement.refresh') }}
        </x-filament::button>

        <x-filament::button
                wire:click="reconcile"
                :disabled="!$this->summary['isBalanced'] || empty($selectedBankLines) || empty($selectedPayments)"
                color="success"
        >
            {{ __('accounting::bank_statement.reconcile_selected') }}
        </x-filament::button>
    </div>
</div>
