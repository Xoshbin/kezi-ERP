<?php

namespace Kezi\Accounting\Services;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalYear;

class FiscalYearService
{
    /**
     * Get the profit and loss balances for a fiscal year.
     *
     * @return array{income: Money, expenses: Money, netIncome: Money}
     */
    public function getProfitAndLossBalances(FiscalYear $fiscalYear): array
    {
        /** @var Company $company */
        $company = $fiscalYear->company;
        $currencyCode = $company->currency->code;
        $zero = Money::zero($currencyCode);

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

        /** @var Collection<int, object{account_type: string, total_debit: string|null, total_credit: string|null}> $results */
        $results = DB::table('journal_entry_lines')
            ->select([
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
            ->groupBy('accounts.type')
            ->get();

        $totalIncome = $zero;
        $totalExpenses = $zero;

        foreach ($results as $row) {
            $totalDebit = (int) ($row->total_debit ?: 0);
            $totalCredit = (int) ($row->total_credit ?: 0);
            $balance = $totalCredit - $totalDebit; // Credits are positive for income

            if (in_array($row->account_type, $incomeTypes)) {
                $totalIncome = $totalIncome->plus(Money::ofMinor($balance, $currencyCode));
            } else {
                // Expenses: debit balance is positive (totalDebit - totalCredit)
                $expenseBalance = $totalDebit - $totalCredit;
                $totalExpenses = $totalExpenses->plus(Money::ofMinor($expenseBalance, $currencyCode));
            }
        }

        return [
            'income' => $totalIncome,
            'expenses' => $totalExpenses,
            'netIncome' => $totalIncome->minus($totalExpenses),
        ];
    }

    /**
     * Validate if a fiscal year is ready to close.
     *
     * @return array{ready: bool, issues: array<int, string>}
     */
    public function validateReadyToClose(FiscalYear $fiscalYear): array
    {
        $issues = [];

        // Check state
        if (! $fiscalYear->state->canClose()) {
            $issues[] = __('accounting::fiscal_year.validation.not_open');
        }

        // Check periods are closed (if using periods)
        if ($fiscalYear->periods()->exists()) {
            $openPeriods = $fiscalYear->periods()->open()->count();
            if ($openPeriods > 0) {
                $issues[] = __('accounting::fiscal_year.validation.open_periods', ['count' => $openPeriods]);
            }
        }

        // Check for draft journal entries in the period
        $draftEntries = DB::table('journal_entries')
            ->where('company_id', $fiscalYear->company_id)
            ->whereBetween('entry_date', [$fiscalYear->start_date, $fiscalYear->end_date])
            ->where('state', JournalEntryState::Draft->value)
            ->count();

        if ($draftEntries > 0) {
            $issues[] = __('accounting::fiscal_year.validation.draft_entries', ['count' => $draftEntries]);
        }

        return [
            'ready' => count($issues) === 0,
            'issues' => $issues,
        ];
    }

    /**
     * Get the retained earnings account for a company.
     */
    public function getRetainedEarningsAccount(Company $company): ?Account
    {
        // Look for an equity account with "retained earnings" in the name or code
        return Account::where('company_id', $company->id)
            ->where('type', AccountType::Equity->value)
            ->where(function ($query) {
                $query->where('code', 'like', '%330101%')
                    ->orWhere('name', 'like', '%retained%');
            })
            ->first();
    }

    /**
     * Get equity accounts suitable for retained earnings.
     *
     * @return Collection<int, Account>
     */
    public function getEquityAccounts(Company $company): Collection
    {
        return Account::where('company_id', $company->id)
            ->where('type', AccountType::Equity->value)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get candidate accounts and their balances for opening entry.
     *
     * @return Collection<int, array{account: Account, partner_id: int|null, balance: Money}>
     */
    public function getOpeningBalanceCandidates(FiscalYear $sourceYear): Collection
    {
        $currencyCode = $sourceYear->company->currency->code;
        $balanceSheetTypes = array_map(fn ($type) => $type->value, AccountType::balanceSheetTypes());

        $results = DB::table('journal_entry_lines')
            ->select([
                'journal_entry_lines.account_id',
                'journal_entry_lines.partner_id',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $sourceYear->company_id)
            ->whereIn('accounts.type', $balanceSheetTypes)
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->where('journal_entries.entry_date', '<=', $sourceYear->end_date)
            ->groupBy('journal_entry_lines.account_id', 'journal_entry_lines.partner_id')
            ->havingRaw('SUM(journal_entry_lines.debit) != SUM(journal_entry_lines.credit)')
            ->get();

        $candidates = collect();

        if ($results->isEmpty()) {
            return $candidates;
        }

        $accountIds = $results->pluck('account_id')->unique()->toArray();
        $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

        foreach ($results as $result) {
            $account = $accounts->get($result->account_id);
            if (! $account) {
                continue;
            }

            $debit = (int) $result->total_debit;
            $credit = (int) $result->total_credit;

            // We calculate Net Debit Balance.
            // Positive = Debit Balance
            // Negative = Credit Balance
            $netDebit = $debit - $credit;
            $balance = Money::ofMinor($netDebit, $currencyCode);

            $candidates->push([
                'account' => $account,
                'partner_id' => $result->partner_id,
                'balance' => $balance,
            ]);
        }

        return $candidates;
    }

    /**
     * Get the fiscal year containing a specific date.
     */
    public function getFiscalYearForDate(Company $company, Carbon $date): ?FiscalYear
    {
        return FiscalYear::forCompany($company)
            ->containingDate($date)
            ->first();
    }
}
