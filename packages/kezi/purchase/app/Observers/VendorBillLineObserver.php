<?php

namespace Kezi\Purchase\Observers;

use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;

class VendorBillLineObserver
{
    /**
     * Handle the VendorBillLine "saving" event.
     */
    public function saving(VendorBillLine $vendorBillLine): void
    {
        $this->applyFiscalPositionMapping($vendorBillLine);
    }

    protected function applyFiscalPositionMapping(VendorBillLine $vendorBillLine): void
    {
        $vendorBill = $vendorBillLine->vendorBill;
        if (! $vendorBill || ! $vendorBill->fiscal_position_id) {
            return;
        }

        $fiscalPositionService = app(\Kezi\Accounting\Services\Accounting\FiscalPositionService::class);
        $fiscalPosition = $vendorBill->fiscalPosition;

        // Map Tax
        if ($vendorBillLine->tax_id) {
            $mappedTax = $fiscalPositionService->mapTax($fiscalPosition, $vendorBillLine->tax);
            $vendorBillLine->tax_id = $mappedTax->id;
        }

        // Map Expense Account
        if ($vendorBillLine->expense_account_id) {
            $mappedAccount = $fiscalPositionService->mapAccount($fiscalPosition, $vendorBillLine->expenseAccount);
            $vendorBillLine->expense_account_id = $mappedAccount->id;
        }
    }

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

        $vendorBill->save();
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

        // Convert total amounts using the stored exchange rate via the CurrencyConverterService
        $currencyConverter = app(\Kezi\Foundation\Services\CurrencyConverterService::class);
        $totalAmountCompanyCurrency = $currencyConverter->convertWithRate(
            $vendorBill->total_amount,
            $exchangeRate,
            $companyCurrency->code,
            false
        );
        $totalTaxCompanyCurrency = $currencyConverter->convertWithRate(
            $vendorBill->total_tax,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        $vendorBill->update([
            'total_amount_company_currency' => $totalAmountCompanyCurrency,
            'total_tax_company_currency' => $totalTaxCompanyCurrency,
        ]);
    }
}
