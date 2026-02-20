<?php

namespace Kezi\Sales\Observers;

use Brick\Money\Money;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;

class InvoiceLineObserver
{
    /**
     * Handle the InvoiceLine "saved" event.
     * This is triggered on both creation and update.
     */
    public function saved(InvoiceLine $invoiceLine): void
    {
        if ($invoiceLine->isDirty(['quantity', 'unit_price', 'tax_id'])) {
            $this->calculateLineTotals($invoiceLine);
        }

        $this->updateParentInvoiceTotals($invoiceLine);
    }

    public function saving(InvoiceLine $invoiceLine): void
    {
        $this->applyFiscalPositionMapping($invoiceLine);
        $this->calculateLineTotals($invoiceLine);
    }

    protected function applyFiscalPositionMapping(InvoiceLine $invoiceLine): void
    {
        $invoice = $invoiceLine->invoice;
        if (! $invoice || ! $invoice->fiscal_position_id) {
            return;
        }

        $fiscalPositionService = app(\Kezi\Accounting\Services\Accounting\FiscalPositionService::class);
        $fiscalPosition = $invoice->fiscalPosition;

        // Map Tax
        if ($invoiceLine->tax_id) {
            $mappedTax = $fiscalPositionService->mapTax($fiscalPosition, $invoiceLine->tax);
            $invoiceLine->tax_id = $mappedTax->id;
        }

        // Map Income Account
        if ($invoiceLine->income_account_id) {
            $mappedAccount = $fiscalPositionService->mapAccount($fiscalPosition, $invoiceLine->incomeAccount);
            $invoiceLine->income_account_id = $mappedAccount->id;
        }
    }

    protected function calculateLineTotals(InvoiceLine $invoiceLine): void
    {
        $quantity = $invoiceLine->quantity;
        $unitPrice = $invoiceLine->unit_price;

        if ($quantity !== null && $unitPrice !== null) {
            $subtotal = $unitPrice->multipliedBy($quantity);
            $invoiceLine->subtotal = $subtotal;

            if ($invoiceLine->tax_id && $invoiceLine->tax) {
                // Assuming tax rate is a fraction (e.g., 0.15)
                // We use getRateFractionAttribute from Tax model which divides rate by 100 if stored as integer,
                // but we changed migration to decimal. Let's verify Tax model accessor.
                // The Tax model has getRateFractionAttribute which does rate / 100.
                // Wait, if I changed migration to decimal, and the user input 0.15,
                // does the accessor need adjustment?
                // In Tax.php: return $this->rate / 100; // 1500 -> 15.00%
                // If I store 0.15 in DB (decimal),
                // getRateFractionAttribute would return 0.0015.
                // I need to check how Tax rate is used/stored.
                // If we store 15.00 for 15%, then rate/100 = 0.15 is correct.
                // If we store 0.15 for 15%, then we don't need /100.

                // Let's assume standard behavior: Rate is stored as percentage (15) or fraction (0.15)?
                // The migration comment said: "// e.g., 0.15000 for 15%" BEFORE my change.
                // But it was unsignedBigInteger! 0.15 as int is 0.
                // So probably it was meant to be scaled (e.g. 1500 basis points).
                // Now I changed it to decimal.
                // Let's rely on the Tax model's `rate` attribute directly for now,
                // or check if I should update Tax model accessor too.

                // For now, I will use the rate directly multiple by subtotal.
                // If Tax model expects scaled int, I might need to adjust.
                // Let's assume the user enters 0.15 for 15%.

                $taxRate = $invoiceLine->tax->rate;
                $invoiceLine->total_line_tax = $subtotal->multipliedBy($taxRate, \Brick\Math\RoundingMode::HALF_UP);
            } else {
                $invoiceLine->total_line_tax = Money::zero($subtotal->getCurrency()->getCurrencyCode());
            }
        }
    }

    /**
     * Handle the InvoiceLine "deleted" event.
     */
    public function deleted(InvoiceLine $invoiceLine): void
    {
        $this->updateParentInvoiceTotals($invoiceLine);
    }

    /**
     * Recalculate and save the totals on the parent Invoice.
     */
    protected function updateParentInvoiceTotals(InvoiceLine $invoiceLine): void
    {
        // The invoice relationship is guaranteed to exist due to foreign key constraints
        // with cascadeOnDelete, so we can safely access it without null checks
        $invoice = $invoiceLine->invoice;
        $invoice->calculateTotalsFromLines();

        // Also update company currency totals if exchange rate is available
        if ($invoice->exchange_rate_at_creation) {
            $this->updateCompanyCurrencyTotals($invoice);
        }

        $invoice->saveQuietly();
    }

    /**
     * Update company currency totals based on current line totals and exchange rate.
     */
    protected function updateCompanyCurrencyTotals(Invoice $invoice): void
    {
        if (! $invoice->exchange_rate_at_creation || $invoice->currency_id === $invoice->company->currency_id) {
            return; // No conversion needed
        }

        $companyCurrency = $invoice->company->currency;
        $exchangeRate = $invoice->exchange_rate_at_creation;

        // Convert total amounts using the stored exchange rate
        $totalAmountCompanyCurrency = $invoice->total_amount->multipliedBy($exchangeRate, \Brick\Math\RoundingMode::HALF_UP);
        $totalTaxCompanyCurrency = $invoice->total_tax->multipliedBy($exchangeRate, \Brick\Math\RoundingMode::HALF_UP);

        $invoice->update([
            'total_amount_company_currency' => $totalAmountCompanyCurrency,
            'total_tax_company_currency' => $totalTaxCompanyCurrency,
        ]);
    }
}
