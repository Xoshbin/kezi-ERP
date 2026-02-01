<?php

namespace Kezi\Sales\Actions\Sales;

use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Events\QuoteSent;
use Kezi\Sales\Exceptions\QuoteCannotBeModifiedException;
use Kezi\Sales\Models\Quote;

/**
 * Action for sending a Quote to a customer
 */
class SendQuoteAction
{
    /**
     * Execute the action to send a quote
     */
    public function execute(Quote $quote): Quote
    {
        // Validate quote can be sent
        if (! $quote->status->canBeSent()) {
            throw new QuoteCannotBeModifiedException(
                'Only draft quotes can be sent.'
            );
        }

        // Validate quote has lines
        if ($quote->lines()->count() === 0) {
            throw new QuoteCannotBeModifiedException(
                'Cannot send a quote without line items.'
            );
        }

        // Update status
        $quote->update([
            'status' => QuoteStatus::Sent,
        ]);

        // Dispatch event
        event(new QuoteSent($quote));

        return $quote->refresh();
    }
}
