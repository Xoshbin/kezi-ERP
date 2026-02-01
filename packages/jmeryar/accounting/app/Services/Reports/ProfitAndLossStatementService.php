<?php

namespace Jmeryar\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\DataTransferObjects\Reports\ProfitAndLossStatementDTO;
use Jmeryar\Accounting\DataTransferObjects\Reports\ReportLineDTO;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Support\TranslatableHelper;

class ProfitAndLossStatementService
{
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): ProfitAndLossStatementDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        /** @var Collection<int, object{account_id: int, account_code: string, account_name: string, account_type: string, total_debit: string|null, total_credit: string|null}> $queryResults */
        $queryResults = DB::table('journal_entry_lines')
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
            ->whereIn('accounts.type', [
                \Jmeryar\Accounting\Enums\Accounting\AccountType::Income->value,
                \Jmeryar\Accounting\Enums\Accounting\AccountType::OtherIncome->value,
                \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense->value,
                \Jmeryar\Accounting\Enums\Accounting\AccountType::Depreciation->value,
                \Jmeryar\Accounting\Enums\Accounting\AccountType::CostOfRevenue->value,
            ])
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->get();

        // Calculate balance in PHP and filter out zero balances
        $results = $queryResults->map(function ($result) {
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

        // Get account models to access translated names
        $accountIds = $results->pluck('account_id')->unique();
        /** @var Collection<int, Account> $accounts */
        $accounts = $company->accounts()->whereIn('id', $accountIds)->get()->keyBy('id');

        $revenueLines = $results->whereIn('account_type', [
            \Jmeryar\Accounting\Enums\Accounting\AccountType::Income->value,
            \Jmeryar\Accounting\Enums\Accounting\AccountType::OtherIncome->value,
        ])
            ->map(function (object $row) use ($currency, $accounts) {
                // Invert the sign for presentation, as income accounts have a natural credit balance.
                // The balance from the query is already in minor units, so use it directly
                $balance = Money::ofMinor(-(int) $row->balance, $currency);
                /** @var Account|null $account */
                $account = $accounts->get($row->account_id);
                $accountName = $account !== null
                    ? TranslatableHelper::getLocalizedValue($account->name)
                    : TranslatableHelper::getLocalizedValue($row->account_name);

                return new ReportLineDTO(
                    accountId: $row->account_id,
                    accountCode: $row->account_code,
                    accountName: $accountName,
                    balance: $balance
                );
            });

        $expenseLines = $results->whereIn('account_type', [
            \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense->value,
            \Jmeryar\Accounting\Enums\Accounting\AccountType::Depreciation->value,
            \Jmeryar\Accounting\Enums\Accounting\AccountType::CostOfRevenue->value,
        ])
            ->map(function (object $row) use ($currency, $accounts) {
                // Expense accounts have a natural debit balance, which is correct for presentation.
                // The balance from the query is already in minor units, so use it directly
                $balance = Money::ofMinor((int) $row->balance, $currency);
                /** @var Account|null $account */
                $account = $accounts->get($row->account_id);
                $accountName = $account !== null
                    ? TranslatableHelper::getLocalizedValue($account->name)
                    : TranslatableHelper::getLocalizedValue($row->account_name);

                return new ReportLineDTO(
                    accountId: $row->account_id,
                    accountCode: $row->account_code,
                    accountName: $accountName,
                    balance: $balance
                );
            });

        $totalRevenue = $revenueLines->reduce(
            fn (Money $carry, ReportLineDTO $line) => $carry->plus($line->balance),
            $zero
        );

        $totalExpenses = $expenseLines->reduce(
            fn (Money $carry, ReportLineDTO $line) => $carry->plus($line->balance),
            $zero
        );

        $netIncome = $totalRevenue->minus($totalExpenses);

        return new ProfitAndLossStatementDTO(
            revenueLines: $revenueLines->values(),
            totalRevenue: $totalRevenue,
            expenseLines: $expenseLines->values(),
            totalExpenses: $totalExpenses,
            netIncome: $netIncome
        );
    }
}
