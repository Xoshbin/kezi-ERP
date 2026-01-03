<?php

namespace Modules\Sales\Actions\Sales;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Sales\DataTransferObjects\Sales\CreateSalesOrderDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Events\QuoteConverted;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\SalesOrder;

/**
 * Action for converting a Quote to a Sales Order
 */
class ConvertQuoteToSalesOrderAction
{
    public function __construct(
        protected CreateSalesOrderAction $createSalesOrderAction,
    ) {}

    /**
     * Execute the action to convert a quote to a sales order
     */
    public function execute(Quote $quote, ?int $userId = null): SalesOrder
    {
        // Validate quote can be converted
        if (! $quote->canBeConverted()) {
            throw new QuoteCannotBeModifiedException(
                'Only accepted quotes that have not been converted can be converted to a sales order.'
            );
        }

        return DB::transaction(function () use ($quote, $userId) {
            // Build the lines DTOs for the sales order
            $linesDtos = [];
            foreach ($quote->lines as $quoteLine) {
                $linesDtos[] = new CreateSalesOrderLineDTO(
                    product_id: $quoteLine->product_id,
                    description: $quoteLine->description,
                    quantity: (float) $quoteLine->quantity,
                    unit_price: $quoteLine->unit_price,
                    tax_id: $quoteLine->tax_id,
                );
            }

            // Create the sales order DTO
            $salesOrderDto = new CreateSalesOrderDTO(
                company_id: $quote->company_id,
                customer_id: $quote->partner_id,
                currency_id: $quote->currency_id,
                created_by_user_id: $userId ?? $quote->created_by_user_id ?? 1,
                reference: $quote->quote_number,
                so_date: Carbon::now(),
                exchange_rate_at_creation: $quote->exchange_rate,
                notes: $quote->notes,
                terms_and_conditions: $quote->terms_and_conditions,
                lines: $linesDtos,
            );

            // Create the sales order
            $salesOrder = $this->createSalesOrderAction->execute($salesOrderDto);

            // Update quote with conversion tracking
            $quote->update([
                'status' => QuoteStatus::Converted,
                'converted_to_sales_order_id' => $salesOrder->id,
                'converted_at' => now(),
            ]);

            // Dispatch event
            event(new QuoteConverted($quote, 'sales_order', $salesOrder));

            return $salesOrder;
        });
    }
}
