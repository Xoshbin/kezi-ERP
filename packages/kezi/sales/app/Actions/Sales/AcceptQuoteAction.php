<?php

namespace Kezi\Sales\Actions\Sales;

use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Events\QuoteAccepted;
use Kezi\Sales\Exceptions\QuoteCannotBeModifiedException;
use Kezi\Sales\Models\Quote;

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
                __('sales::exceptions.quote.accept_sent_only')
            );
        }

        // Check if quote has expired
        if ($quote->isExpired()) {
            throw new QuoteCannotBeModifiedException(
                __('sales::exceptions.quote.accept_expired')
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
