<?php

namespace App\Observers;

use App\Models\VendorBillLine;

class VendorBillLineObserver
{

    /**
     * Handle the VendorBillLine "saved" event.
     * This is triggered on both creation and update.
     */
    public function saved(VendorBillLine $vendorBillLine): void
    {
        $this->updateParentVendorBillTotals($vendorBillLine);
    }

    /**
     * Handle the VendorBillLine "deleted" event.
     */
    public function deleted(VendorBillLine $vendorBillLine): void
    {
        $this->updateParentVendorBillTotals($vendorBillLine);
    }

    /**
     * Recalculate and save the totals on the parent VendorBill.
     */
    protected function updateParentVendorBillTotals(VendorBillLine $vendorBillLine): void
    {
        $vendorBill = $vendorBillLine->vendorBill;
        if ($vendorBill) {
            $vendorBill->calculateTotalsFromLines();
            $vendorBill->saveQuietly();
        }
    }
}
