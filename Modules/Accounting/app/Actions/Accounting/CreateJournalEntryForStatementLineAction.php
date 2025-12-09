<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryForStatementLineDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;

class CreateJournalEntryForStatementLineAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(CreateJournalEntryForStatementLineDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $line = $dto->bankStatementLine;
            $writeOffAccount = $dto->writeOffAccount;
            $user = $dto->user;
            $description = $dto->description;

            $journal = $line->bankStatement->journal;
            $bankAccount = $journal->default_debit_account_id
                ? $journal->defaultDebitAccount
                : $journal->defaultCreditAccount;

            if (! $bankAccount) {
                throw new InvalidArgumentException(
                    'The selected Bank Journal is missing its default debit/credit account.'
                );
            }

            $currency = $line->bankStatement->currency;
            $amount = Money::of($line->amount->getAmount(), $currency->code)->abs();
            $zero = Money::zero($currency->code);
            $isCreditToBank = $line->amount->isNegative();

            $lines = [
                new CreateJournalEntryLineDTO(
                    account_id: $writeOffAccount->id,
                    debit: $isCreditToBank ? $amount : $zero,
                    credit: $isCreditToBank ? $zero : $amount,
                    description: $description,
                    partner_id: null,
                    analytic_account_id: null,
                ),
                new CreateJournalEntryLineDTO(
                    account_id: (int) $bankAccount->getKey(),
                    debit: $isCreditToBank ? $zero : $amount,
                    credit: $isCreditToBank ? $amount : $zero,
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
                reference: $line->description ?: 'Reconciliation Write-Off',
                description: $description,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: get_class($line),
                source_id: $line->id
            );

            // Create the journal entry
            $this->createJournalEntryAction->execute($journalEntryData);

            // Mark the statement line as reconciled
            $line->update(['is_reconciled' => true]);
        });
    }
}
