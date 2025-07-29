<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Illuminate\Support\Facades\App;

class CreateJournalEntryForReconciliationAction
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = App::make(JournalEntryService::class);
    }

    public function execute(Payment $payment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $payment->load('company.currency', 'currency');
        $company = $payment->company;
        $currencyCode = $payment->currency->code;

        // 2. Get the required default accounts from the company.
        $bankAccountId = $company->default_bank_account_id;
        $outstandingAccountId = $company->default_outstanding_receipts_account_id;

        if (!$bankAccountId || !$outstandingAccountId) {
            throw new \RuntimeException('Default bank or outstanding receipts account is not configured for this company.');
        }

        // 3. Build the journal entry lines based on reconciliation accounting rules.
        // This entry moves value from the in-transit account to the final bank account.
        $lines = [
            // Rule: DEBIT the actual Bank Account to increase its balance.
            [
                'account_id' => $bankAccountId,
                'debit' => $payment->amount,
                'credit' => Money::of(0, $currencyCode),
            ],
            // Rule: CREDIT the Outstanding Receipts/Payments account to clear it.
            [
                'account_id' => $outstandingAccountId,
                'credit' => $payment->amount,
                'debit' => Money::of(0, $currencyCode),
            ],
        ];

        // 4. Prepare the data payload.
        $journalEntryData = [
            'company_id' => $payment->company_id,
            'journal_id' => $payment->journal_id,
            'currency_id' => $payment->currency_id,
            'entry_date' => now(),
            'reference' => 'RECO/' . $payment->id,
            'description' => 'Reconciliation for Payment #' . $payment->id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        // 5. Execute the generic service to create and post the entry.
        return $this->journalEntryService->create($journalEntryData, true);
    }
}
