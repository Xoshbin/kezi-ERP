<?php

namespace App\Livewire;

use Brick\Money\Money;
use App\Models\Payment;
use Livewire\Component;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Services\BankReconciliationService;

class BankReconciliationMatcher extends Component
{
    public BankStatement $record;

    public array $selectedLines = [];
    public array $selectedPayments = [];

    #[Computed]
    public function bankTotal(): Money
    {
        if (empty($this->selectedLines)) {
            return Money::of(0, $this->record->currency->code);
        }

        $total = BankStatementLine::whereIn('id', $this->selectedLines)->sum('amount');
        return Money::ofMinor($total, $this->record->currency->code);
    }

    #[Computed]
    public function paymentTotal(): Money
    {
        if (empty($this->selectedPayments)) {
            return Money::of(0, $this->record->currency->code);
        }

        $payments = Payment::find($this->selectedPayments);
        $total = Money::of(0, $this->record->currency->code);

        foreach ($payments as $payment) {
            // To balance against bank statement lines, inbound payments are treated
            // as negative and outbound as positive in this calculation.
            if ($payment->payment_type === 'inbound') {
                $total = $total->minus($payment->amount);
            } else { // outbound
                $total = $total->plus($payment->amount);
            }
        }
        return $total;
    }

    #[Computed]
    public function difference(): Money
    {
        return $this->bankTotal()->plus($this->paymentTotal());
    }

    public function reconcile(): void
    {
        // Ensure there's something to reconcile and the totals match
        if ($this->difference()->isZero() === false || (count($this->selectedLines) === 0 && count($this->selectedPayments) === 0)) {
            return;
        }

        try {
            // Instantiate and execute the service
            $service = new BankReconciliationService();
            $service->reconcile($this->selectedLines, $this->selectedPayments, Auth::user());

            // Send a success notification
            Notification::make()
                ->title('Reconciliation Successful')
                ->body('The selected items have been reconciled.')
                ->success()
                ->send();

            // Reset the selections to clear the UI
            $this->reset('selectedLines', 'selectedPayments');

        } catch (\Exception $e) {
            // Send a failure notification
            Notification::make()
                ->title('Reconciliation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        $statementLines = $this->record->bankStatementLines()
            ->where('is_reconciled', false)
            ->with('partner') // Eager load partner for display
            ->get();

        $payments = Payment::where('status', Payment::STATUS_CONFIRMED)
            ->where('journal_id', $this->record->journal_id)
            ->with('partner') // Eager load partner for display
            ->get();

        return view('livewire.bank-reconciliation-matcher', [
            'statementLines' => $statementLines,
            'payments' => $payments,
        ]);
    }
}
