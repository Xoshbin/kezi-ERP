<?php

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\TrialBalanceDTO;
use App\DataTransferObjects\Reports\TrialBalanceLineDTO;
use App\Enums\Accounting\AccountType;
use App\Models\Company;
use App\Models\JournalEntryLine;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    public function generate(Company $company, Carbon $asOfDate): TrialBalanceDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        /** @var \Illuminate\Support\Collection<int, object> $results */
        $results = DB::table('journal_entry_lines')
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
            ->where('journal_entries.state', 'posted')
            ->where('journal_entries.entry_date', '<=', $asOfDate->toDateString())
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->havingRaw('balance != 0')
            ->orderBy('accounts.code')
            ->get();

        $totalDebit = $zero;
        $totalCredit = $zero;

        $reportLines = $results->map(function (object $row) use ($currency, $zero, &$totalDebit, &$totalCredit) {
            /** @var object{balance: string, account_id: int, account_code: string, account_name: string, account_type: string} $row */
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

            return new TrialBalanceLineDTO(
                accountId: $row->account_id,
                accountCode: $row->account_code,
                accountName: $row->account_name,
                accountType: AccountType::from($row->account_type),
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
