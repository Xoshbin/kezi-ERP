<?php

namespace App\Observers;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\Partner;

class PartnerObserver
{
    /**
     * Handle the Partner "deleting" event.
     *
     * Prevents deletion if the partner is linked to any financial transactions.
     */
    public function deleting(Partner $partner): void
    {
        if ($partner->invoices()->exists() 
            || $partner->vendorBills()->exists() 
            || $partner->payments()->exists()
        ) {
            // Throw the exception to completely block the deletion.
            throw new DeletionNotAllowedException(
                'Cannot delete a partner with associated financial documents (invoices, bills, or payments).'
            );
        }
    }
}