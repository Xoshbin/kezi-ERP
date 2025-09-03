<?php

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\ProfitAndLossStatementDTO;
use App\DataTransferObjects\Reports\ReportLineDTO;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryState;
use App\Models\Company;
use App\Models\JournalEntryLine;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitAndLossStatementService
{
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): ProfitAndLossStatementDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        $results = JournalEntryLine::query()
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
            ->whereIn('accounts.type', [
                AccountType::Income->value,
                AccountType::OtherIncome->value,
                AccountType::Expense->value,
                AccountType::Depreciation->value,
                AccountType::CostOfRevenue->value,
            ])
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->havingRaw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) != 0')
            ->get();

        // Get account models to access translated names
        $accountIds = $results->pluck('account_id')->unique();
        $accounts = $company->accounts()->whereIn('id', $accountIds)->get()->keyBy('id');

        $revenueLines = $results->whereIn('account_type', [
            AccountType::Income->value,
            AccountType::OtherIncome->value,
        ])
            ->map(function ($row) use ($currency, $accounts) {
                // Invert the sign for presentation, as income accounts have a natural credit balance.
                // The balance from the query is already in minor units, so use it directly
                $balance = Money::ofMinor(-$row->balance, $currency);
                $account = $accounts->get($row->account_id);

                return new ReportLineDTO(
                    accountId: $row->account_id,
                    accountCode: $row->account_code,
                    accountName: $account ? $account->name : $row->account_name,
                    balance: $balance
                );
            });

        $expenseLines = $results->whereIn('account_type', [
            AccountType::Expense->value,
            AccountType::Depreciation->value,
            AccountType::CostOfRevenue->value,
        ])
            ->map(function ($row) use ($currency, $accounts) {
                // Expense accounts have a natural debit balance, which is correct for presentation.
                // The balance from the query is already in minor units, so use it directly
                $balance = Money::ofMinor($row->balance, $currency);
                $account = $accounts->get($row->account_id);

                return new ReportLineDTO(
                    accountId: $row->account_id,
                    accountCode: $row->account_code,
                    accountName: $account ? $account->name : $row->account_name,
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
