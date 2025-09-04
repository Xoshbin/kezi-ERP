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

        // Load account information to understand account types
        $partner->load(['receivableAccount', 'payableAccount']);

        // Get transactions for both accounts but process them separately to maintain context
        $receivableTransactions = $this->getTransactionsForAccount($partner->receivable_account_id, $startDate, $endDate);
        $payableTransactions = $this->getTransactionsForAccount($partner->payable_account_id, $startDate, $endDate);

        // Calculate opening balances for each account type
        $receivableOpeningBalance = $this->getAccountOpeningBalance($partner->receivable_account_id, $startDate, $currency);
        $payableOpeningBalance = $this->getAccountOpeningBalance($partner->payable_account_id, $startDate, $currency);

        // Combine and sort all transactions by date, then by journal entry id for stability
        $allTransactions = $receivableTransactions->concat($payableTransactions)
            ->sortBy(function (JournalEntryLine $l) {
                $date = $l->journalEntry->entry_date;
                $ymd = $date instanceof \Carbon\CarbonInterface
                    ? $date->format('Ymd')
                    : (is_string($date) ? date('Ymd', strtotime($date)) : '00000000');

                return sprintf('%s:%010d', $ymd, (int) $l->journalEntry->getKey());
            });

        // Calculate the partner's opening balance (receivable balance - payable balance)
        // For partner ledger: positive = partner owes us (customer) or we owe partner (vendor)
        $openingBalance = $this->calculatePartnerBalance($receivableOpeningBalance, $payableOpeningBalance);

        $runningBalance = $openingBalance;
        $transactionLines = new Collection;

        foreach ($allTransactions as $line) {
            $debit = $line->debit;
            $credit = $line->credit;

            // Determine if this is a receivable or payable account transaction
            $isReceivableAccount = $line->account_id === $partner->receivable_account_id;

            // Update running balance based on account type and partner context
            $runningBalance = $this->updatePartnerBalance($runningBalance, $debit, $credit, $isReceivableAccount);

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

    private function getAccountOpeningBalance(int $accountId, Carbon $startDate, string $currency): Money
    {
        $balance = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.state', 'posted')
            ->where('journal_entries.entry_date', '<', $startDate->toDateString())
            ->sum(DB::raw('journal_entry_lines.debit - journal_entry_lines.credit'));

        return Money::ofMinor($balance ?: 0, $currency);
    }

    private function getTransactionsForAccount(int $accountId, Carbon $startDate, Carbon $endDate): Collection
    {
        return JournalEntryLine::query()
            ->with(['journalEntry' => fn ($q) => $q->with('journal')]) // Eager load for type lookup
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.state', 'posted')
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->select('journal_entry_lines.*') // Select only the line columns to avoid conflicts
            ->get();
    }

    /**
     * Calculate the partner's opening balance by combining receivable and payable balances.
     *
     * For partner ledger interpretation:
     * - Receivable balance (asset): positive = customer owes us
     * - Payable balance (liability): positive = we owe vendor
     * - Combined: receivable - payable = net amount partner owes us (if positive) or we owe partner (if negative)
     */
    private function calculatePartnerBalance(Money $receivableBalance, Money $payableBalance): Money
    {
        // Net partner balance = receivable balance - payable balance
        // Positive = partner owes us (customer scenario)
        // Negative = we owe partner (vendor scenario)
        return $receivableBalance->minus($payableBalance);
    }

    /**
     * Update the running partner balance based on the transaction and account type.
     */
    private function updatePartnerBalance(Money $currentBalance, Money $debit, Money $credit, bool $isReceivableAccount): Money
    {
        if ($isReceivableAccount) {
            // Receivable account: debit increases what partner owes us, credit decreases it
            return $currentBalance->plus($debit)->minus($credit);
        } else {
            // Payable account: credit increases what we owe partner, debit decreases it
            // Since partner balance = receivable - payable, payable changes are subtracted
            return $currentBalance->minus($debit)->plus($credit);
        }
    }

    private function getTransactionType(JournalEntryLine $line): string
    {
        // Derive a business-friendly name from the journal's type.
        $type = $line->journalEntry->journal->type;
        return match ($type instanceof \App\Enums\Accounting\JournalType ? $type->value : $type) {
            'sale' => 'Invoice',
            'purchase' => 'Vendor Bill',
            'bank' => 'Payment',
            default => 'Journal Entry',
        };
    }
}
