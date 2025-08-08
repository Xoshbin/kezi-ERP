<div>
    {{-- Bank Statement Information --}}
    <div class="mb-6 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ __('bank_statement.statement_details') }}</h2>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.reference') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->reference }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.date') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->date->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.starting_balance') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->starting_balance->formatTo('en_US') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.ending_balance') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $bankStatement->ending_balance->formatTo('en_US') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Main Reconciliation Interface --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Bank Transactions Table --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('bank_statement.bank_transactions') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('bank_statement.bank_transactions_description') }}</p>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>

        {{-- System Payments Table --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('bank_statement.system_payments') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('bank_statement.system_payments_description') }}</p>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('bank_statement.select') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('bank_statement.partner') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('bank_statement.payment_date') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('bank_statement.type') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('bank_statement.amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($this->systemPayments as $payment)
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    wire:click="toggleSystemPayment({{ $payment->id }})">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                               @checked(in_array($payment->id, $selectedSystemPayments))
                                               wire:click.stop="toggleSystemPayment({{ $payment->id }})">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $payment->partner->name ?? __('bank_statement.no_partner') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $payment->payment_date->format('Y-m-d') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $payment->payment_type === 'inbound' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($payment->payment_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ $payment->amount->formatTo('en_US') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        {{ __('bank_statement.no_unreconciled_payments') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Section --}}
    <div class="mt-6 bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">{{ __('bank_statement.reconciliation_summary') }}</h3>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.bank_total') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $this->summary['bankTotalFormatted'] }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.system_total') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $this->summary['systemTotalFormatted'] }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ __('bank_statement.difference') }}</dt>
                <dd class="mt-1 text-sm font-bold {{ $this->summary['isBalanced'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->summary['differenceFormatted'] }}
                </dd>
            </div>
            <div>
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
    <div class="mt-6 flex justify-end space-x-3">
        <x-filament::button
            color="gray"
            wire:click="$refresh"
        >
            {{ __('bank_statement.refresh') }}
        </x-filament::button>

        <x-filament::button
            wire:click="reconcile"
            :disabled="!$this->summary['isBalanced'] || empty($selectedBankLines) || empty($selectedSystemPayments)"
            color="success"
        >
            {{ __('bank_statement.reconcile_selected') }}
        </x-filament::button>
    </div>
</div>
