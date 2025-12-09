<?php

namespace Modules\Foundation\Observers;

use Modules\Foundation\Models\Currency;

class CurrencyObserver
{
    /**
     * Handle the Currency "deleting" event.
     * Prevents deletion if the currency is linked to a company or journal entry.
     */
    public function deleting(Currency $currency): void
    {
        // Add a check for journal entries as well for robustness.
        if ($currency->companies()->exists() || $currency->journalEntries()->exists()) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException('Cannot delete a currency that is in use.');
        }
    }
}
