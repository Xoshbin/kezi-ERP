<?php

namespace App\Services;

use App\Exceptions\UpdateNotAllowedException;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function update(Invoice $invoice, array $data): bool
    {
        // Guard Clause: Never allow updating a posted invoice.
        if ($invoice->status !== 'Draft') {
            throw new UpdateNotAllowedException('Cannot modify a non-draft invoice.');
        }

        return DB::transaction(function () use ($invoice, $data) {
            // If new lines are provided, replace the old ones.
            if (isset($data['lines'])) {
                $invoice->invoiceLines()->delete();
                $invoice->invoiceLines()->createMany($data['lines']);
            }

            // Always recalculate totals to ensure they are correct.
            $this->recalculateInvoiceTotals($invoice);

            // Update other fields like description, due_date, etc.
            return $invoice->update(
                collect($data)->except('lines')->all()
            );
        });
    }

    public function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $invoice->load('invoiceLines'); // Ensure lines are loaded

        $subtotal = $invoice->invoiceLines->sum('subtotal'); // Assuming subtotal is calculated on the line
        $tax = $invoice->invoiceLines->sum('total_line_tax'); // Assuming tax is calculated on the line

        $invoice->total_amount = $subtotal + $tax;
        $invoice->total_tax = $tax;
    }
}
