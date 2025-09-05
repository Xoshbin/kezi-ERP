<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\VendorBill;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ExchangeGainLossService
 *
 * Handles calculation and posting of exchange gains and losses
 * for multi-currency transactions and period-end revaluations.
 */
class ExchangeGainLossService
{
    protected CurrencyConverterService $currencyConverter;

    protected JournalEntryService $journalEntryService;

    public function __construct(
        CurrencyConverterService $currencyConverter,
        JournalEntryService $journalEntryService
    ) {
        $this->currencyConverter = $currencyConverter;
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * Calculate and post realized exchange gain/loss for a payment reconciliation.
     *
     * @param  Payment  $payment
     * @param  Invoice|VendorBill  $document
     */
    public function processRealizedGainLoss($payment, $document, Money $amountApplied): ?JournalEntry
    {
        // Only process if currencies match but rates differ
        if ($payment->currency_id !== $document->currency_id) {
            return null; // Different currencies - handled separately
        }

        // Skip if same as company base currency
        if ($payment->currency_id === $payment->company->currency_id) {
            return null; // No exchange difference in base currency
        }

        // Get exchange rates
        $documentRate = $this->getDocumentExchangeRate($document);
        $paymentRate = $this->getPaymentExchangeRate($payment);

        if ($documentRate === null || $paymentRate === null) {
            return null; // Cannot calculate without rates
        }

        // Calculate exchange difference
        $exchangeDifference = $this->calculateRealizedExchangeDifference(
            $amountApplied,
            $documentRate,
            $paymentRate,
            $payment->company->currency->code
        );

        // Post journal entry if there's a significant difference
        if (abs($exchangeDifference->getAmount()->toFloat()) >= 0.01) {
            return $this->postRealizedGainLossEntry(
                $payment->company,
                $exchangeDifference,
                $payment,
                $document
            );
        }

        return null;
    }

    /**
     * Perform period-end revaluation of foreign currency balances.
     *
     * @param  array<int>  $accountIds  Optional specific accounts to revalue
     * @return Collection<int, JournalEntry> Collection of journal entries created
     */
    public function performPeriodEndRevaluation(
        Company $company,
        Carbon $revaluationDate,
        array $accountIds = []
    ): Collection {
        $journalEntries = collect();

        // Get accounts with foreign currency balances
        $accountsToRevalue = $this->getAccountsForRevaluation($company, $accountIds);

        foreach ($accountsToRevalue as $account) {
            $foreignCurrencyBalances = $this->getForeignCurrencyBalances($account, $revaluationDate);

            foreach ($foreignCurrencyBalances as $currencyId => $balance) {
                $currency = Currency::find($currencyId);

                if (! $currency || $currency->id === $company->currency_id) {
                    continue; // Skip base currency
                }

                $unrealizedGainLoss = $this->calculateUnrealizedGainLoss(
                    $balance,
                    $currency,
                    $company,
                    $revaluationDate
                );

                if (abs($unrealizedGainLoss->getAmount()->toFloat()) >= 0.01) {
                    $journalEntry = $this->postUnrealizedGainLossEntry(
                        $company,
                        $account,
                        $currency,
                        $unrealizedGainLoss,
                        $revaluationDate
                    );

                    if ($journalEntry) {
                        $journalEntries->push($journalEntry);
                    }
                }
            }
        }

        return $journalEntries;
    }

    /**
     * Calculate realized exchange difference between document and payment rates.
     */
    protected function calculateRealizedExchangeDifference(
        Money $amount,
        float $documentRate,
        float $paymentRate,
        string $baseCurrencyCode
    ): Money {
        // Convert amount using both rates and find the difference
        $documentValue = $this->currencyConverter->convertWithRate(
            $amount,
            $documentRate,
            $baseCurrencyCode,
            false
        );

        $paymentValue = $this->currencyConverter->convertWithRate(
            $amount,
            $paymentRate,
            $baseCurrencyCode,
            false
        );

        return $paymentValue->minus($documentValue);
    }

    /**
     * Calculate unrealized gain/loss for a foreign currency balance.
     */
    protected function calculateUnrealizedGainLoss(
        Money $balance,
        Currency $currency,
        Company $company,
        Carbon $revaluationDate
    ): Money {
        $currentRate = $this->currencyConverter->getExchangeRate($currency, $revaluationDate, $company);

        if ($currentRate === null) {
            return Money::of(0, $company->currency->code);
        }

        // Get the current book value in base currency
        $currentBookValue = $this->currencyConverter->convertWithRate(
            $balance,
            $currentRate,
            $company->currency->code,
            false
        );

        // Get the historical book value (this would need to be tracked separately)
        // For now, we'll use the current balance converted at current rate
        // In a full implementation, you'd track the original conversion values

        return Money::of(0, $company->currency->code); // Placeholder
    }

    /**
     * Post realized gain/loss journal entry.
     */
    protected function postRealizedGainLossEntry(
        Company $company,
        Money $exchangeDifference,
        Payment $payment,
        \Illuminate\Database\Eloquent\Model $document
    ): JournalEntry {
        $isGain = $exchangeDifference->isPositive();
        $gainLossAccount = $company->default_gain_loss_account_id;

        // Determine the receivable/payable account
        $balanceAccount = $document instanceof Invoice
            ? $company->default_accounts_receivable_id
            : $company->default_accounts_payable_id;

        $lines = [];

        if ($isGain) {
            // Debit Receivable/Payable, Credit Exchange Gain
            $lines[] = [
                'account_id' => $balanceAccount,
                'debit' => abs($exchangeDifference->getAmount()->toFloat()),
                'credit' => 0,
                'description' => 'Realized exchange gain on payment',
            ];
            $lines[] = [
                'account_id' => $gainLossAccount,
                'debit' => 0,
                'credit' => abs($exchangeDifference->getAmount()->toFloat()),
                'description' => 'Realized exchange gain',
            ];
        } else {
            // Debit Exchange Loss, Credit Receivable/Payable
            $lines[] = [
                'account_id' => $gainLossAccount,
                'debit' => abs($exchangeDifference->getAmount()->toFloat()),
                'credit' => 0,
                'description' => 'Realized exchange loss',
            ];
            $lines[] = [
                'account_id' => $balanceAccount,
                'debit' => 0,
                'credit' => abs($exchangeDifference->getAmount()->toFloat()),
                'description' => 'Realized exchange loss on payment',
            ];
        }

        // Build DTO-based journal entry in base currency
        $baseCurrencyCode = $company->currency->code;
        $lineDTOs = [];
        foreach ($lines as $line) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $line['account_id'],
                debit: Money::of($line['debit'], $baseCurrencyCode),
                credit: Money::of($line['credit'], $baseCurrencyCode),
                description: $line['description'],
                partner_id: null,
                analytic_account_id: null,
            );
        }

        $entryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $company->default_bank_journal_id,
            currency_id: $company->currency_id,
            entry_date: $payment->payment_date->toDateString(),
            reference: "EX-GAIN-LOSS-{$payment->getKey()}",
            description: 'Realized exchange '.($isGain ? 'gain' : 'loss')." on payment #{$payment->getKey()}",
            created_by_user_id: $payment->created_by_user_id ?? $payment->user_id ?? optional($payment->company->users()->first())->getKey() ?? 1,
            is_posted: true,
            lines: $lineDTOs,
            source_type: get_class($payment),
            source_id: $payment->getKey(),
        );

        return app(CreateJournalEntryAction::class)->execute($entryDTO);
    }

    /**
     * Get exchange rate from document (invoice/vendor bill).
     */
    protected function getDocumentExchangeRate(\Illuminate\Database\Eloquent\Model $document): ?float
    {
        // This would need to be implemented based on how you store exchange rates on documents
        // For now, return null as placeholder
        return null;
    }

    /**
     * Get exchange rate from payment.
     */
    protected function getPaymentExchangeRate(Payment $payment): ?float
    {
        // This would need to be implemented based on how you store exchange rates on payments
        // For now, return null as placeholder
        return null;
    }

    /**
     * Get accounts that need revaluation.
     *
     * @param  array<int>  $accountIds
     * @return \Illuminate\Database\Eloquent\Collection<int, Account>
     */
    protected function getAccountsForRevaluation(Company $company, array $accountIds = []): Collection
    {
        $query = $company->accounts();

        if (! empty($accountIds)) {
            $query->whereIn('id', $accountIds);
        } else {
            // Typically revalue receivables, payables, and foreign currency bank accounts
            $query->whereIn('type', ['ASSET', 'LIABILITY']);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Account> */
        return $query->get();
    }

    /**
     * Get foreign currency balances for an account.
     *
     * @return array<string, mixed>
     */
    protected function getForeignCurrencyBalances(Account $account, Carbon $date): array
    {
        // This would need to be implemented to calculate balances by currency
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Post unrealized gain/loss journal entry.
     */
    protected function postUnrealizedGainLossEntry(
        Company $company,
        Account $account,
        Currency $currency,
        Money $unrealizedGainLoss,
        Carbon $revaluationDate
    ): ?JournalEntry {
        // Implementation would create journal entry for unrealized gain/loss
        // This is a placeholder
        return null;
    }
}
