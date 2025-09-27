<?php

namespace Modules\Sales\Observers;

use App\Models\InvoiceLine;
use Brick\Money\Money;

class InvoiceLineObserver
{
    /**
     * Handle the InvoiceLine "saved" event.
     * This is triggered on both creation and update.
     */
    public function saved(InvoiceLine $invoiceLine): void
    {
        $this->updateParentInvoiceTotals($invoiceLine);
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
    protected function updateCompanyCurrencyTotals(\App\Models\Invoice $invoice): void
    {
        if (! $invoice->exchange_rate_at_creation || $invoice->currency_id === $invoice->company->currency_id) {
            return; // No conversion needed
        }

        $companyCurrency = $invoice->company->currency;
        $exchangeRate = $invoice->exchange_rate_at_creation;

        // Convert total amounts using the stored exchange rate
        $totalAmountCompanyCurrency = $invoice->total_amount->getAmount()->toFloat() * $exchangeRate;
        $totalTaxCompanyCurrency = $invoice->total_tax->getAmount()->toFloat() * $exchangeRate;

        $invoice->update([
            'total_amount_company_currency' => Money::of($totalAmountCompanyCurrency, $companyCurrency->code),
            'total_tax_company_currency' => Money::of($totalTaxCompanyCurrency, $companyCurrency->code),
        ]);
    }
}
