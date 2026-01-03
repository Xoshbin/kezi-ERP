<?php

namespace Modules\Sales\Actions\Sales;

use Illuminate\Support\Facades\DB;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;

/**
 * Action for creating a new revision of a Quote
 */
class CreateQuoteRevisionAction
{
    public function __construct(
        protected CreateQuoteAction $createQuoteAction,
        protected CancelQuoteAction $cancelQuoteAction,
    ) {}

    /**
     * Execute the action to create a quote revision
     */
    public function execute(Quote $originalQuote): Quote
    {
        // Validate quote can have a revision created
        if (! $originalQuote->status->canCreateRevision()) {
            throw new QuoteCannotBeModifiedException(
                'Only sent or rejected quotes can have revisions created.'
            );
        }

        return DB::transaction(function () use ($originalQuote) {
            // Build the lines DTOs for the new revision
            $linesDtos = [];
            foreach ($originalQuote->lines as $index => $quoteLine) {
                $linesDtos[] = new CreateQuoteLineDTO(
                    description: $quoteLine->description,
                    quantity: (float) $quoteLine->quantity,
                    unitPrice: $quoteLine->unit_price,
                    productId: $quoteLine->product_id,
                    taxId: $quoteLine->tax_id,
                    incomeAccountId: $quoteLine->income_account_id,
                    unit: $quoteLine->unit,
                    discountPercentage: (float) $quoteLine->discount_percentage,
                    lineOrder: $index,
                );
            }

            // Create the new quote DTO
            $newQuoteDto = new CreateQuoteDTO(
                companyId: $originalQuote->company_id,
                partnerId: $originalQuote->partner_id,
                currencyId: $originalQuote->currency_id,
                quoteDate: now(),
                validUntil: now()->addDays(30),
                lines: $linesDtos,
                notes: $originalQuote->notes,
                termsAndConditions: $originalQuote->terms_and_conditions,
                exchangeRate: $originalQuote->exchange_rate,
                createdByUserId: $originalQuote->created_by_user_id,
            );

            // Create the new revision
            $newQuote = $this->createQuoteAction->execute($newQuoteDto);

            // Update the new quote with version info
            $newQuote->update([
                'version' => $originalQuote->version + 1,
                'previous_version_id' => $originalQuote->id,
            ]);

            // Cancel the original quote
            $this->cancelQuoteAction->execute($originalQuote);

            return $newQuote->fresh();
        });
    }
}
