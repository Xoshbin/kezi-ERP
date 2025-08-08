<?php

namespace App\Observers;

use App\Models\InvoiceLine;

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
        $invoice = $invoiceLine->invoice;
        if ($invoice) {
            $invoice->calculateTotalsFromLines();
            $invoice->saveQuietly();
        }
    }
}
