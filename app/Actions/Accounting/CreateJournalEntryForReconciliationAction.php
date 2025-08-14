<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money;

class CreateJournalEntryForReconciliationAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(Payment $payment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for multi-currency handling.
        $payment->load('company.currency', 'journal.currency', 'currency');
        $company = $payment->company;
        $journal = $payment->journal;
        $baseCurrency = $company->currency;
        $paymentCurrency = $payment->currency;

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $paymentCurrency->id) ? 1.0 : $paymentCurrency->exchange_rate;

        // Convert payment amount to company base currency
        $amountInBase = Money::of(
            $payment->amount->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );

        $zeroAmountInBase = Money::zero($baseCurrency->code);

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
                debit: $amountInBase,
                credit: $zeroAmountInBase,
                description: 'Bank Account',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $payment->amount, // Original Money object
                original_currency_id: $payment->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            ),
            // Rule: CREDIT the Outstanding Receipts/Payments account to clear it.
            new CreateJournalEntryLineDTO(
                account_id: $outstandingAccountId,
                debit: $zeroAmountInBase,
                credit: $amountInBase,
                description: 'Outstanding Receipts/Payments',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $payment->amount, // Original Money object
                original_currency_id: $payment->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            ),
        ];

        // 4. Prepare the data payload.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payment->company_id,
            journal_id: $payment->journal_id,
            currency_id: $baseCurrency->id, // Journal entry is always in company base currency
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
