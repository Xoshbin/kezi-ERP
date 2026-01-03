<?php

namespace Modules\Sales\Actions\Sales;

use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Events\QuoteAccepted;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;

/**
 * Action for marking a Quote as accepted by the customer
 */
class AcceptQuoteAction
{
    /**
     * Execute the action to accept a quote
     */
    public function execute(Quote $quote): Quote
    {
        // Validate quote can be accepted
        if (! $quote->status->canBeAccepted()) {
            throw new QuoteCannotBeModifiedException(
                'Only sent quotes can be accepted.'
            );
        }

        // Check if quote has expired
        if ($quote->isExpired()) {
            throw new QuoteCannotBeModifiedException(
                'Cannot accept an expired quote. Please create a new revision.'
            );
        }

        // Update status
        $quote->update([
            'status' => QuoteStatus::Accepted,
        ]);

        // Dispatch event
        event(new QuoteAccepted($quote));

        return $quote->refresh();
    }
}
