<?php

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\GeneralLedgerAccountDTO;
use App\DataTransferObjects\Reports\GeneralLedgerDTO;
use App\DataTransferObjects\Reports\GeneralLedgerTransactionLineDTO;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntryLine;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneralLedgerService
{
    /**
     * @param  int[]|null  $accountIds
     */
    public function generate(Company $company, Carbon $startDate, Carbon $endDate, ?array $accountIds = null): GeneralLedgerDTO
    {
        $currency = $company->currency->code;

        $accountsQuery = $company->accounts();
        if ($accountIds) {
            $accountsQuery->whereIn('id', $accountIds);
        }

        $accounts = $accountsQuery->orderBy('code')->get();
        $reportAccounts = new Collection;

        foreach ($accounts as $account) {
            $openingBalance = $this->getOpeningBalance($account, $startDate, $currency);
            $transactions = $this->getTransactionsForPeriod($account, $startDate, $endDate);

            if ($openingBalance->isZero() && $transactions->isEmpty()) {
                continue; // Skip accounts with no activity
            }

            $runningBalance = $openingBalance;
            $transactionLines = new Collection;

            foreach ($transactions as $line) {
                $debit = $line->debit; // Already a Money object due to MoneyCast
                $credit = $line->credit; // Already a Money object due to MoneyCast
                $runningBalance = $runningBalance->plus($debit)->minus($credit);

                $transactionLines->push(new GeneralLedgerTransactionLineDTO(
                    journalEntryId: $line->journal_entry_id,
                    date: $line->journalEntry->entry_date,
                    reference: $line->journalEntry->reference,
                    description: $line->journalEntry->description,
                    contraAccount: $this->getContraAccountDescription($line),
                    debit: $debit,
                    credit: $credit,
                    balance: $runningBalance
                ));
            }

            $reportAccounts->push(new GeneralLedgerAccountDTO(
                accountId: $account->id,
                accountCode: $account->code,
                accountName: $account->name,
                openingBalance: $openingBalance,
                transactionLines: $transactionLines,
                closingBalance: $runningBalance
            ));
        }

        return new GeneralLedgerDTO($reportAccounts);
    }

    private function getOpeningBalance(Account $account, Carbon $startDate, string $currency): Money
    {
        $balance = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.state', 'posted')
            ->where('journal_entries.entry_date', '<', $startDate->toDateString())
            ->sum(DB::raw('journal_entry_lines.debit - journal_entry_lines.credit'));

        return Money::ofMinor($balance ?: 0, $currency);
    }

    private function getTransactionsForPeriod(Account $account, Carbon $startDate, Carbon $endDate): Collection
    {
        return JournalEntryLine::query()
            ->with('journalEntry.lines.account') // Eager load for contra-account lookup
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.state', 'posted')
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->select('journal_entry_lines.*') // Select only the line columns to avoid conflicts
            ->get();
    }

    private function getContraAccountDescription(JournalEntryLine $line): string
    {
        // Find other lines in the same journal entry to describe the other side of the transaction.
        return $line->journalEntry->lines
            ->where('id', '!=', $line->id)
            ->map(fn ($l) => $l->account->name)
            ->implode(', ');
    }
}
