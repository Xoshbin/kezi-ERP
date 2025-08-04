<?php

namespace App\Observers;

use App\Models\VendorBillLine;

class VendorBillLineObserver
{
    // The "creating" method is no longer needed here, as the Action handles initial calculation.
    // The "updating" method could still be used if you allow lines to be edited.

    /**
     * Handle the "saved" event (after creation or update).
     * This triggers the parent VendorBill to update its own totals.
     */
    public function saved(VendorBillLine $vendorBillLine): void
    {
        $vendorBillLine->vendorBill->calculateTotalsFromLines();
        $vendorBillLine->vendorBill->saveQuietly();
    }

    /**
     * Handle the "deleted" event.
     * This also triggers the parent VendorBill to update its totals.
     */
    public function deleted(VendorBillLine $vendorBillLine): void
    {
        $vendorBillLine->vendorBill->calculateTotalsFromLines();
        $vendorBillLine->vendorBill->saveQuietly();
    }
}
