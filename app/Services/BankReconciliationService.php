<?php

namespace App\Services;

use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class BankReconciliationService
{

    public function reconcilePayment(Payment $payment, BankStatementLine $statementLine, User $user): void
    {
        DB::transaction(function () use ($payment, $statementLine, $user) {
            // 1. Update the status of the payment and the statement line.
            $payment->status = 'Reconciled';
            $payment->save();

            $statementLine->is_reconciled = true;
            $statementLine->payment_id = $payment->id;
            $statementLine->save();

            // 2. Create the journal entry to move the funds.
            $this->createJournalEntryForReconciliation($payment, $user);
        });
    }

    private function createJournalEntryForReconciliation(Payment $payment, User $user): void
    {
        $bankAccountId = config('accounting.defaults.default_bank_account_id');
        $outstandingAccountId = config('accounting.defaults.outstanding_receipts_account_id');

        $lines = [
            ['account_id' => $bankAccountId, 'debit' => $payment->amount],
            ['account_id' => $outstandingAccountId, 'credit' => $payment->amount],
        ];

        $journalEntryData = [
            'company_id' => $payment->company_id,
            'journal_id' => $payment->journal_id,
            'entry_date' => now()->toDateString(),
            'reference' => 'RECO/' . $payment->id,
            'description' => 'Reconciliation for Payment #' . $payment->id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        (new JournalEntryService())->create($journalEntryData);
    }
}
