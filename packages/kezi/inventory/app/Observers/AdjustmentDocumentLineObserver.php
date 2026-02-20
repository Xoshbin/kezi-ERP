<?php

namespace Kezi\Inventory\Observers;

use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Inventory\Models\AdjustmentDocumentLine;

class AdjustmentDocumentLineObserver
{
    /**
     * Handle the AdjustmentDocumentLine "saved" event.
     * This is triggered on both creation and update.
     */
    public function saved(AdjustmentDocumentLine $adjustmentDocumentLine): void
    {
        $this->updateParentAdjustmentDocumentTotals($adjustmentDocumentLine);
    }

    /**
     * Handle the AdjustmentDocumentLine "deleted" event.
     */
    public function deleted(AdjustmentDocumentLine $adjustmentDocumentLine): void
    {
        $this->updateParentAdjustmentDocumentTotals($adjustmentDocumentLine);
    }

    /**
     * Recalculate and save the totals on the parent AdjustmentDocument.
     */
    protected function updateParentAdjustmentDocumentTotals(AdjustmentDocumentLine $adjustmentDocumentLine): void
    {
        // The adjustmentDocument relationship is guaranteed to exist due to foreign key constraints
        // with cascadeOnDelete, so we can safely access it without null checks
        $adjustmentDocument = $adjustmentDocumentLine->adjustmentDocument;
        $adjustmentDocument->calculateTotalsFromLines();

        // Also update company currency totals if exchange rate is available
        if ($adjustmentDocument->exchange_rate_at_creation) {
            $this->updateCompanyCurrencyTotals($adjustmentDocument);
        }

        $adjustmentDocument->saveQuietly();
    }

    /**
     * Update company currency totals based on current line totals and exchange rate.
     */
    protected function updateCompanyCurrencyTotals(AdjustmentDocument $adjustmentDocument): void
    {
        if (! $adjustmentDocument->exchange_rate_at_creation || $adjustmentDocument->currency_id === $adjustmentDocument->company->currency_id) {
            return; // No conversion needed
        }

        $companyCurrency = $adjustmentDocument->company->currency;
        $exchangeRate = $adjustmentDocument->exchange_rate_at_creation;

        // Convert total amounts using the stored exchange rate via the CurrencyConverterService
        $currencyConverter = app(\Kezi\Foundation\Services\CurrencyConverterService::class);
        $subtotalCompanyCurrency = $currencyConverter->convertWithRate($adjustmentDocument->subtotal, $exchangeRate, $companyCurrency->code, false);
        $totalAmountCompanyCurrency = $currencyConverter->convertWithRate($adjustmentDocument->total_amount, $exchangeRate, $companyCurrency->code, false);
        $totalTaxCompanyCurrency = $currencyConverter->convertWithRate($adjustmentDocument->total_tax, $exchangeRate, $companyCurrency->code, false);

        $adjustmentDocument->update([
            'subtotal_company_currency' => $subtotalCompanyCurrency,
            'total_amount_company_currency' => $totalAmountCompanyCurrency,
            'total_tax_company_currency' => $totalTaxCompanyCurrency,
        ]);
    }
}
