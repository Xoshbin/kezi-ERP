<?php

namespace Jmeryar\Sales\Services;

use Jmeryar\Sales\Actions\Sales\AcceptQuoteAction;
use Jmeryar\Sales\Actions\Sales\CancelQuoteAction;
use Jmeryar\Sales\Actions\Sales\ConvertQuoteToInvoiceAction;
use Jmeryar\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction;
use Jmeryar\Sales\Actions\Sales\CreateQuoteAction;
use Jmeryar\Sales\Actions\Sales\CreateQuoteRevisionAction;
use Jmeryar\Sales\Actions\Sales\RejectQuoteAction;
use Jmeryar\Sales\Actions\Sales\SendQuoteAction;
use Jmeryar\Sales\Actions\Sales\UpdateQuoteAction;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateQuoteDTO;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Events\QuoteCreated;
use Jmeryar\Sales\Events\QuoteExpired;
use Jmeryar\Sales\Models\Invoice;
use Jmeryar\Sales\Models\Quote;
use Jmeryar\Sales\Models\SalesOrder;

/**
 * Service for orchestrating quote operations.
 *
 * This service delegates business operations to Actions while
 * coordinating complex workflows and dispatching events.
 */
class QuoteService
{
    public function __construct(
        protected CreateQuoteAction $createAction,
        protected UpdateQuoteAction $updateAction,
        protected SendQuoteAction $sendAction,
        protected AcceptQuoteAction $acceptAction,
        protected RejectQuoteAction $rejectAction,
        protected CancelQuoteAction $cancelAction,
        protected ConvertQuoteToSalesOrderAction $convertToOrderAction,
        protected ConvertQuoteToInvoiceAction $convertToInvoiceAction,
        protected CreateQuoteRevisionAction $revisionAction,
    ) {}

    /**
     * Create a new quote.
     */
    public function create(CreateQuoteDTO $dto): Quote
    {
        $quote = $this->createAction->execute($dto);

        event(new QuoteCreated($quote));

        return $quote;
    }

    /**
     * Update an existing quote.
     */
    public function update(UpdateQuoteDTO $dto): Quote
    {
        return $this->updateAction->execute($dto);
    }

    /**
     * Send a quote to the customer.
     */
    public function send(Quote $quote): Quote
    {
        return $this->sendAction->execute($quote);
    }

    /**
     * Mark a quote as accepted by the customer.
     */
    public function accept(Quote $quote): Quote
    {
        return $this->acceptAction->execute($quote);
    }

    /**
     * Mark a quote as rejected by the customer.
     */
    public function reject(Quote $quote, ?string $reason = null): Quote
    {
        return $this->rejectAction->execute($quote, $reason);
    }

    /**
     * Cancel a quote.
     */
    public function cancel(Quote $quote): Quote
    {
        return $this->cancelAction->execute($quote);
    }

    /**
     * Convert a quote to a sales order.
     */
    public function convertToSalesOrder(Quote $quote, ?int $userId = null): SalesOrder
    {
        return $this->convertToOrderAction->execute($quote, $userId);
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertToInvoice(Quote $quote): Invoice
    {
        return $this->convertToInvoiceAction->execute($quote);
    }

    /**
     * Create a new revision of a quote.
     */
    public function createRevision(Quote $quote): Quote
    {
        return $this->revisionAction->execute($quote);
    }

    /**
     * Duplicate a quote with new dates.
     */
    public function duplicate(Quote $quote): Quote
    {
        $lines = [];
        foreach ($quote->lines as $line) {
            $lines[] = new \Jmeryar\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO(
                description: $line->description,
                quantity: (float) $line->quantity,
                unitPrice: $line->unit_price,
                productId: $line->product_id,
                taxId: $line->tax_id,
                incomeAccountId: $line->income_account_id,
                unit: $line->unit,
                discountPercentage: (float) $line->discount_percentage,
            );
        }

        $dto = new CreateQuoteDTO(
            companyId: $quote->company_id,
            partnerId: $quote->partner_id,
            currencyId: $quote->currency_id,
            quoteDate: now(),
            validUntil: now()->addDays(30),
            lines: $lines,
            notes: $quote->notes,
            termsAndConditions: $quote->terms_and_conditions,
            exchangeRate: $quote->exchange_rate,
            createdByUserId: $quote->created_by_user_id,
        );

        return $this->create($dto);
    }

    /**
     * Check and mark expired quotes.
     * Returns the number of quotes marked as expired.
     */
    public function checkExpiredQuotes(): int
    {
        $expiredQuotes = Quote::expired()->get();
        $count = 0;

        foreach ($expiredQuotes as $quote) {
            $quote->update(['status' => QuoteStatus::Expired]);
            event(new QuoteExpired($quote));
            $count++;
        }

        return $count;
    }
}
