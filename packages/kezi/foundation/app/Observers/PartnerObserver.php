<?php

namespace Kezi\Foundation\Observers;

use Kezi\Foundation\Models\Partner;

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
            throw new \Kezi\Foundation\Exceptions\DeletionNotAllowedException(
                __('foundation::exceptions.partner.in_use')
            );
        }
    }
}
