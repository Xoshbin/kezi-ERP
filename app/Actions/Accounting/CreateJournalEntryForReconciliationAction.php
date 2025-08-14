<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\CurrencyConverterService;

class CreateJournalEntryForReconciliationAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CurrencyConverterService $currencyConverter
    ) {
    }

    public function execute(Payment $payment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for multi-currency handling.
        $payment->load('company.currency', 'journal.currency', 'currency');
        $company = $payment->company;

        // Use CurrencyConverterService for all currency conversion logic
        $conversion = $this->currencyConverter->convertToCompanyBaseCurrency(
            $payment->amount,
            $payment->currency,
            $company
        );

        $zeroAmountInBase = $conversion->createZeroInTargetCurrency();

        // 2. Get the required default accounts from the company.
        $bankAccountId = $company->default_bank_account_id;
        $outstandingAccountId = $company->default_outstanding_receipts_account_id;

        if (!$bankAccountId || !$outstandingAccountId) {
            throw new \RuntimeException('Default bank or outstanding receipts account is not configured for this company.');
        }

        // 3. Build the journal entry lines based on reconciliation accounting rules.
        // This entry moves value from the in-transit account to the final bank account.
        $lineDTOs = [
            // Rule: DEBIT the actual Bank Account to increase its balance.
            new CreateJournalEntryLineDTO(
                account_id: $bankAccountId,
                debit: $conversion->convertedAmount,
                credit: $zeroAmountInBase,
                description: 'Bank Account',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            ),
            // Rule: CREDIT the Outstanding Receipts/Payments account to clear it.
            new CreateJournalEntryLineDTO(
                account_id: $outstandingAccountId,
                debit: $zeroAmountInBase,
                credit: $conversion->convertedAmount,
                description: 'Outstanding Receipts/Payments',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            ),
        ];

        // 4. Prepare the data payload.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payment->company_id,
            journal_id: $payment->journal_id,
            currency_id: $conversion->targetCurrency->id, // Journal entry is always in company base currency
            entry_date: now(),
            reference: 'RECO/' . $payment->id,
            description: 'Reconciliation for Payment #' . $payment->id,
            source_type: Payment::class,
            source_id: $payment->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs,
        );

        // 5. Execute the action to create and post the entry.
        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
