<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateOpeningBalanceEntryDTO;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\FiscalYearService;

class CreateOpeningBalanceEntryAction
{
    public function __construct(
        protected FiscalYearService $fiscalYearService,
        protected CreateJournalEntryAction $createJournalEntryAction,
    ) {}

    public function execute(CreateOpeningBalanceEntryDTO $dto): JournalEntry
    {
        return DB::transaction(function () use ($dto) {
            $targetYear = $dto->newFiscalYear;
            $sourceYear = $dto->previousFiscalYear;

            // 1. Get candidates (Balance Sheet accounts with non-zero balance)
            $candidates = $this->fiscalYearService->getOpeningBalanceCandidates($sourceYear);

            if ($candidates->isEmpty()) {
                throw new \RuntimeException(__('accounting::fiscal_year.error.no_opening_balances'));
            }

            // 2. Prepare Journal Entry Lines
            $lines = [];
            $currencyCode = $targetYear->company->currency->code;

            foreach ($candidates as $candidate) {
                /** @var \Modules\Accounting\Models\Account $account */
                $account = $candidate['account'];
                /** @var \Brick\Money\Money $balance */
                $balance = $candidate['balance']; // Net Debit Balance

                // Skip zero balances just in case
                if ($balance->isZero()) {
                    continue;
                }

                $amount = $balance->getAmount()->toInt(); // Minor units
                $debitAmount = 0;
                $creditAmount = 0;

                if ($amount > 0) {
                    $debitAmount = $amount;
                } else {
                    $creditAmount = abs($amount);
                }

                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $account->id,
                    debit: Money::ofMinor($debitAmount, $currencyCode),
                    credit: Money::ofMinor($creditAmount, $currencyCode),
                    description: __('accounting::fiscal_year.opening_balance_label', ['year' => $sourceYear->name]),
                    partner_id: null,
                    analytic_account_id: null,
                );
            }

            // 3. Create the Opening Entry
            // We use a predefined Journal for Opening Entries if possible, or general Miscellaneous
            // Ideally, the company settings should define an "Opening/Closing Journal".
            // Fallback: Find a Miscellaneous operations journal.
            $journal = $targetYear->company->journals()
                ->where('type', 'miscellaneous')
                ->first();

            if (! $journal) {
                throw new \RuntimeException('No Miscellaneous Journal found for Opening Entry.');
            }

            $entryDTO = new CreateJournalEntryDTO(
                company_id: $targetYear->company_id,
                journal_id: $journal->id,
                currency_id: $targetYear->company->currency_id,
                entry_date: $targetYear->start_date,
                reference: 'OPENING/'.$targetYear->name,
                description: __('accounting::fiscal_year.opening_entry_description', ['year' => $targetYear->name]),
                created_by_user_id: $dto->createdByUserId,
                is_posted: false, // Create as Draft for review
                lines: $lines,
            );

            return $this->createJournalEntryAction->execute($entryDTO);
        });
    }
}
