<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\AdjustmentDocument;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;

class CreateJournalEntryForAdjustmentAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for multi-currency handling.
        $adjustment->load('company.currency', 'currency');
        $company = $adjustment->company;
        $baseCurrency = $company->currency;
        $adjustmentCurrency = $adjustment->currency;

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $adjustmentCurrency->id) ? 1.0 : $adjustmentCurrency->exchange_rate;

        // 2. Get the required default accounts from the company.
        $arAccountId = $company->default_accounts_receivable_id;
        $salesDiscountAccountId = $company->default_sales_discount_account_id;
        $taxAccountId = $company->default_tax_account_id;
        $salesJournalId = $company->default_sales_journal_id;

        if (!$arAccountId || !$salesDiscountAccountId || !$taxAccountId || !$salesJournalId) {
            throw new \RuntimeException('Default accounting accounts for adjustments are not configured for this company.');
        }

        // 3. Build the journal entry lines based on credit note accounting rules (reversing a sale).
        $lineDTOs = [];

        // Get original amounts in adjustment currency
        $totalAmountOriginal = $adjustment->total_amount;
        $totalTaxOriginal = $adjustment->total_tax;
        $subtotalOriginal = $totalAmountOriginal->minus($totalTaxOriginal);

        // Convert amounts to company base currency
        $totalAmountInBase = Money::of(
            $totalAmountOriginal->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );
        $totalTaxInBase = Money::of(
            $totalTaxOriginal->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );
        $subtotalInBase = Money::of(
            $subtotalOriginal->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );

        $zeroAmountInBase = Money::zero($baseCurrency->code);

        // Rule: DEBIT the Sales Discount/Contra-Revenue account.
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $salesDiscountAccountId,
            debit: $subtotalInBase,
            credit: $zeroAmountInBase,
            description: 'Sales Discount/Contra-Revenue',
            partner_id: null,
            analytic_account_id: null,
            original_currency_amount: $subtotalOriginal, // Original Money object
            original_currency_id: $adjustment->currency_id, // Original currency ID
            exchange_rate_at_transaction: $exchangeRate,
        );

        // Rule: DEBIT Tax Payable to reduce it (if there is tax).
        if ($totalTaxOriginal->isPositive()) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $taxAccountId,
                debit: $totalTaxInBase,
                credit: $zeroAmountInBase,
                description: 'Tax Payable',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $totalTaxOriginal, // Original Money object
                original_currency_id: $adjustment->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            );
        }

        // Rule: CREDIT Accounts Receivable to reduce the customer's debt.
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $arAccountId,
            debit: $zeroAmountInBase,
            credit: $totalAmountInBase,
            description: 'Accounts Receivable',
            partner_id: null, // Adjustment documents don't have direct partner relationships
            analytic_account_id: null,
            original_currency_amount: $totalAmountOriginal, // Original Money object
            original_currency_id: $adjustment->currency_id, // Original currency ID
            exchange_rate_at_transaction: $exchangeRate,
        );

        // 4. Prepare the data payload for the action.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $adjustment->company_id,
            currency_id: $baseCurrency->id, // Journal entry is always in company base currency
            journal_id: $salesJournalId,
            entry_date: $adjustment->posted_at,
            reference: 'CN-' . $adjustment->reference_number,
            description: 'Credit Note ' . $adjustment->reference_number,
            source_type: AdjustmentDocument::class,
            source_id: $adjustment->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs
        );

        // 5. Execute the action and return the result.
        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
