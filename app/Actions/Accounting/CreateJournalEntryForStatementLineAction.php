<?php

namespace App\Actions\Accounting;


use App\Models\User;
use App\Models\Account;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use App\Models\BankStatementLine;
use Illuminate\Support\Facades\Log;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;

class CreateJournalEntryForStatementLineAction
{
    public function execute(BankStatementLine $line, Account $writeOffAccount, User $user, string $description): void
    {
        $valueInAction = $line->amount->getMinorAmount()->toInt();
        Log::info('2. Value at the start of the action: ' . $valueInAction);

        $journal = $line->bankStatement->journal;
        $bankAccount = $journal->default_debit_account_id
            ? $journal->defaultDebitAccount
            : $journal->defaultCreditAccount;

        if (!$bankAccount) {
            throw new InvalidArgumentException(
                'The selected Bank Journal is missing its default debit/credit account.'
            );
        }

        // THE FIX: Use the Money object's methods directly to avoid manual math.
        // 1. `$line->amount` is the Money object (e.g., for -$50.00).
        // 2. `getAbsoluteAmount()` returns its value as a precise BigDecimal object (e.g., 50.00).
        // 3. Casting to a string gives us a reliable "50.00" string representation.
        $amountInMinorUnits = $line->amount->abs()->getMinorAmount()->toInt();
        // Convert minor units to major units (decimal string) for CreateJournalEntryAction
        $amountInMajorUnits = $line->amount->abs()->getAmount()->toScale(3);

        $isCreditToBank = $line->amount->isNegative();

        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $writeOffAccount->id,
                debit: $isCreditToBank ? $amountInMajorUnits : '0',
                credit: $isCreditToBank ? '0' : $amountInMajorUnits,
                description: $description,
                partner_id: null,
                analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $bankAccount->id,
                debit: $isCreditToBank ? '0' : $amountInMajorUnits,
                credit: $isCreditToBank ? $amountInMajorUnits : '0',
                description: $description,
                partner_id: null,
                analytic_account_id: null,
            ),
        ];

        $journalEntryData = new CreateJournalEntryDTO(
            company_id: $line->bankStatement->company_id,
            journal_id: $journal->id,
            currency_id: $line->bankStatement->currency_id,
            entry_date: Carbon::today()->toDateString(),
            reference: $line->payment_ref ?: 'Reconciliation Write-Off',
            description: $description,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lines
        );

        // This action now correctly receives '50' and interprets it as $50.00.
        (new CreateJournalEntryAction())->execute($journalEntryData);

        // Mark the statement line as reconciled
        $line->update(['is_reconciled' => true]);
    }
}
