<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('bank_statement.bank_transactions') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('bank_statement.bank_transactions_description') }}</p>
    </div>
    <div class="p-6">
        {{ $this->table }}
    </div>
</div>
