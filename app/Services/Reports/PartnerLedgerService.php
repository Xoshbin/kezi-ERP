<?php

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\PartnerLedgerDTO;
use App\DataTransferObjects\Reports\PartnerLedgerTransactionLineDTO;

use App\Models\Company;
use App\Models\JournalEntryLine;
use App\Models\Partner;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PartnerLedgerService
{
    public function generate(Company $company, Partner $partner, Carbon $startDate, Carbon $endDate): PartnerLedgerDTO
    {
        // Prerequisite: Ensure the partner is correctly configured.
        if (is_null($partner->receivable_account_id) || is_null($partner->payable_account_id)) {
            throw new InvalidArgumentException("Partner {$partner->name} does not have assigned receivable/payable accounts.");
        }

        $currency = $company->currency->code;
        $partnerAccountIds = [$partner->receivable_account_id, $partner->payable_account_id];

        $openingBalance = $this->getOpeningBalance($partnerAccountIds, $startDate, $currency);
        $transactions = $this->getTransactionsForPeriod($partnerAccountIds, $startDate, $endDate);

        $runningBalance = $openingBalance;
        $transactionLines = new Collection();

        foreach ($transactions as $line) {
            $debit = $line->debit; // Already a Money object due to MoneyCast
            $credit = $line->credit; // Already a Money object due to MoneyCast
            $runningBalance = $runningBalance->plus($debit)->minus($credit);

            $transactionLines->push(new PartnerLedgerTransactionLineDTO(
                date: $line->journalEntry->entry_date,
                reference: $line->journalEntry->reference,
                transactionType: $this->getTransactionType($line),
                debit: $debit,
                credit: $credit,
                balance: $runningBalance
            ));
        }

        return new PartnerLedgerDTO(
            partnerId: $partner->id,
            partnerName: $partner->name,
            currency: $currency,
            openingBalance: $openingBalance,
            transactionLines: $transactionLines,
            closingBalance: $runningBalance
        );
    }

    private function getOpeningBalance(array $accountIds, Carbon $startDate, string $currency): Money
    {
        $balance = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.state', 'posted')
            ->where('journal_entries.entry_date', '<', $startDate->toDateString())
            ->sum(DB::raw('journal_entry_lines.debit - journal_entry_lines.credit'));

        return Money::ofMinor($balance ?: 0, $currency);
    }

    private function getTransactionsForPeriod(array $accountIds, Carbon $startDate, Carbon $endDate): Collection
    {
        return JournalEntryLine::query()
            ->with(['journalEntry' => fn($q) => $q->with('journal')]) // Eager load for type lookup
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.state', 'posted')
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->select('journal_entry_lines.*') // Select only the line columns to avoid conflicts
            ->get();
    }

    private function getTransactionType(JournalEntryLine $line): string
    {
        // Derive a business-friendly name from the journal's type.
        return match ($line->journalEntry->journal->type->value) {
            'sale' => 'Invoice',
            'purchase' => 'Vendor Bill',
            'bank' => 'Payment',
            default => 'Journal Entry',
        };
    }
}
