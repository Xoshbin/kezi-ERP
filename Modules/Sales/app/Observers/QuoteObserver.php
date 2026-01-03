<?php

namespace Modules\Sales\Observers;

use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;

/**
 * Observer for Quote model lifecycle events.
 *
 * Handles setting defaults and enforcing business rules
 * around quote modification and deletion.
 */
class QuoteObserver
{
    /**
     * Handle the Quote "creating" event.
     */
    public function creating(Quote $quote): void
    {
        // Set default quote date if not provided
        if (! $quote->quote_date) {
            $quote->quote_date = now();
        }

        // Set default valid_until if not provided (30 days from quote date)
        if (! $quote->valid_until) {
            $quote->valid_until = $quote->quote_date->copy()->addDays(30);
        }

        // Set default status
        if (! $quote->status) {
            $quote->status = QuoteStatus::Draft;
        }

        // Set default version
        if (! $quote->version) {
            $quote->version = 1;
        }
    }

    /**
     * Handle the Quote "updating" event.
     */
    public function updating(Quote $quote): void
    {
        $originalStatus = $quote->getOriginal('status');

        // Prevent updating converted quotes
        if ($originalStatus === QuoteStatus::Converted || $originalStatus === QuoteStatus::Converted->value) {
            throw new QuoteCannotBeModifiedException(__('sales::quote.messages.converted_no_modify'));
        }

        // Prevent updating cancelled quotes (except for specific status changes)
        if (($originalStatus === QuoteStatus::Cancelled || $originalStatus === QuoteStatus::Cancelled->value) && ! $quote->isDirty('status')) {
            throw new QuoteCannotBeModifiedException(__('sales::quote.messages.cancelled_no_modify'));
        }
    }

    /**
     * Handle the Quote "deleting" event.
     */
    public function deleting(Quote $quote): void
    {
        // Only allow deletion of draft quotes
        if ($quote->status !== QuoteStatus::Draft) {
            throw new QuoteCannotBeModifiedException(
                __('sales::quote.messages.draft_delete_only')
            );
        }
    }
}
