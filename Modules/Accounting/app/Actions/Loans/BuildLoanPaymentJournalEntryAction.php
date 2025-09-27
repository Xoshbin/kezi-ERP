<?php

namespace Modules\Accounting\Actions\Loans;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Enums\Loans\LoanType;
use App\Models\JournalEntry;
use App\Models\LoanAgreement;
use App\Models\LoanScheduleEntry;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class BuildLoanPaymentJournalEntryAction
{
    public function __construct(private readonly \Modules\Accounting\Actions\Accounting\CreateJournalEntryAction $createJE) {}

    public function execute(
        \Modules\Accounting\Models\LoanAgreement $loan,
        User $user,
        int $journalId,
        int $bankAccountId,
        int $loanAccountId,
        int $accruedInterestAccountId,
        int $forMonthSequence,
    ): JournalEntry {
        return DB::transaction(function () use ($loan, $user, $journalId, $bankAccountId, $loanAccountId, $accruedInterestAccountId, $forMonthSequence) {
            $loan->loadMissing('currency', 'company', 'scheduleEntries');
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new \RuntimeException('Loan currency missing');
            }
            $code = (string) data_get($currencyModel, 'code');

            /** @var \Modules\Accounting\Models\LoanScheduleEntry $entry */
            $entry = $loan->scheduleEntries()->where('sequence', $forMonthSequence)->firstOrFail();
            /** @var Money $int */ $int = $entry->interest_component;
            /** @var Money $prin */ $prin = $entry->principal_component;

            $zero = Money::of(0, $code);
            $lines = [];

            if ($loan->loan_type === LoanType::Payable) {
                // Repayment: Dr Accrued Interest, Dr Loan Payable (principal), Cr Bank
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $accruedInterestAccountId,
                    debit: $int,
                    credit: $zero,
                    description: 'Settle accrued interest for loan #'.$loan->id.' month '.$forMonthSequence,
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $loanAccountId,
                    debit: $prin,
                    credit: $zero,
                    description: 'Repay principal',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $bankAccountId,
                    debit: $zero,
                    credit: $int->plus($prin),
                    description: 'Loan repayment - bank',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
            } else {
                // Receivable loans: Dr Bank, Cr Accrued Interest, Cr Loan Receivable (principal)
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $bankAccountId,
                    debit: $int->plus($prin),
                    credit: $zero,
                    description: 'Loan repayment received',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $accruedInterestAccountId,
                    debit: $zero,
                    credit: $int,
                    description: 'Settle accrued interest',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $loanAccountId,
                    debit: $zero,
                    credit: $prin,
                    description: 'Reduce loan receivable',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
            }

            $dto = new CreateJournalEntryDTO(
                company_id: $loan->company_id,
                journal_id: $journalId,
                currency_id: $loan->currency_id,
                entry_date: $entry->due_date,
                reference: 'LOAN-PAY/'.$loan->id.'/'.$forMonthSequence,
                description: 'Loan repayment',
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: \Modules\Accounting\Models\LoanAgreement::class,
                source_id: $loan->id,
            );

            return $this->createJE->execute($dto);
        });
    }
}
