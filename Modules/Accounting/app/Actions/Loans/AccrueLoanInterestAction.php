<?php

namespace Modules\Accounting\Actions\Loans;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\LoanAgreement;
use App\Models\LoanScheduleEntry;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class AccrueLoanInterestAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJE) {}

    /**
     * Accrue interest for a given schedule sequence (monthly end by default).
     */
    public function execute(LoanAgreement $loan, User $user, int $journalId, int $interestAccountId, int $accruedInterestAccountId, int $forMonthSequence): JournalEntry
    {
        return DB::transaction(function () use ($loan, $user, $journalId, $interestAccountId, $accruedInterestAccountId, $forMonthSequence) {
            $loan->loadMissing('currency', 'company', 'scheduleEntries');
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new \RuntimeException('Loan currency missing');
            }
            $currencyCode = (string) data_get($currencyModel, 'code');

            /** @var LoanScheduleEntry $entry */
            $entry = $loan->scheduleEntries()->where('sequence', $forMonthSequence)->firstOrFail();

            // Amount to accrue = interest_component of schedule entry
            /** @var \Brick\Money\Money $amount */
            $amount = $entry->interest_component;

            $zero = Money::of(0, $currencyCode);
            $lineDTOs = [
                new CreateJournalEntryLineDTO(
                    account_id: $interestAccountId,
                    debit: $amount,
                    credit: $zero,
                    description: 'Interest accrual for loan #'.$loan->id.' month '.$forMonthSequence,
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $accruedInterestAccountId,
                    debit: $zero,
                    credit: $amount,
                    description: 'Accrued interest',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                ),
            ];

            $dto = new CreateJournalEntryDTO(
                company_id: $loan->company_id,
                journal_id: $journalId,
                currency_id: $loan->currency_id,
                entry_date: $entry->due_date,
                reference: 'LOAN-INT/'.$loan->id.'/'.$forMonthSequence,
                description: 'Loan interest accrual',
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
                source_type: get_class($loan),
                source_id: $loan->id,
            );

            return $this->createJE->execute($dto);
        });
    }
}
