<?php

namespace App\Observers;

use App\Models\VendorBill;

class VendorBillObserver
{
    /**
     * Handle the VendorBill "updated" event.
     */
    public function updated(VendorBill $vendorBill): void
    {
        // NOTE: Inventory processing is now handled by ProcessInventoryForConfirmedBill listener
        // to avoid duplicate processing and ensure proper multi-currency handling.

        // Only trigger when the status is first changed to 'posted'.
        // if ($vendorBill->wasChanged('status') && $vendorBill->status === VendorBillStatus::Posted) {
        //     foreach ($vendorBill->lines as $line) {
        //         // Only process lines with storable products.
        //         if ($line->product?->type === ProductType::Storable) {
        //             $this->processStorableProductLine($vendorBill, $line);
        //         }
        //     }
        // }
    }

    // NOTE: This method is no longer used. Inventory processing is now handled by
    // ProcessInventoryForConfirmedBill listener to ensure proper multi-currency handling.
    /*
    public function processStorableProductLine(VendorBill $vendorBill, $line): void
    {
        // ... method implementation commented out ...
    }
    */
}
