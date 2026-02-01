@php use Kezi\Accounting\Livewire\Accounting\BankReconciliationMatcher; @endphp
<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-4">{{ __('accounting::bank_statement.reconcile_bank_statement') }}</h1>

    @livewire(BankReconciliationMatcher::class, ['bankStatementId' => $record])
</x-filament-panels::page>
