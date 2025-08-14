<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\AdjustmentDocument;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\CurrencyConverterService;

class CreateJournalEntryForAdjustmentAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CurrencyConverterService $currencyConverter
    ) {
    }

    public function execute(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for multi-currency handling.
        $adjustment->load('company.currency', 'currency');
        $company = $adjustment->company;

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

        // Use CurrencyConverterService for all currency conversion logic
        $totalAmountConversion = $this->currencyConverter->convertToCompanyBaseCurrency(
            $adjustment->total_amount,
            $adjustment->currency,
            $company
        );

        $totalTaxConversion = $this->currencyConverter->convertToCompanyBaseCurrency(
            $adjustment->total_tax,
            $adjustment->currency,
            $company
        );

        $subtotalOriginal = $adjustment->total_amount->minus($adjustment->total_tax);
        $subtotalConversion = $this->currencyConverter->convertToCompanyBaseCurrency(
            $subtotalOriginal,
            $adjustment->currency,
            $company
        );

        $zeroAmountInBase = $totalAmountConversion->createZeroInTargetCurrency();

        // Rule: DEBIT the Sales Discount/Contra-Revenue account.
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $salesDiscountAccountId,
            debit: $subtotalConversion->convertedAmount,
            credit: $zeroAmountInBase,
            description: 'Sales Discount/Contra-Revenue',
            partner_id: null,
            analytic_account_id: null,
            original_currency_amount: $subtotalConversion->originalAmount,
            original_currency_id: $subtotalConversion->originalCurrency->id,
            exchange_rate_at_transaction: $subtotalConversion->exchangeRate,
        );

        // Rule: DEBIT Tax Payable to reduce it (if there is tax).
        if ($adjustment->total_tax->isPositive()) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $taxAccountId,
                debit: $totalTaxConversion->convertedAmount,
                credit: $zeroAmountInBase,
                description: 'Tax Payable',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $totalTaxConversion->originalAmount,
                original_currency_id: $totalTaxConversion->originalCurrency->id,
                exchange_rate_at_transaction: $totalTaxConversion->exchangeRate,
            );
        }

        // Rule: CREDIT Accounts Receivable to reduce the customer's debt.
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $arAccountId,
            debit: $zeroAmountInBase,
            credit: $totalAmountConversion->convertedAmount,
            description: 'Accounts Receivable',
            partner_id: null, // Adjustment documents don't have direct partner relationships
            analytic_account_id: null,
            original_currency_amount: $totalAmountConversion->originalAmount,
            original_currency_id: $totalAmountConversion->originalCurrency->id,
            exchange_rate_at_transaction: $totalAmountConversion->exchangeRate,
        );

        // 4. Prepare the data payload for the action.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $adjustment->company_id,
            currency_id: $totalAmountConversion->targetCurrency->id, // Journal entry is always in company base currency
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
