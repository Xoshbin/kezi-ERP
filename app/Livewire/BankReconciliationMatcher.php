<?php

namespace App\Livewire;

use App\Models\BankStatement;
use App\Models\Payment;
use Livewire\Component;

class BankReconciliationMatcher extends Component
{
    public BankStatement $record;

    public array $selectedLines = [];
    public array $selectedPayments = [];

    // This mount method is no longer needed because Livewire will
    // automatically inject the public $record property passed from the view.

    public function render()
    {
        $statementLines = $this->record->bankStatementLines()
            ->where('is_reconciled', false)
            ->get();

        $payments = Payment::where('status', Payment::STATUS_CONFIRMED)
            ->where('journal_id', $this->record->journal_id)
            ->get();

        // The view file path is automatically determined by Livewire
        return view('livewire.bank-reconciliation-matcher', [
            'statementLines' => $statementLines,
            'payments' => $payments,
        ]);
    }
}
