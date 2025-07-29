<?php

namespace App\Actions\Sales;

use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\Models\Currency;
use App\Models\Invoice;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            $currencyCode = Currency::find($dto->currency_id)->code;

            $invoice = Invoice::create([
                'company_id' => $dto->company_id,
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
                'status' => Invoice::TYPE_DRAFT,
                'total_amount' => Money::of(0, $currencyCode),
                'total_tax' => Money::of(0, $currencyCode),
            ]);

            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'product_id' => $lineDto->product_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'unit_price' => Money::of($lineDto->unit_price, $currencyCode),
                    'tax_id' => $lineDto->tax_id,
                    'income_account_id' => $lineDto->income_account_id,
                ];
            }

            if (!empty($linesToCreate)) {
                $invoice->invoiceLines()->createMany($linesToCreate);
            }

            // The model's saving observer will calculate totals.
            $invoice->save();

            return $invoice;
        });
    }
}
