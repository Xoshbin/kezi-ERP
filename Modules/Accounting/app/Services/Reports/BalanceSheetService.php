<?php

namespace Modules\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Reports\BalanceSheetDTO;
use Modules\Accounting\DataTransferObjects\Reports\ReportLineDTO;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Exceptions\BalanceSheetNotBalancedException;
use Modules\Accounting\Models\Account;

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
        /** @var \Illuminate\Database\Eloquent\Collection<int, Account> $accounts */
        $accounts = $company->accounts()->whereIn('id', $accountIds)->get()->keyBy('id');

        // 4. Process and assemble the DTO
        $assetLines = $this->mapBalancesToReportLines($accountBalances, \Modules\Accounting\Enums\Accounting\AccountType::assetTypes(), $currency, $accounts);
        $liabilityLines = $this->mapBalancesToReportLines($accountBalances, \Modules\Accounting\Enums\Accounting\AccountType::liabilityTypes(), $currency, $accounts, true);
        $equityLines = $this->mapBalancesToReportLines($accountBalances, \Modules\Accounting\Enums\Accounting\AccountType::equityTypes(), $currency, $accounts, true);

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

    private function getAccountBalances(Company $company, Carbon $asOfDate): Collection
    {
        /** @var Collection<int, object{account_id: int, account_code: string, account_name: string, account_type: string, total_debit: string|null, total_credit: string|null}> $results */
        $results = DB::table('journal_entry_lines')
            ->select([
                'accounts.id as account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->whereIn('accounts.type', array_map(fn ($type) => $type->value, \Modules\Accounting\Enums\Accounting\AccountType::balanceSheetTypes()))
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->where('journal_entries.entry_date', '<=', $asOfDate->toDateString())
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->get();

        // Calculate balance in PHP and filter out zero balances
        return $results->map(function ($result) {
            /** @var object{account_id: int, account_code: string, account_name: string, account_type: string, total_debit: string|null, total_credit: string|null} $result */
            $totalDebit = (int) ($result->total_debit ?: 0);
            $totalCredit = (int) ($result->total_credit ?: 0);
            $balance = $totalDebit - $totalCredit;

            return (object) [
                'account_id' => $result->account_id,
                'account_code' => $result->account_code,
                'account_name' => $result->account_name,
                'account_type' => $result->account_type,
                'total_debit' => $result->total_debit,
                'total_credit' => $result->total_credit,
                'balance' => $balance,
            ];
        })->filter(function ($result) {
            return $result->balance != 0;
        });
    }

    private function getCurrentYearEarnings(Company $company, Carbon $fiscalYearStart, Carbon $asOfDate): Money
    {
        /** @var Collection<int, object{account_type: string, total_debit: string|null, total_credit: string|null}> $results */
        $results = DB::table('journal_entry_lines')
            ->select([
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->whereIn('accounts.type', [
                \Modules\Accounting\Enums\Accounting\AccountType::Income->value,
                \Modules\Accounting\Enums\Accounting\AccountType::OtherIncome->value,
                \Modules\Accounting\Enums\Accounting\AccountType::Expense->value,
                \Modules\Accounting\Enums\Accounting\AccountType::Depreciation->value,
                \Modules\Accounting\Enums\Accounting\AccountType::CostOfRevenue->value,
            ])
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->whereBetween('journal_entries.entry_date', [$fiscalYearStart, $asOfDate])
            ->groupBy('accounts.type')
            ->get();

        // Calculate balances in PHP
        $balances = $results->mapWithKeys(function ($result) {
            /** @var object{account_type: string, total_debit: string|null, total_credit: string|null} $result */
            $totalDebit = (int) ($result->total_debit ?: 0);
            $totalCredit = (int) ($result->total_credit ?: 0);
            $balance = $totalDebit - $totalCredit;

            return [$result->account_type => $balance];
        });

        // Calculate total revenue (Income accounts have credit nature, so we negate)
        $totalRevenue = Money::ofMinor(
            -$balances->get(\Modules\Accounting\Enums\Accounting\AccountType::Income->value, 0) - $balances->get(\Modules\Accounting\Enums\Accounting\AccountType::OtherIncome->value, 0),
            $company->currency->code
        );

        // Calculate total expenses (Expense accounts have debit nature)
        $totalExpenses = Money::ofMinor(
            $balances->get(\Modules\Accounting\Enums\Accounting\AccountType::Expense->value, 0) +
                $balances->get(\Modules\Accounting\Enums\Accounting\AccountType::Depreciation->value, 0) +
                $balances->get(\Modules\Accounting\Enums\Accounting\AccountType::CostOfRevenue->value, 0),
            $company->currency->code
        );

        return $totalRevenue->minus($totalExpenses);
    }

    /**
     * @param  array<\Modules\Accounting\Enums\Accounting\AccountType>  $types
     * @param  \Illuminate\Database\Eloquent\Collection<int, Account>  $accounts
     * @return Collection<int, ReportLineDTO>
     */
    private function mapBalancesToReportLines(Collection $balances, array $types, string $currency, Collection $accounts, bool $negate = false): Collection
    {
        return $balances->whereIn('account_type', array_map(fn ($type) => $type->value, $types))
            ->map(function ($row) use ($currency, $accounts, $negate) {
                $balance = Money::ofMinor($row->balance, $currency);
                $account = $accounts->get($row->account_id);

                $accountName = $account ? (is_array($account->name) ? ($account->name['en'] ?? (empty($account->name) ? '' : (string) array_values($account->name)[0])) : (string) $account->name) : $row->account_name;

                return new ReportLineDTO(
                    accountId: $row->account_id,
                    accountCode: $row->account_code,
                    accountName: $accountName,
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
