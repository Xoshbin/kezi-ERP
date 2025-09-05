<?php

namespace App\Observers;

use App\Models\AdjustmentDocumentLine;
use Brick\Money\Money;

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
        // The adjustmentDocument relationship is guaranteed to exist due to foreign key constraints,
        // but we keep this check for defensive programming
        $adjustmentDocument = $adjustmentDocumentLine->adjustmentDocument;
        if ($adjustmentDocument !== null) {
            $adjustmentDocument->calculateTotalsFromLines();

            // Also update company currency totals if exchange rate is available
            if ($adjustmentDocument->exchange_rate_at_creation) {
                $this->updateCompanyCurrencyTotals($adjustmentDocument);
            }

            $adjustmentDocument->saveQuietly();
        }
    }

    /**
     * Update company currency totals based on current line totals and exchange rate.
     */
    protected function updateCompanyCurrencyTotals(\App\Models\AdjustmentDocument $adjustmentDocument): void
    {
        if (! $adjustmentDocument->exchange_rate_at_creation || $adjustmentDocument->currency_id === $adjustmentDocument->company->currency_id) {
            return; // No conversion needed
        }

        $companyCurrency = $adjustmentDocument->company->currency;
        $exchangeRate = $adjustmentDocument->exchange_rate_at_creation;

        // Convert total amounts using the stored exchange rate
        $subtotalCompanyCurrency = $adjustmentDocument->subtotal->getAmount()->toFloat() * $exchangeRate;
        $totalAmountCompanyCurrency = $adjustmentDocument->total_amount->getAmount()->toFloat() * $exchangeRate;
        $totalTaxCompanyCurrency = $adjustmentDocument->total_tax->getAmount()->toFloat() * $exchangeRate;

        $adjustmentDocument->update([
            'subtotal_company_currency' => Money::of($subtotalCompanyCurrency, $companyCurrency->code),
            'total_amount_company_currency' => Money::of($totalAmountCompanyCurrency, $companyCurrency->code),
            'total_tax_company_currency' => Money::of($totalTaxCompanyCurrency, $companyCurrency->code),
        ]);
    }
}
