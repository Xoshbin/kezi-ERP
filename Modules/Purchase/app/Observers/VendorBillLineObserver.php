<?php

namespace Modules\Purchase\Observers;

use Brick\Money\Money;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;

class VendorBillLineObserver
{
    /**
     * Handle the VendorBillLine "saved" event.
     * This is triggered on both creation and update.
     */
    public function saved(VendorBillLine $vendorBillLine): void
    {
        $this->updateParentVendorBillTotals($vendorBillLine);
    }

    /**
     * Handle the VendorBillLine "deleted" event.
     */
    public function deleted(VendorBillLine $vendorBillLine): void
    {
        $this->updateParentVendorBillTotals($vendorBillLine);
    }

    /**
     * Recalculate and save the totals on the parent VendorBill.
     */
    protected function updateParentVendorBillTotals(VendorBillLine $vendorBillLine): void
    {
        // The vendorBill relationship is guaranteed to exist due to foreign key constraints
        // with cascadeOnDelete, so we can safely access it without null checks
        $vendorBill = $vendorBillLine->vendorBill;
        $vendorBill->calculateTotalsFromLines();

        // Also update company currency totals if exchange rate is available
        if ($vendorBill->exchange_rate_at_creation) {
            $this->updateCompanyCurrencyTotals($vendorBill);
        }

        $vendorBill->saveQuietly();
    }

    /**
     * Update company currency totals based on current line totals and exchange rate.
     */
    protected function updateCompanyCurrencyTotals(VendorBill $vendorBill): void
    {
        if (! $vendorBill->exchange_rate_at_creation || $vendorBill->currency_id === $vendorBill->company->currency_id) {
            return; // No conversion needed
        }

        $companyCurrency = $vendorBill->company->currency;
        $exchangeRate = $vendorBill->exchange_rate_at_creation;

        // Convert total amounts using the stored exchange rate
        $totalAmountCompanyCurrency = $vendorBill->total_amount->getAmount()->toFloat() * $exchangeRate;
        $totalTaxCompanyCurrency = $vendorBill->total_tax->getAmount()->toFloat() * $exchangeRate;

        $vendorBill->update([
            'total_amount_company_currency' => Money::of($totalAmountCompanyCurrency, $companyCurrency->code),
            'total_tax_company_currency' => Money::of($totalTaxCompanyCurrency, $companyCurrency->code),
        ]);
    }
}
