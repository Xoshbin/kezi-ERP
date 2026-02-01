<?php

namespace Kezi\Accounting\Actions\Loans;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Enums\Loans\LoanType;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\LoanAgreement;
use RuntimeException;

class CreateJournalEntryForLoanInitialRecognitionAction
{
    public function __construct(private readonly \Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction $createJE) {}

    public function execute(LoanAgreement $loan, User $user, int $journalId, int $bankAccountId, int $loanAccountId): JournalEntry
    {
        return DB::transaction(function () use ($loan, $user, $journalId, $bankAccountId, $loanAccountId) {
            $loan->loadMissing('company', 'currency');
            $company = $loan->company;
            if (! $company) {
                throw new RuntimeException('Loan company missing');
            }
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new RuntimeException('Loan currency missing');
            }
            $currencyCode = (string) data_get($currencyModel, 'code');

            /** @var Money $amount */
            $amount = $loan->principal_amount; // Money in loan currency

            // For initial recognition we post in transaction currency; CreateJournalEntryAction converts to base
            $zero = Money::of(0, $currencyCode);
            $lineDTOs = [];

            if ($loan->loan_type === LoanType::Payable) {
                // Borrowing: Dr Bank, Cr Loan Payable (LT)
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $bankAccountId,
                    debit: $amount,
                    credit: $zero,
                    description: 'Loan proceeds',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $loanAccountId,
                    debit: $zero,
                    credit: $amount,
                    description: 'Loan principal recognized',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
            } else {
                // Receivable loan: Dr Loan Receivable (LT), Cr Bank
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $loanAccountId,
                    debit: $amount,
                    credit: $zero,
                    description: 'Loan principal disbursed',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $bankAccountId,
                    debit: $zero,
                    credit: $amount,
                    description: 'Loan disbursement',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
            }

            $dto = new CreateJournalEntryDTO(
                company_id: (int) $loan->getAttribute('company_id'),
                journal_id: $journalId,
                currency_id: (int) $loan->getAttribute('currency_id'),
                entry_date: $loan->loan_date,
                reference: 'LOAN/'.(string) $loan->getAttribute('id'),
                description: 'Initial recognition of loan',
                created_by_user_id: (int) $user->getAttribute('id'),
                is_posted: true,
                lines: $lineDTOs,
                source_type: LoanAgreement::class,
                source_id: (int) $loan->getAttribute('id'),
            );

            return $this->createJE->execute($dto);
        });
    }
}
