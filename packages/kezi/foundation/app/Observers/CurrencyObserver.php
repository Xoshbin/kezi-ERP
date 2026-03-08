<?php

namespace Kezi\Foundation\Observers;

use Kezi\Foundation\Models\Currency;

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
            throw new \Kezi\Foundation\Exceptions\DeletionNotAllowedException(__('foundation::exceptions.currency.in_use'));
        }
    }
}
