<?php

namespace Jmeryar\Sales\Actions\Sales;

use Illuminate\Support\Facades\DB;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateQuoteDTO;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;
use Jmeryar\Sales\Models\QuoteLine;

/**
 * Action for updating an existing Quote
 */
class UpdateQuoteAction
{
    public function __construct(
        protected CreateQuoteLineAction $createLineAction,
    ) {}

    /**
     * Execute the action to update a quote
     */
    public function execute(UpdateQuoteDTO $dto): Quote
    {
        return DB::transaction(function () use ($dto) {
            $quote = Quote::findOrFail($dto->quoteId);

            // Check if quote can be edited
            if (! $quote->isEditable()) {
                throw new QuoteCannotBeModifiedException(
                    'Only draft or sent quotes can be updated.'
                );
            }

            // Update quote fields if provided
            $updateData = [];

            if ($dto->partnerId !== null) {
                $updateData['partner_id'] = $dto->partnerId;
            }
            if ($dto->currencyId !== null) {
                $updateData['currency_id'] = $dto->currencyId;
            }
            if ($dto->quoteDate !== null) {
                $updateData['quote_date'] = $dto->quoteDate;
            }
            if ($dto->validUntil !== null) {
                $updateData['valid_until'] = $dto->validUntil;
            }
            if ($dto->notes !== null) {
                $updateData['notes'] = $dto->notes;
            }
            if ($dto->termsAndConditions !== null) {
                $updateData['terms_and_conditions'] = $dto->termsAndConditions;
            }
            if ($dto->exchangeRate !== null) {
                $updateData['exchange_rate'] = $dto->exchangeRate;
            }

            if (! empty($updateData)) {
                $quote->update($updateData);
            }

            // Handle lines if provided
            if ($dto->lines !== null) {
                $this->updateLines($quote, $dto->lines);
            }

            // Refresh and recalculate totals
            $quote->refresh();
            $quote->calculateTotals();
            $quote->save();

            return $quote;
        });
    }

    /**
     * Update quote lines based on provided DTOs
     *
     * @param  array<\Jmeryar\Sales\DataTransferObjects\Sales\UpdateQuoteLineDTO>  $lines
     */
    private function updateLines(Quote $quote, array $lines): void
    {
        $existingLineIds = [];

        foreach ($lines as $index => $lineDto) {
            if ($lineDto->shouldDelete && $lineDto->lineId) {
                // Delete the line
                QuoteLine::where('id', $lineDto->lineId)
                    ->where('quote_id', $quote->id)
                    ->delete();

                continue;
            }

            if ($lineDto->lineId) {
                // Update existing line
                $line = QuoteLine::where('id', $lineDto->lineId)
                    ->where('quote_id', $quote->id)
                    ->first();

                if ($line) {
                    $updateData = [];

                    if ($lineDto->description !== null) {
                        $updateData['description'] = $lineDto->description;
                    }
                    if ($lineDto->quantity !== null) {
                        $updateData['quantity'] = $lineDto->quantity;
                    }
                    if ($lineDto->unitPrice !== null) {
                        $updateData['unit_price'] = $lineDto->unitPrice;
                    }
                    if ($lineDto->productId !== null) {
                        $updateData['product_id'] = $lineDto->productId;
                    }
                    if ($lineDto->taxId !== null) {
                        $updateData['tax_id'] = $lineDto->taxId;
                    }
                    if ($lineDto->incomeAccountId !== null) {
                        $updateData['income_account_id'] = $lineDto->incomeAccountId;
                    }
                    if ($lineDto->unit !== null) {
                        $updateData['unit'] = $lineDto->unit;
                    }
                    if ($lineDto->discountPercentage !== null) {
                        $updateData['discount_percentage'] = $lineDto->discountPercentage;
                    }
                    if ($lineDto->lineOrder !== null) {
                        $updateData['line_order'] = $lineDto->lineOrder;
                    }

                    if (! empty($updateData)) {
                        $line->update($updateData);
                    }

                    $existingLineIds[] = $line->id;
                }
            } else {
                // Create new line - need to build CreateQuoteLineDTO
                $createDto = new \Jmeryar\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO(
                    description: $lineDto->description ?? '',
                    quantity: $lineDto->quantity ?? 1,
                    unitPrice: $lineDto->unitPrice ?? \Brick\Money\Money::of(0, $quote->currency->code),
                    productId: $lineDto->productId,
                    taxId: $lineDto->taxId,
                    incomeAccountId: $lineDto->incomeAccountId,
                    unit: $lineDto->unit,
                    discountPercentage: $lineDto->discountPercentage ?? 0.0,
                    discountAmount: $lineDto->discountAmount,
                    lineOrder: $lineDto->lineOrder ?? $index,
                );

                $newLine = $this->createLineAction->execute($quote, $createDto, $index);
                $existingLineIds[] = $newLine->id;
            }
        }
    }
}
