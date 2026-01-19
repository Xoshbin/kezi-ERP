<?php

namespace Modules\Sales\Actions\Sales;

use Modules\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteLine;

/**
 * Action for creating a new Quote Line
 */
class CreateQuoteLineAction
{
    /**
     * Execute the action to create a quote line
     */
    public function execute(Quote $quote, CreateQuoteLineDTO $dto, int $lineOrder = 0): QuoteLine
    {
        $currency = $quote->currency;

        // Create the line - observer will handle calculations
        return QuoteLine::create([
            'quote_id' => $quote->id,
            'product_id' => $dto->productId,
            'tax_id' => $dto->taxId,
            'income_account_id' => $dto->incomeAccountId,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit' => $dto->unit,
            'line_order' => $dto->lineOrder ?: $lineOrder,
            'unit_price' => $dto->unitPrice,
            'discount_percentage' => $dto->discountPercentage,
            'discount_amount' => 0, // Will be calculated by observer
            'subtotal' => 0, // Will be calculated by observer
            'tax_amount' => 0, // Will be calculated by observer
            'total' => 0, // Will be calculated by observer
        ]);
    }
}
