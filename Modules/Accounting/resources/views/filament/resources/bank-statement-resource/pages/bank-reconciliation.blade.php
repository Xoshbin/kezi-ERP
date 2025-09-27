@php use App\Livewire\Accounting\BankReconciliationMatcher; @endphp
<x-filament-panels::page>
    @livewire(BankReconciliationMatcher::class, ['bankStatementId' => $record])
</x-filament-panels::page>
