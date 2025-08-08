<?php

namespace App\Actions\Sales;

use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CreateInvoiceLineAction $createInvoiceLineAction
    ) {
    }

    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->invoice_date));

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

            foreach ($dto->lines as $lineDto) {
                $this->createInvoiceLineAction->execute($invoice, $lineDto);
            }

            return $invoice;
        });

        return $invoice->fresh();
    }
}
