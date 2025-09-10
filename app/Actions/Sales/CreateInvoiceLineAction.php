<?php

namespace App\Actions\Sales;

use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Tax;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class CreateInvoiceLineAction
{
    public function execute(Invoice $invoice, CreateInvoiceLineDTO $dto): InvoiceLine
    {
        $currency = $invoice->currency;
        $unitPrice = $dto->unit_price; // Already a Money object

        // 1. Perform all calculations *before* creating the model, as per our pattern.
        $subtotal = $unitPrice->multipliedBy($dto->quantity, RoundingMode::HALF_UP);

        $taxAmount = Money::zero($currency->code);
        if ($dto->tax_id) {
            $tax = Tax::findOrFail($dto->tax_id);
            // NOTE: The rate in your Tax model is a float (e.g., 0.10 for 10%)
            $taxAmount = $subtotal->multipliedBy($tax->rate, RoundingMode::HALF_UP);
        }

        // 2. The create method now receives a complete, valid array of attributes.
        /** @var InvoiceLine $invoiceLine */
        $invoiceLine = $invoice->invoiceLines()->create([
            'company_id' => $invoice->company_id,
            'product_id' => $dto->product_id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit_price' => $unitPrice,
            'tax_id' => $dto->tax_id,
            'income_account_id' => $dto->income_account_id,
            'subtotal' => $subtotal, // Explicitly provide the calculated subtotal
            'total_line_tax' => $taxAmount, // Explicitly provide the calculated tax
        ]);

        return $invoiceLine;
    }
}
