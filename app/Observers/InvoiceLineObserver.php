<?php

namespace App\Observers;

use App\Models\InvoiceLine;
use Brick\Money\Money;
use App\Models\Tax;

class InvoiceLineObserver
{

    /**
     * Handle the InvoiceLine "creating" event.
     */
    /**
     * Handle the InvoiceLine "creating" event.
     */
    public function creating(InvoiceLine $invoiceLine): void
    {
        // --- START OF FIX ---

        // If a product is associated, ensure its details are correctly used.
        if ($invoiceLine->product_id) {
            // Explicitly load the product relationship to prevent lazy-loading issues.
            $invoiceLine->loadMissing('product');

            if ($invoiceLine->product) {
                // If an income account isn't manually specified on the line,
                // default to the one on the product. This is the crucial part.
                if (!$invoiceLine->income_account_id) {
                    $invoiceLine->income_account_id = $invoiceLine->product->income_account_id;
                }

                // If a description isn't manually specified, default to the product's name.
                if (!$invoiceLine->description) {
                    $invoiceLine->description = $invoiceLine->product->name;
                }
            }
        }

        // --- END OF FIX ---

        // The MoneyCast has already converted unit_price to a Money object.
        // We must perform all calculations with Money objects.
        $subtotal = $invoiceLine->unit_price->multipliedBy($invoiceLine->quantity);
        $invoiceLine->subtotal = $subtotal;

        // Calculate the tax amount as a Money object.
        $currency = $invoiceLine->unit_price->getCurrency();
        $taxAmount = Money::of(0, $currency);

        if ($invoiceLine->tax_id) {
            $tax = Tax::find($invoiceLine->tax_id);
            if ($tax) {
                // Use the 'rate' float directly as the multiplier.
                $taxAmount = $subtotal->multipliedBy($tax->rate);
            }
        }

        $invoiceLine->total_line_tax = $taxAmount;
    }

    /**
     * Handle the InvoiceLine "created" event.
     */
    public function created(InvoiceLine $invoiceLine): void
    {
        $invoiceLine->invoice->calculateTotalsFromLines();
        $invoiceLine->invoice->saveQuietly();

    }

    /**
     * Handle the InvoiceLine "updated" event.
     */
    public function updated(InvoiceLine $invoiceLine): void
    {
        $invoiceLine->invoice->calculateTotalsFromLines();
        $invoiceLine->invoice->saveQuietly();
    }

    /**
     * Handle the InvoiceLine "deleted" event.
     */
    public function deleted(InvoiceLine $invoiceLine): void
    {
        // We need to load the relationship from the model before it's fully deleted
        $invoice = $invoiceLine->invoice()->first();
        if ($invoice) {
            $invoice->calculateTotalsFromLines();
            $invoice->saveQuietly();
        }
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
