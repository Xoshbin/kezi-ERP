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
        // Automatically calculate the subtotal before saving.
        // You can add tax calculation here later as well.
        // Ensure subtotal is set first
        if (is_null($invoiceLine->subtotal) && $invoiceLine->quantity && $invoiceLine->unit_price) {
            $invoiceLine->subtotal = $invoiceLine->quantity * $invoiceLine->unit_price;
        }

        // Now, calculate the tax for the line
        $taxAmount = 0.00;
        // Check if a tax ID was provided for the line
        if ($invoiceLine->tax_id) {
            // Load the tax relationship to get the rate
            $tax = \App\Models\Tax::find($invoiceLine->tax_id);
            if ($tax) {
                $taxAmount = $invoiceLine->subtotal * $tax->rate;
            }
        }

        // Set the total_line_tax, which will be 0.00 if no tax was applied
        $invoiceLine->total_line_tax = round($taxAmount, 2);
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
