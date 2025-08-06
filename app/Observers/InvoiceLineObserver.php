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
        // NO LONGER NEEDED.
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
