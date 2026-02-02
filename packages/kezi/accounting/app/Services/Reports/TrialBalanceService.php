<?php

namespace Kezi\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\DataTransferObjects\Reports\TrialBalanceDTO;
use Kezi\Accounting\DataTransferObjects\Reports\TrialBalanceLineDTO;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Support\TranslatableHelper;

class TrialBalanceService
{
    public function generate(Company $company, Carbon $asOfDate): TrialBalanceDTO
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
            ->where('journal_entries.state', 'posted')
            ->whereDate('journal_entries.entry_date', '<=', $asOfDate)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
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

        // Get account models to access translated names via HasTranslations trait
        $accountIds = $results->pluck('account_id')->unique();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Account> $accounts */
        $accounts = $company->accounts()->whereIn('id', $accountIds)->get()->keyBy('id');

        $totalDebit = $zero;
        $totalCredit = $zero;

        $reportLines = $results->map(function (object $row) use ($currency, $zero, &$totalDebit, &$totalCredit, $accounts) {
            $balance = Money::ofMinor((int) $row->balance, $currency);
            $debit = $zero;
            $credit = $zero;

            if ($balance->isPositive()) {
                $debit = $balance;
            } else {
                // Credits are negative, so we negate them to show a positive number in the credit column.
                $credit = $balance->negated();
            }

            $totalDebit = $totalDebit->plus($debit);
            $totalCredit = $totalCredit->plus($credit);

            // Get the localized account name from the Eloquent model (HasTranslations trait)
            $account = $accounts->get($row->account_id);
            $accountName = $account !== null
                ? TranslatableHelper::getLocalizedValue($account->name)
                : TranslatableHelper::getLocalizedValue($row->account_name);

            return new TrialBalanceLineDTO(
                accountId: $row->account_id,
                accountCode: $row->account_code,
                accountName: $accountName,
                accountType: \Kezi\Accounting\Enums\Accounting\AccountType::from($row->account_type),
                debit: $debit,
                credit: $credit
            );
        });

        return new TrialBalanceDTO(
            reportLines: $reportLines,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            isBalanced: $totalDebit->isEqualTo($totalCredit)
        );
    }
}
