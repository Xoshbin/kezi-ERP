<?php

namespace Kezi\Sales\Actions\Sales;

use Illuminate\Support\Facades\DB;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Events\QuoteConverted;
use Kezi\Sales\Exceptions\QuoteCannotBeModifiedException;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\Quote;

/**
 * Action for converting a Quote directly to an Invoice
 */
class ConvertQuoteToInvoiceAction
{
    public function __construct(
        protected CreateInvoiceAction $createInvoiceAction,
    ) {}

    /**
     * Execute the action to convert a quote to an invoice
     */
    public function execute(Quote $quote): Invoice
    {
        // Validate quote can be converted
        if (! $quote->canBeConverted()) {
            throw new QuoteCannotBeModifiedException(
                __('sales::quote.messages.converted_invoice_only_accepted')
            );
        }

        return DB::transaction(function () use ($quote) {
            // Build the lines DTOs for the invoice
            $linesDtos = [];
            foreach ($quote->lines as $quoteLine) {
                $linesDtos[] = new CreateInvoiceLineDTO(
                    product_id: $quoteLine->product_id,
                    description: $quoteLine->description,
                    quantity: (float) $quoteLine->quantity,
                    unit_price: $quoteLine->unit_price,
                    tax_id: $quoteLine->tax_id,
                    income_account_id: $quoteLine->income_account_id,
                );
            }

            // Create the invoice DTO
            $invoiceDto = new CreateInvoiceDTO(
                company_id: $quote->company_id,
                customer_id: $quote->partner_id,
                currency_id: $quote->currency_id,
                invoice_date: now()->toDateString(),
                due_date: now()->addDays(30)->toDateString(),
                lines: $linesDtos,
                fiscal_position_id: null,
            );

            // Create the invoice
            $invoice = $this->createInvoiceAction->execute($invoiceDto);

            // Update quote with conversion tracking
            $quote->update([
                'status' => QuoteStatus::Converted,
                'converted_to_invoice_id' => $invoice->id,
                'converted_at' => now(),
            ]);

            // Dispatch event
            event(new QuoteConverted($quote, 'invoice', $invoice));

            return $invoice;
        });
    }
}
