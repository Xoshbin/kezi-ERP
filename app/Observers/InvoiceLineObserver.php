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
    public function creating(InvoiceLine $invoiceLine): void
    {
        // The MoneyCast has already converted unit_price to a Money object.
        // We must perform all calculations with Money objects.

        // 1. Set the income account and description from the product.
        if ($invoiceLine->product_id) {
            if (!$invoiceLine->income_account_id) {
                $invoiceLine->income_account_id = $invoiceLine->product->income_account_id;
            }
            if (!$invoiceLine->description) {
                $invoiceLine->description = $invoiceLine->product->name;
            }
        }

        // 2. Always calculate the subtotal as a Money object.
        $subtotal = $invoiceLine->unit_price->multipliedBy($invoiceLine->quantity);
        $invoiceLine->subtotal = $subtotal;

        // 3. Calculate the tax amount as a Money object.
        $currency = $invoiceLine->unit_price->getCurrency();
        $taxAmount = Money::of(0, $currency); // Initialize as a Money object.

        if ($invoiceLine->tax_id) {
            $tax = Tax::find($invoiceLine->tax_id);
            if ($tax) {
                // MODIFIED: Use the multipliedBy method. Treat the tax rate as a numeric factor.
                // We use getAmount() on the rate assuming it's also a Money object (e.g., Money::of('0.05', ...))
                $taxAmount = $subtotal->multipliedBy($tax->rate->getAmount());
            }
        }

        // MODIFIED: Set the Money object directly. The cast will handle storing the integer value.
        $invoiceLine->total_line_tax = $taxAmount;
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
