<?php

namespace App\Observers;

use App\Models\InvoiceLine;

class InvoiceLineObserver
{
    /**
     * Handle the InvoiceLine "creating" event.
     * This method is now empty as per our new pattern.
     * All calculation logic is handled by CreateInvoiceLineAction.
     */
    public function creating(InvoiceLine $invoiceLine): void
    {
        $invoiceLine->loadMissing('tax', 'invoice.currency');
        $currency = $invoiceLine->invoice->currency;

        // unit_price is already a Money object thanks to the MoneyCast
        // Calculate subtotal
        $subtotal = $invoiceLine->unit_price->multipliedBy($invoiceLine->quantity);
        $invoiceLine->subtotal = $subtotal;

        // Calculate tax
        if ($invoiceLine->tax) {
            $taxRate = $invoiceLine->tax->rate;
            $invoiceLine->total_line_tax = $subtotal->multipliedBy($taxRate);
        } else {
            $invoiceLine->total_line_tax = \Brick\Money\Money::of(0, $currency->code);
        }
    }

    /**
     * Handle the InvoiceLine "updating" event.
     * Logic for updates should also be moved to a dedicated UpdateInvoiceLineAction.
     * For now, we clear this to enforce the pattern for creation.
     */
    public function updating(InvoiceLine $invoiceLine): void
    {
        // This should be moved to an `UpdateInvoiceLineAction` in the future.
        // For now, we focus on the creation pattern.
    }


    /**
     * Handle the InvoiceLine "saved" (created or updated) event.
     * This is a side effect and is the correct responsibility for an observer.
     */
    public function saved(InvoiceLine $invoiceLine): void
    {
        $invoiceLine->invoice->calculateTotalsFromLines();
        $invoiceLine->invoice->saveQuietly();
    }

    /**
     * Handle the InvoiceLine "deleted" event.
     * This is a side effect and is the correct responsibility for an observer.
     */
    public function deleted(InvoiceLine $invoiceLine): void
    {
        $invoice = $invoiceLine->invoice()->first();
        if ($invoice) {
            $invoice->calculateTotalsFromLines();
            $invoice->saveQuietly();
        }
    }
}
