<div>
    {{-- Bank Statement Information --}}
    <div class="mb-6 bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ __('bank_statement.statement_details') }}</h2>
        <dl class="flex flex-row justify-between w-full space-x-8">
            <div class="flex flex-col flex-1 items-start">
            <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.reference') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->reference }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
            <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.date') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->date->format('Y-m-d') }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
            <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.starting_balance') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\NumberFormatter::formatMoneyTo($bankStatement->starting_balance) }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
            <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.ending_balance') }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\NumberFormatter::formatMoneyTo($bankStatement->ending_balance) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Main Reconciliation Interface --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Bank Transactions Table --}}
        <livewire:accounting.bank-transactions-table :bank-statement="$bankStatement" />

        {{-- System Payments Table --}}
        <livewire:accounting.system-payments-table :bank-statement="$bankStatement" />
    </div>

    {{-- Summary Section --}}
    <div class="mt-6 bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">{{ __('bank_statement.reconciliation_summary') }}</h3>
        <dl class="flex flex-row justify-between w-full space-x-8">
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.bank_total') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $this->summary['bankTotalFormatted'] }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.system_total') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $this->summary['systemTotalFormatted'] }}</dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.difference') }}</dt>
                <dd class="mt-1 text-sm font-bold {{ $this->summary['isBalanced'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->summary['differenceFormatted'] }}
                </dd>
            </div>
            <div class="flex flex-col flex-1 items-start">
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.status') }}</dt>
                <dd class="mt-1">
                    @if($this->summary['isBalanced'])
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ __('bank_statement.balanced') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ __('bank_statement.not_balanced') }}
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
            {{ __('bank_statement.refresh') }}
        </x-filament::button>

        <x-filament::button
            wire:click="reconcile"
            :disabled="!$this->summary['isBalanced'] || empty($selectedBankLines) || empty($selectedPayments)"
            color="success"
        >
            {{ __('bank_statement.reconcile_selected') }}
        </x-filament::button>
    </div>
</div>
