<?php

namespace App\Observers;

use App\Models\InvoiceLine;

class InvoiceLineObserver
{

    /**
     * Handle the InvoiceLine "creating" event.
     */
    public function creating(InvoiceLine $invoiceLine): void
    {
        // The MoneyCast has already converted unit_price to an integer (e.g., 50.00 -> 5000).
        // We must perform calculations with these integers.

        // 1. Always calculate the subtotal as an integer.
        $subtotal = $invoiceLine->quantity * $invoiceLine->unit_price;
        $invoiceLine->subtotal = $subtotal;

        // 2. Calculate the tax amount as an integer.
        $taxAmount = 0;
        if ($invoiceLine->tax_id) {
            $tax = \App\Models\Tax::find($invoiceLine->tax_id);
            if ($tax) {
                // Multiply first, then divide to maintain precision before rounding.
                $taxAmount = ($subtotal * $tax->rate);
            }
        }

        // Set the integer value for the total line tax.
        $invoiceLine->total_line_tax = round($taxAmount);
    }

    /**
     * Handle the InvoiceLine "created" event.
     */
    public function created(InvoiceLine $invoiceLine): void
    {
        //
    }

    /**
     * Handle the InvoiceLine "updated" event.
     */
    public function updated(InvoiceLine $invoiceLine): void
    {
        //
    }

    /**
     * Handle the InvoiceLine "deleted" event.
     */
    public function deleted(InvoiceLine $invoiceLine): void
    {
        //
    }

    /**
     * Handle the InvoiceLine "restored" event.
     */
    public function restored(InvoiceLine $invoiceLine): void
    {
        //
    }

    /**
     * Handle the InvoiceLine "force deleted" event.
     */
    public function forceDeleted(InvoiceLine $invoiceLine): void
    {
        //
    }
}
