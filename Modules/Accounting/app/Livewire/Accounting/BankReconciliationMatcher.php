<?php

namespace Modules\Accounting\Livewire\Accounting;

use App\Models\Company;
use Brick\Money\Money;
use Exception;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Accounting\Models\BankStatement;

class BankReconciliationMatcher extends Component
{
    public int $bankStatementId;

    public BankStatement $bankStatement;

    // Totals from child components
    public Money $bankTotal;

    public Money $systemTotal;

    /** @var array<int, int> */
    public array $selectedBankLines = [];

    /** @var array<int, int> */
    public array $selectedPayments = [];

    public function mount(int $bankStatementId): void
    {
        $this->bankStatementId = $bankStatementId;
        /** @var Company|null $tenant */
        $tenant = Filament::getTenant();

        $bankStatement = BankStatement::with(['currency', 'journal'])->find($bankStatementId);

        if (! $bankStatement || ($tenant && $bankStatement->company_id !== $tenant->getKey())) {
            // Unauthorized or non-existent: render a safe, empty state without leaking data
            $this->bankStatement = new BankStatement([
                'id' => 0,
                'company_id' => $tenant?->id,
                'currency_id' => $tenant?->currency_id,
            ]);

            if ($tenant) {
                $this->bankStatement->setRelation('currency', $tenant->currency);
            }
        } else {
            $this->bankStatement = $bankStatement;
        }

        $currencyCode = $this->bankStatement->currency->code ?? $tenant->currency->code ?? 'IQD'; // fallback IQD

        // Initialize totals
        $this->bankTotal = Money::of(0, $currencyCode);
        $this->systemTotal = Money::of(0, $currencyCode);
    }

    /**
     * @param  array{selectedIds: array<int, int>, total: int, currency: string}  $data
     */
    #[On('bank-selection-changed')]
    public function updateBankSelection(array $data): void
    {
        $this->selectedBankLines = $data['selectedIds'];
        $this->bankTotal = Money::ofMinor($data['total'], $data['currency']);
    }

    /**
     * @param  array{selectedIds: array<int, int>, total: int, currency: string}  $data
     */
    #[On('payment-selection-changed')]
    public function updatePaymentSelection(array $data): void
    {
        $this->selectedPayments = $data['selectedIds'];
        $this->systemTotal = Money::ofMinor($data['total'], $data['currency']);
    }

    /**
     * @return array{bankTotal: string, systemTotal: string, difference: string, isBalanced: bool}
     */
    #[Computed]
    public function summary(): array
    {
        $difference = $this->bankTotal->minus($this->systemTotal);

        return [
            'bankTotal' => $this->bankTotal,
            'systemTotal' => $this->systemTotal,
            'difference' => $difference,
            'isBalanced' => $difference->isZero(),
            'bankTotalFormatted' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($this->bankTotal),
            'systemTotalFormatted' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($this->systemTotal),
            'differenceFormatted' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($difference),
        ];
    }

    public function reconcile(): void
    {
        if (! $this->summary()['isBalanced']) {
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
        $user = Auth::user();
        if (! $user) {
            throw new Exception('User must be authenticated to reconcile transactions');
        }
        app(\Modules\Accounting\Services\BankReconciliationService::class)->reconcileMultiple(
            $this->selectedBankLines,
            $this->selectedPayments,
            $user
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

    public function render(): View
    {
        return view('livewire.accounting.bank-reconciliation-matcher');
    }
}
