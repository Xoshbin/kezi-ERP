<?php

namespace Modules\Accounting\Actions\Loans;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Enums\Loans\LoanType;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\LoanAgreement;
use Modules\Accounting\Models\LoanScheduleEntry;
use RuntimeException;

class ReclassifyLoanCurrentPortionAction
{
    public function __construct(private readonly \Modules\Accounting\Actions\Accounting\CreateJournalEntryAction $createJE)
    {
    }

    public function execute(
        LoanAgreement $loan,
        User $user,
        int $journalId,
        int $longTermAccountId,
        int $shortTermAccountId,
        int $months,
        string $asOfDate,
    ): JournalEntry {
        return DB::transaction(function () use ($loan, $user, $journalId, $longTermAccountId, $shortTermAccountId, $months, $asOfDate) {
            $loan->loadMissing('currency', 'company', 'scheduleEntries');
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new RuntimeException('Loan currency missing');
            }
            $code = (string) data_get($currencyModel, 'code');

            $asOf = Carbon::parse($asOfDate);

            // Sum principal components due in next N months after asOf
            $sum = Money::of(0, $code);
            /** @var Collection<int, LoanScheduleEntry> $entries */
            $entries = $loan->scheduleEntries()->orderBy('sequence')->get();
            foreach ($entries as $entry) {
                if ($entry->due_date->lessThanOrEqualTo($asOf)) {
                    continue; // already current or past
                }
                if ($entry->due_date->greaterThan($asOf->copy()->addMonths($months))) {
                    break; // beyond window
                }
                /** @var Money $pc */
                $pc = $entry->principal_component;
                $sum = $sum->plus($pc);
            }

            if ($sum->isZero()) {
                return app(JournalEntry::class)::factory()->make(); // no-op: return an unpersisted JE for test simplicity
            }

            $zero = Money::of(0, $code);
            $lines = [];

            if ($loan->loan_type === LoanType::Payable) {
                // Move from LT liability to ST liability: Dr LT, Cr ST
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $shortTermAccountId,
                    debit: $sum,
                    credit: $zero,
                    description: 'Reclassify current portion to ST',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $longTermAccountId,
                    debit: $zero,
                    credit: $sum,
                    description: 'Reduce LT portion',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
            } else {
                // Receivable: Move from LT asset to ST asset: Dr ST, Cr LT
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $shortTermAccountId,
                    debit: $sum,
                    credit: $zero,
                    description: 'Reclassify current portion to ST',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $longTermAccountId,
                    debit: $zero,
                    credit: $sum,
                    description: 'Reduce LT portion',
                    partner_id: $loan->partner_id,
                    analytic_account_id: null,
                );
            }

            $dto = new CreateJournalEntryDTO(
                company_id: $loan->company_id,
                journal_id: $journalId,
                currency_id: $loan->currency_id,
                entry_date: $asOf->toDateString(),
                reference: 'LOAN-RECLASS/' . $loan->id . '/' . $months,
                description: 'Reclassify loan current portion',
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: LoanAgreement::class,
                source_id: $loan->id,
            );

            return $this->createJE->execute($dto);
        });
    }
}
