<?php

namespace App\Actions\Sales;

use Carbon\Carbon;
use App\Models\InvoiceLine;
use App\DataTransferObjects\Sales\UpdateInvoiceDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Invoice;
use App\Models\Tax;
use App\Enums\Sales\InvoiceStatus;
use App\Services\Accounting\LockDateService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceAction
{
    public function __construct(protected LockDateService $lockDateService)
    {
    }

    public function execute(UpdateInvoiceDTO $dto): Invoice
    {
        $invoice = $dto->invoice;

        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new UpdateNotAllowedException('Cannot modify a non-draft invoice.');
        }

        $this->lockDateService->enforce($invoice->company, Carbon::parse($dto->invoice_date));

        return DB::transaction(function () use ($dto, $invoice) {
            $invoice->update([
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
            ]);

            $invoice->invoiceLines()->delete();

            $lines = [];
            foreach ($dto->lines as $lineDto) {
                // Calculate subtotal and tax amounts
                $unitPrice = $lineDto->unit_price;
                $subtotal = $unitPrice->multipliedBy($lineDto->quantity, RoundingMode::HALF_UP);

                $taxAmount = Money::of(0, $invoice->currency->code);
                if ($lineDto->tax_id) {
                    $tax = Tax::find($lineDto->tax_id);
                    if ($tax) {
                        $taxAmount = $subtotal->multipliedBy($tax->rate, RoundingMode::HALF_UP);
                    }
                }

                $lines[] = new InvoiceLine([
                    'company_id' => $invoice->company_id,
                    'product_id' => $lineDto->product_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'unit_price' => $lineDto->unit_price,
                    'subtotal' => $subtotal,
                    'total_line_tax' => $taxAmount,
                    'income_account_id' => $lineDto->income_account_id,
                    'tax_id' => $lineDto->tax_id,
                ]);
            }

            $invoice->setRelation('invoiceLines', collect($lines));
            $invoice->calculateTotalsFromLines();
            $invoice->save();

            foreach ($lines as $line) {
                $line->invoice_id = $invoice->id;
                $line->save();
            }

            return $invoice;
        });
    }
}
