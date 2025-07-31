<?php

namespace App\Observers;

use App\Models\Tax;

class TaxObserver
{
    /**
     * Handle the Tax "deleting" event.
     * If the tax has been used, it deactivates it and cancels the deletion.
     *
     * @return bool Returns false to cancel the deletion operation.
     */
    public function deleting(Tax $tax): bool
    {
        if ($tax->invoiceLines()->exists() || $tax->vendorBillLines()->exists()) {
            $tax->is_active = false;
            $tax->save();

            // Return false to prevent the actual deletion from the database.
            return false;
        }

        // If the tax was never used, allow it to be deleted.
        return true;
    }
}