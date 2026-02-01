<?php

namespace Jmeryar\Sales\Actions\Sales;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateInvoiceDTO;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;
use Jmeryar\Sales\Models\InvoiceLine;

class UpdateInvoiceAction
{
    public function __construct(protected \Jmeryar\Accounting\Services\Accounting\LockDateService $lockDateService) {}

    public function execute(UpdateInvoiceDTO $dto): Invoice
    {
        $invoice = $dto->invoice;

        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new \Jmeryar\Foundation\Exceptions\UpdateNotAllowedException('Cannot modify a non-draft invoice.');
        }

        $this->lockDateService->enforce($invoice->company, Carbon::parse($dto->invoice_date));

        return DB::transaction(function () use ($dto, $invoice) {
            $invoice->update([
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
                'incoterm' => $dto->incoterm ?? $invoice->incoterm,
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
                    'deferred_start_date' => $lineDto->deferred_start_date,
                    'deferred_end_date' => $lineDto->deferred_end_date,
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
