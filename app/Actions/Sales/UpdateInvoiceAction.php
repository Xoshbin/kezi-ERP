<?php

namespace App\Actions\Sales;

use App\DataTransferObjects\Sales\UpdateInvoiceDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Currency;
use App\Models\Invoice;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceAction
{
    public function execute(UpdateInvoiceDTO $dto): Invoice
    {
        $invoice = $dto->invoice;

        if ($invoice->status !== Invoice::TYPE_DRAFT) {
            throw new UpdateNotAllowedException('Cannot modify a non-draft invoice.');
        }

        return DB::transaction(function () use ($dto, $invoice) {
            $invoice->update([
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
            ]);

            $invoice->invoiceLines()->delete();

            $currencyCode = Currency::find($dto->currency_id)->code;
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

            $invoice->save();

            return $invoice;
        });
    }
}
