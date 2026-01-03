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

            $netIncome = null;

            // If previous year is NOT closed, we must calculate the Net Income manually
            // and add it to Retained Earnings effectively in the opening entry.
            // Otherwise the entry will be unbalanced because Income/Expense accounts are ignored.
            if (! $sourceYear->isClosed()) {
                $plBalances = $this->fiscalYearService->getProfitAndLossBalances($sourceYear);
                $netIncome = $plBalances['netIncome']; // This is Income - Expense
            }

            foreach ($candidates as $candidate) {
                /** @var \Modules\Accounting\Models\Account $account */
                $account = $candidate['account'];
                /** @var \Brick\Money\Money $balance */
                $balance = $candidate['balance']; // Net Debit Balance
                $partnerId = $candidate['partner_id'];

                // Check if this is the Retained Earnings account
                // If we have an unclosed year, we need to add the calculated Net Income to this account's balance
                // to simulate the closing.
                // Note: There could be multiple Retained Earnings accounts (rare), or specifically the one defined in settings.
                // For simplicity/robustness: We should probably identify the "Default" RE account and add it there.
                // Or better: Add a SEPARATE line for "Unallocated Earnings" if RE account is not found in candidates?
                // Let's rely on finding the RE account.

                // If this account IS the Retained Earnings account (we might need a smarter check than just one account instance)
                // Actually, let's handle the Net Income as a separate ADDITION to the lines list after the loop,
                // targeting the default Retained Earnings account provided by the Service.

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
                    partner_id: $partnerId,
                    analytic_account_id: null,
                );
            }

            // Handle Unclosed Year Net Income
            if ($netIncome && ! $netIncome->isZero()) {
                $reAccount = $this->fiscalYearService->getRetainedEarningsAccount($targetYear->company);

                if (! $reAccount) {
                    // Fallback: Try to find ANY Equity account if specific RE not found
                    $reAccount = $this->fiscalYearService->getEquityAccounts($targetYear->company)->first();
                }

                if (! $reAccount) {
                    throw new \RuntimeException('Cannot generate Opening Entry: Previous year is open/unbalanced, and no Equity/Retained Earnings account could be found to park the Net Income.');
                }

                // If Net Income is POSITIVE (Profit), it's a CREDIT to Equity.
                // If Net Income is NEGATIVE (Loss), it's a DEBIT to Equity.

                $niAmount = $netIncome->getAmount()->toInt();
                $niDebit = 0;
                $niCredit = 0;

                // Logic check:
                // Profit = Credit Balance in P&L.
                // To move to Equity: Debit P&L (closing), Credit Equity.
                // So Positive Net Income => Credit Equity.

                // My getProfitAndLossBalances returns: Income (Credit) - Expense (Debit).
                // Wait, getProfitAndLossBalances returns Money objects where values are absolute?
                // Let's check getProfitAndLossBalances implementation.
                // It returns $totalIncome - $totalExpenses.
                // If Income (100) > Expense (80), NetResult = 20 (Positive).
                // This 20 needs to be CREDITED to Retained Earnings.

                if ($niAmount > 0) {
                    $niCredit = $niAmount;
                } else {
                    $niDebit = abs($niAmount);
                }

                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $reAccount->id,
                    debit: Money::ofMinor($niDebit, $currencyCode),
                    credit: Money::ofMinor($niCredit, $currencyCode),
                    description: __('accounting::fiscal_year.unallocated_earnings_label', ['year' => $sourceYear->name]), // We need to add this translation key
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
