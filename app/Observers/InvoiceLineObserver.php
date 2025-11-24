<?php

namespace App\Observers;

use App\Models\InvoiceLine;
use Brick\Money\Money;

class InvoiceLineObserver
{
    /**
     * Handle the InvoiceLine "saving" event.
     * Calculate subtotal and tax amounts before saving.
     */
    public function saving(InvoiceLine $invoiceLine): void
    {
        // Ensure invoice is loaded for currency context
        if (! $invoiceLine->relationLoaded('invoice')) {
            $invoiceLine->load('invoice.currency');
        }

        // Calculate Subtotal: Quantity * Unit Price
        if ($invoiceLine->quantity !== null && $invoiceLine->unit_price) {
            $quantity = $invoiceLine->quantity;
            // Unit Price is Money object
            $invoiceLine->subtotal = $invoiceLine->unit_price->multipliedBy($quantity, \Brick\Math\RoundingMode::HALF_UP);
        }

        // Calculate Tax: Subtotal * Rate
        if ($invoiceLine->subtotal) {
            // Initialize total_line_tax to 0 if not set
            if (! isset($invoiceLine->total_line_tax)) {
                 $invoiceLine->total_line_tax = Money::of(0, $invoiceLine->invoice->currency->code);
            }

            if ($invoiceLine->tax_id) {
                if (! $invoiceLine->relationLoaded('tax')) {
                    $invoiceLine->load('tax');
                }

                if ($invoiceLine->tax) {
                    // Tax rate is stored as decimal (e.g. 0.1500)
                    $rate = $invoiceLine->tax->rate;
                    $invoiceLine->total_line_tax = $invoiceLine->subtotal->multipliedBy($rate, \Brick\Math\RoundingMode::HALF_UP);
                }
            }
        }
    }

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
