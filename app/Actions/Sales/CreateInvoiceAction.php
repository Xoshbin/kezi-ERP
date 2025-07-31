<?php

namespace App\Actions\Sales;

use Brick\Money\Money;
use App\Models\Invoice;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingValidationService;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;

class CreateInvoiceAction
{
    public function __construct(
        private readonly AccountingValidationService $accountingValidationService = new AccountingValidationService()
    ) {}

    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        $this->accountingValidationService->checkIfPeriodIsLocked($dto->company_id, $dto->invoice_date);

        // 1. Execute the transaction and store the created (but stale) invoice in a variable.
        $invoice = DB::transaction(function () use ($dto) {
            $currencyCode = Currency::find($dto->currency_id)->code;

            $invoice = Invoice::create([
                'company_id' => $dto->company_id,
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
                'status' => Invoice::STATUS_DRAFT,
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

        // 2. Now, refresh the model from the database and return the fresh version.
        return $invoice->fresh();
    }
}
