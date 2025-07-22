<?php

namespace App\Observers;

use App\Models\VendorBillLine;

class VendorBillLineObserver
{
    /**
     * Handle the VendorBillLine "creating" event.
     */
    public function creating(VendorBillLine $vendorBillLine): void
    {
        // 1. Calculate the subtotal as an integer.
        $subtotal = $vendorBillLine->quantity * $vendorBillLine->unit_price;
        $vendorBillLine->subtotal = $subtotal;

        // 2. Calculate the tax amount as an integer.
        $taxAmount = 0;
        if ($vendorBillLine->tax_id) {
            $tax = \App\Models\Tax::find($vendorBillLine->tax_id);
            if ($tax) {
                $taxAmount = ($subtotal * $tax->rate);
            }
        }
        $vendorBillLine->total_line_tax = round($taxAmount);
    }

    /**
     * Handle the VendorBillLine "created" event.
     */
    public function created(VendorBillLine $vendorBillLine): void
    {
        //
    }

    /**
     * Handle the VendorBillLine "updated" event.
     */
    public function updated(VendorBillLine $vendorBillLine): void
    {
        //
    }

    /**
     * Handle the VendorBillLine "deleted" event.
     */
    public function deleted(VendorBillLine $vendorBillLine): void
    {
        //
    }

    /**
     * Handle the VendorBillLine "restored" event.
     */
    public function restored(VendorBillLine $vendorBillLine): void
    {
        //
    }

    /**
     * Handle the VendorBillLine "force deleted" event.
     */
    public function forceDeleted(VendorBillLine $vendorBillLine): void
    {
        //
    }
}
