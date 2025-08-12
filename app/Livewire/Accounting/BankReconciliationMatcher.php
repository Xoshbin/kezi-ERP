<?php

namespace App\Livewire\Accounting;

use App\Models\BankStatement;
use App\Services\BankReconciliationService;
use App\Support\NumberFormatter;
use Brick\Money\Money;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BankReconciliationMatcher extends Component
{
    public int $bankStatementId;
    public BankStatement $bankStatement;

    // Totals from child components
    public Money $bankTotal;
    public Money $systemTotal;
    public array $selectedBankLines = [];
    public array $selectedPayments = [];

    public function mount(int $bankStatementId): void
    {
        $this->bankStatementId = $bankStatementId;
        $this->bankStatement = BankStatement::with(['currency', 'journal'])->findOrFail($bankStatementId);

        // Initialize totals
        $this->bankTotal = Money::of(0, $this->bankStatement->currency->code);
        $this->systemTotal = Money::of(0, $this->bankStatement->currency->code);
    }

    #[On('bank-selection-changed')]
    public function updateBankSelection(array $data): void
    {
        $this->selectedBankLines = $data['selectedIds'];
        $this->bankTotal = Money::ofMinor($data['total'], $data['currency']);
    }

    #[On('payment-selection-changed')]
    public function updatePaymentSelection(array $data): void
    {
        $this->selectedPayments = $data['selectedIds'];
        $this->systemTotal = Money::ofMinor($data['total'], $data['currency']);
    }

    #[Computed]
    public function summary(): array
    {
        $difference = $this->bankTotal->minus($this->systemTotal);

        return [
            'bankTotal' => $this->bankTotal,
            'systemTotal' => $this->systemTotal,
            'difference' => $difference,
            'isBalanced' => $difference->isZero(),
            'bankTotalFormatted' => NumberFormatter::formatMoneyTo($this->bankTotal),
            'systemTotalFormatted' => NumberFormatter::formatMoneyTo($this->systemTotal),
            'differenceFormatted' => NumberFormatter::formatMoneyTo($difference),
        ];
    }

    public function reconcile()
    {
        if (!$this->summary()['isBalanced']) {
            Notification::make()
                ->title(__('bank_statement.reconciliation_not_balanced'))
                ->danger()
                ->send();
            return;
        }

        if (empty($this->selectedBankLines) || empty($this->selectedPayments)) {
            Notification::make()
                ->title(__('bank_statement.select_transactions_to_reconcile'))
                ->warning()
                ->send();
            return;
        }

        // Use the service to reconcile
        app(BankReconciliationService::class)->reconcileMultiple(
            $this->selectedBankLines,
            $this->selectedPayments,
            Auth::user()
        );

        // Clear selections and reset totals
        $this->selectedBankLines = [];
        $this->selectedPayments = [];
        $this->bankTotal = Money::of(0, $this->bankStatement->currency->code);
        $this->systemTotal = Money::of(0, $this->bankStatement->currency->code);

        Notification::make()
            ->title(__('bank_statement.reconciliation_successful'))
            ->success()
            ->send();

        // Refresh child components
        $this->dispatch('refresh-tables');
    }



    public function render()
    {
        return view('livewire.accounting.bank-reconciliation-matcher');
    }
}
