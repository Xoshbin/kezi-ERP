<?php

namespace App\Services;

use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money; // Import the Money class
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

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
        $company = $payment->company;
        $bankAccountId = $company->default_bank_account_id;
        $outstandingAccountId = $company->default_outstanding_receipts_account_id;
        // MODIFIED: Get currency code for creating zero-value Money objects
        $currencyCode = $payment->currency->code;


        if (!$bankAccountId || !$outstandingAccountId) {
            throw new \RuntimeException('Default bank or outstanding receipts account is not configured for this company.');
        }

        // MODIFIED: Update lines to include zero-value Money objects for the opposing side.
        $lines = [
            ['account_id' => $bankAccountId, 'debit' => $payment->amount, 'credit' => Money::of(0, $currencyCode)],
            ['account_id' => $outstandingAccountId, 'credit' => $payment->amount, 'debit' => Money::of(0, $currencyCode)],
        ];

        $journalEntryData = [
            'company_id' => $payment->company_id,
            'journal_id' => $payment->journal_id,
            'currency_id' => $payment->currency_id,
            'entry_date' => now(), // Use a Carbon instance
            'reference' => 'RECO/' . $payment->id,
            'description' => 'Reconciliation for Payment #' . $payment->id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        // MODIFIED: Pass 'true' to post the journal entry immediately for consistency.
        $this->journalEntryService->create($journalEntryData, true);
    }
}