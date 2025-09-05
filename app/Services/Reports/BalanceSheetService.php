<?php

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\BalanceSheetDTO;
use App\DataTransferObjects\Reports\ReportLineDTO;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryState;
use App\Exceptions\BalanceSheetNotBalancedException;
use App\Models\Company;
use App\Models\JournalEntryLine;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    public function generate(Company $company, Carbon $asOfDate): BalanceSheetDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        $fiscalYearStart = $asOfDate->copy()->startOfYear(); // Assuming fiscal year starts Jan 1st

        // 1. Calculate balances for all real accounts up to the report date
        $accountBalances = $this->getAccountBalances($company, $asOfDate);

        // 2. Calculate current year earnings (Income - Expenses) for the period
        $currentYearEarnings = $this->getCurrentYearEarnings($company, $fiscalYearStart, $asOfDate);

        // 3. Get account models to access translated names
        $accountIds = $accountBalances->pluck('account_id')->unique();
        $accounts = $company->accounts()->whereIn('id', $accountIds)->get()->keyBy('id');

        // 4. Process and assemble the DTO
        $assetLines = $this->mapBalancesToReportLines($accountBalances, AccountType::assetTypes(), $currency, $accounts);
        $liabilityLines = $this->mapBalancesToReportLines($accountBalances, AccountType::liabilityTypes(), $currency, $accounts, true);
        $equityLines = $this->mapBalancesToReportLines($accountBalances, AccountType::equityTypes(), $currency, $accounts, true);

        $totalAssets = $this->sumLines($assetLines, $zero);
        $totalLiabilities = $this->sumLines($liabilityLines, $zero);
        $retainedEarnings = $this->sumLines($equityLines, $zero); // Historical equity
        $totalEquity = $retainedEarnings->plus($currentYearEarnings);
        $totalLiabilitiesAndEquity = $totalLiabilities->plus($totalEquity);

        // Crucial accounting validation
        if (! $totalAssets->isEqualTo($totalLiabilitiesAndEquity)) {
            throw new BalanceSheetNotBalancedException(
                "Assets ({$totalAssets}) do not equal Liabilities and Equity ({$totalLiabilitiesAndEquity})."
            );
        }

        return new BalanceSheetDTO(
            assetLines: $assetLines,
            totalAssets: $totalAssets,
            liabilityLines: $liabilityLines,
            totalLiabilities: $totalLiabilities,
            equityLines: $equityLines,
            retainedEarnings: $retainedEarnings,
            currentYearEarnings: $currentYearEarnings,
            totalEquity: $totalEquity,
            totalLiabilitiesAndEquity: $totalLiabilitiesAndEquity
        );
    }

    /**
     * @return Collection<int, object>
     */
    private function getAccountBalances(Company $company, Carbon $asOfDate): Collection
    {
        return JournalEntryLine::query()
            ->select([
                'accounts.id as account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) as balance'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->whereIn('accounts.type', array_map(fn ($type) => $type->value, AccountType::balanceSheetTypes()))
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->where('journal_entries.entry_date', '<=', $asOfDate->toDateString())
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->havingRaw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) != 0')
            ->get();
    }

    private function getCurrentYearEarnings(Company $company, Carbon $fiscalYearStart, Carbon $asOfDate): Money
    {
        $balances = JournalEntryLine::query()
            ->select([
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) as balance'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->whereIn('accounts.type', [
                AccountType::Income->value,
                AccountType::OtherIncome->value,
                AccountType::Expense->value,
                AccountType::Depreciation->value,
                AccountType::CostOfRevenue->value,
            ])
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->whereBetween('journal_entries.entry_date', [$fiscalYearStart, $asOfDate])
            ->groupBy('accounts.type')
            ->pluck('balance', 'account_type');

        // Calculate total revenue (Income accounts have credit nature, so we negate)
        $totalRevenue = Money::ofMinor(
            -$balances->get(AccountType::Income->value, 0) - $balances->get(AccountType::OtherIncome->value, 0),
            $company->currency->code
        );

        // Calculate total expenses (Expense accounts have debit nature)
        $totalExpenses = Money::ofMinor(
            $balances->get(AccountType::Expense->value, 0) +
            $balances->get(AccountType::Depreciation->value, 0) +
            $balances->get(AccountType::CostOfRevenue->value, 0),
            $company->currency->code
        );

        return $totalRevenue->minus($totalExpenses);
    }

    /**
     * @param  Collection<int, object>  $balances
     * @param  array<AccountType>  $types
     * @param  Collection<int, Account>  $accounts
     * @return Collection<int, object>
     */
    private function mapBalancesToReportLines(Collection $balances, array $types, string $currency, Collection $accounts, bool $negate = false): Collection
    {
        return $balances->whereIn('account_type', array_map(fn ($type) => $type->value, $types))
            ->map(function ($row) use ($currency, $accounts, $negate) {
                $balance = Money::ofMinor($row->balance, $currency);
                $account = $accounts->get($row->account_id);

                return new ReportLineDTO(
                    accountId: $row->account_id,
                    accountCode: $row->account_code,
                    accountName: $account ? $account->name : $row->account_name,
                    balance: $negate ? $balance->negated() : $balance
                );
            })->values();
    }

    /**
     * @param  Collection<int, ReportLineDTO>  $lines
     */
    private function sumLines(Collection $lines, Money $zero): Money
    {
        return $lines->reduce(
            fn (Money $carry, ReportLineDTO $line) => $carry->plus($line->balance),
            $zero
        );
    }
}
