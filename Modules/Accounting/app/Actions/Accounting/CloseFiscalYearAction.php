<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Accounting\CloseFiscalYearDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\FiscalYearState;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Events\FiscalYearClosed;
use Modules\Accounting\Exceptions\FiscalYearNotReadyToCloseException;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;

class CloseFiscalYearAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
    ) {}

    /**
     * Execute the action to close a fiscal year.
     *
     * This creates a closing entry following the Anglo-Saxon method:
     * - Debit all income accounts to zero out their credit balances
     * - Credit all expense accounts to zero out their debit balances
     * - Credit/Debit Retained Earnings for the net profit/loss
     *
     * @throws FiscalYearNotReadyToCloseException
     */
    public function execute(CloseFiscalYearDTO $dto): FiscalYear
    {
        return DB::transaction(function () use ($dto) {
            $fiscalYear = $dto->fiscalYear;

            // Validate fiscal year can be closed
            $this->validateCanClose($fiscalYear);

            // Get P&L account balances
            $plBalances = $this->getProfitAndLossBalances($fiscalYear);

            // Create the closing journal entry
            $closingEntry = $this->createClosingEntry(
                $fiscalYear,
                $plBalances,
                $dto->retainedEarningsAccountId,
                $dto->closedByUserId,
                $dto->description
            );

            // Update fiscal year with closing info
            $fiscalYear->update([
                'state' => FiscalYearState::Closed,
                'closing_journal_entry_id' => $closingEntry?->id,
                'closed_by_user_id' => $dto->closedByUserId,
                'closed_at' => Carbon::now(),
            ]);

            // Dispatch event
            event(new FiscalYearClosed($fiscalYear));

            return $fiscalYear->refresh();
        });
    }

    /**
     * Validate that the fiscal year can be closed.
     *
     * @throws FiscalYearNotReadyToCloseException
     */
    private function validateCanClose(FiscalYear $fiscalYear): void
    {
        if (! $fiscalYear->canClose()) {
            throw new FiscalYearNotReadyToCloseException(
                'Fiscal year cannot be closed. Ensure it is in open state and all periods are closed.'
            );
        }
    }

    /**
     * Get P&L account balances for the fiscal year.
     *
     * @return Collection<int, object{account_id: int, account_type: string, balance: int}>
     */
    private function getProfitAndLossBalances(FiscalYear $fiscalYear): Collection
    {
        $incomeTypes = [
            AccountType::Income->value,
            AccountType::OtherIncome->value,
        ];

        $expenseTypes = [
            AccountType::Expense->value,
            AccountType::Depreciation->value,
            AccountType::CostOfRevenue->value,
        ];

        $plAccountTypes = array_merge($incomeTypes, $expenseTypes);

        /** @var Collection<int, object{account_id: int, account_type: string, total_debit: string|null, total_credit: string|null}> $results */
        $results = DB::table('journal_entry_lines')
            ->select([
                'accounts.id as account_id',
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $fiscalYear->company_id)
            ->whereIn('accounts.type', $plAccountTypes)
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->whereBetween('journal_entries.entry_date', [
                $fiscalYear->start_date,
                $fiscalYear->end_date,
            ])
            ->groupBy('accounts.id', 'accounts.type')
            ->get();

        return $results->map(function ($row) {
            $totalDebit = (int) ($row->total_debit ?: 0);
            $totalCredit = (int) ($row->total_credit ?: 0);
            $balance = $totalDebit - $totalCredit;

            return (object) [
                'account_id' => $row->account_id,
                'account_type' => $row->account_type,
                'balance' => $balance,
            ];
        })->filter(fn ($row) => $row->balance !== 0);
    }

    /**
     * Create the closing journal entry.
     *
     * @param  Collection<int, object{account_id: int, account_type: string, balance: int}>  $plBalances
     */
    private function createClosingEntry(
        FiscalYear $fiscalYear,
        Collection $plBalances,
        int $retainedEarningsAccountId,
        int $userId,
        ?string $description
    ): ?JournalEntry {
        /** @var Company $company */
        $company = $fiscalYear->company;
        $currency = $company->currency;
        $currencyCode = $currency->code;

        // Get the miscellaneous/general journal for closing entries
        $journal = $this->getMiscellaneousJournal($company);

        $lines = [];
        $netIncome = Money::zero($currencyCode);

        $incomeTypes = [
            AccountType::Income->value,
            AccountType::OtherIncome->value,
        ];

        foreach ($plBalances as $row) {
            $isIncome = in_array($row->account_type, $incomeTypes);
            $balance = Money::ofMinor($row->balance, $currencyCode);

            if ($isIncome) {
                // Income accounts have credit balance (negative in our calculation)
                // To zero them out, we DEBIT them
                // balance is negative for credit balance, so we negate for the entry
                $debit = $balance->isNegative() ? $balance->negated() : Money::zero($currencyCode);
                $credit = $balance->isPositive() ? $balance : Money::zero($currencyCode);
                $netIncome = $netIncome->minus($balance); // Credit balance adds to net income
            } else {
                // Expense accounts have debit balance (positive in our calculation)
                // To zero them out, we CREDIT them
                $debit = $balance->isNegative() ? $balance->negated() : Money::zero($currencyCode);
                $credit = $balance->isPositive() ? $balance : Money::zero($currencyCode);
                $netIncome = $netIncome->minus($balance); // Debit balance reduces net income
            }

            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $row->account_id,
                debit: $debit,
                credit: $credit,
                description: $description ?? __('accounting::fiscal_year.closing_entry_line'),
                partner_id: null,
                analytic_account_id: null,
            );
        }

        // Add the retained earnings line for the net result
        if (! $netIncome->isZero()) {
            if ($netIncome->isPositive()) {
                // Net profit: credit retained earnings
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $retainedEarningsAccountId,
                    debit: Money::zero($currencyCode),
                    credit: $netIncome,
                    description: __('accounting::fiscal_year.net_profit_to_retained_earnings'),
                    partner_id: null,
                    analytic_account_id: null,
                );
            } else {
                // Net loss: debit retained earnings
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $retainedEarningsAccountId,
                    debit: $netIncome->negated(),
                    credit: Money::zero($currencyCode),
                    description: __('accounting::fiscal_year.net_loss_to_retained_earnings'),
                    partner_id: null,
                    analytic_account_id: null,
                );
            }
        }

        // If there are no P&L entries, skip creating a closing entry
        // This happens when the fiscal year had no income/expense transactions
        if (count($lines) === 0) {
            return null;
        }

        // Create the journal entry
        $dto = new CreateJournalEntryDTO(
            company_id: $fiscalYear->company_id,
            journal_id: $journal->id,
            currency_id: $currency->id,
            entry_date: $fiscalYear->end_date->format('Y-m-d'),
            reference: 'CLOSE-'.$fiscalYear->name,
            description: $description ?? __('accounting::fiscal_year.closing_entry_description', ['name' => $fiscalYear->name]),
            created_by_user_id: $userId,
            is_posted: true,
            lines: $lines,
            source_type: FiscalYear::class,
            source_id: $fiscalYear->id
        );

        return $this->createJournalEntryAction->execute($dto);
    }

    /**
     * Get the miscellaneous/general journal for closing entries.
     */
    private function getMiscellaneousJournal(Company $company): Journal
    {
        // Find a general or miscellaneous journal, or use the first available
        return Journal::where('company_id', $company->id)
            ->where(function ($query) {
                $query->where('type', 'general')
                    ->orWhere('type', 'miscellaneous');
            })
            ->first() ?? Journal::where('company_id', $company->id)->firstOrFail();
    }
}
