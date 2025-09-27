<?php

namespace Modules\Accounting\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money;
use RuntimeException;

class CreateJournalEntryForReconciliationAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(\Modules\Payment\Models\Payment $payment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $payment->load('company', 'journal.currency');
        $company = $payment->company;
        $journal = $payment->journal;
        $currency = $journal->currency;

        if (! $currency) {
            throw new \InvalidArgumentException('Journal currency is not configured');
        }

        $currencyCode = $currency->code;

        // The amount must be in the currency of the journal.
        // We create a new Money instance from the payment's minor amount, but with the journal's currency.
        $amountInJournalCurrency = Money::ofMinor($payment->amount->getMinorAmount(), $currencyCode);

        // 2. Get the required default accounts from the company.
        $bankAccountId = $company->default_bank_account_id;
        $outstandingAccountId = $company->default_outstanding_receipts_account_id;

        if (! $bankAccountId || ! $outstandingAccountId) {
            throw new RuntimeException('Default bank or outstanding receipts account is not configured for this company.');
        }

        // 3. Build the journal entry lines based on reconciliation accounting rules.
        // This entry moves value from the in-transit account to the final bank account.
        $lineDTOs = [
            // Rule: DEBIT the actual Bank Account to increase its balance.
            new CreateJournalEntryLineDTO(
                account_id: $bankAccountId,
                debit: $amountInJournalCurrency,
                credit: Money::of(0, $currencyCode),
                description: 'Bank Account',
                partner_id: null,
                analytic_account_id: null,
            ),
            // Rule: CREDIT the Outstanding Receipts/Payments account to clear it.
            new CreateJournalEntryLineDTO(
                account_id: $outstandingAccountId,
                debit: Money::of(0, $currencyCode),
                credit: $amountInJournalCurrency,
                description: 'Outstanding Receipts/Payments',
                partner_id: null,
                analytic_account_id: null,
            ),
        ];

        // 4. Prepare the data payload.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payment->company_id,
            journal_id: $payment->journal_id,
            currency_id: $currency->id,
            entry_date: now(),
            reference: 'RECO/'.$payment->id,
            description: 'Reconciliation for Payment #'.$payment->id,
            source_type: \Modules\Payment\Models\Payment::class,
            source_id: $payment->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs,
        );

        // 5. Execute the action to create and post the entry.
        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
